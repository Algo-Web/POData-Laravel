<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 29/09/19
 * Time: 6:08 PM
 */

namespace AlgoWeb\PODataLaravel\Query;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\App;
use POData\Common\InvalidOperationException;
use POData\Common\ODataException;
use POData\Providers\Metadata\ResourceSet;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\KeyDescriptor;
use Symfony\Component\Process\Exception\InvalidArgumentException;

class LaravelWriteQuery extends LaravelBaseQuery
{


    /**
     * @param $data
     * @param $paramList
     * @param Model|null $sourceEntityInstance
     * @return array
     */
    protected function createUpdateDeleteProcessInput($data, $paramList, Model $sourceEntityInstance)
    {
        $parms = [];

        foreach ($paramList as $spec) {
            $varType = isset($spec['type']) ? $spec['type'] : null;
            $varName = $spec['name'];
            if (null == $varType) {
                $parms[] = ('id' == $varName) ? $sourceEntityInstance->getKey() : $sourceEntityInstance->$varName;
                continue;
            }
            // TODO: Give this smarts and actively pick up instantiation details
            $var = new $varType();
            if ($spec['isRequest']) {
                $var->setMethod('POST');
                $var->request = new \Symfony\Component\HttpFoundation\ParameterBag($data);
            }
            $parms[] = $var;
        }
        return $parms;
    }


    /**
     * @param $result
     * @throws ODataException
     * @return array|mixed
     */
    protected function createUpdateDeleteProcessOutput($result)
    {
        if (!($result instanceof \Illuminate\Http\JsonResponse)) {
            throw ODataException::createInternalServerError('Controller response not well-formed json.');
        }
        $outData = $result->getData();
        if (is_object($outData)) {
            $outData = (array) $outData;
        }

        if (!is_array($outData)) {
            throw ODataException::createInternalServerError('Controller response does not have an array.');
        }
        if (!(key_exists('id', $outData) && key_exists('status', $outData) && key_exists('errors', $outData))) {
            throw ODataException::createInternalServerError(
                'Controller response array missing at least one of id, status and/or errors fields.'
            );
        }
        return $outData;
    }

    /**
     * @param ResourceSet $sourceResourceSet
     * @param $data
     * @param                            $verb
     * @param  Model|null                $source
     * @throws InvalidOperationException
     * @throws ODataException
     * @throws \Exception
     * @return Model|null
     */
    protected function createUpdateCoreWrapper(ResourceSet $sourceResourceSet, $data, $verb, Model $source = null)
    {
        $lastWord = 'update' == $verb ? 'updated' : 'created';
        $class = $sourceResourceSet->getResourceType()->getInstanceType()->getName();
        if (!$this->getAuth()->canAuth($this->getVerbMap()[$verb], $class, $source)) {
            throw new ODataException('Access denied', 403);
        }

        $payload = $this->createUpdateDeleteCore($source, $data, $class, $verb);

        $success = isset($payload['id']);

        if ($success) {
            try {
                return $class::findOrFail($payload['id']);
            } catch (\Exception $e) {
                throw new ODataException($e->getMessage(), 500);
            }
        }
        throw new ODataException('Target model not successfully ' . $lastWord, 422);
    }


    /**
     * @param $sourceEntityInstance
     * @param $data
     * @param $class
     * @param string $verb
     *
     * @throws ODataException
     * @throws InvalidOperationException
     * @return array|mixed
     */
    protected function createUpdateDeleteCore($sourceEntityInstance, $data, $class, $verb)
    {
        $raw = $this->getControllerContainer();
        $map = $raw->getMetadata();

        if (!array_key_exists($class, $map)) {
            throw new InvalidOperationException('Controller mapping missing for class ' . $class . '.');
        }
        $goal = $raw->getMapping($class, $verb);
        if (null == $goal) {
            throw new InvalidOperationException(
                'Controller mapping missing for ' . $verb . ' verb on class ' . $class . '.'
            );
        }

        if (null === $data) {
            $msg = 'Data must not be null';
            throw new InvalidOperationException($msg);
        }
        if (is_object($data)) {
            $arrayData = (array) $data;
        } else {
            $arrayData = $data;
        }
        if (!is_array($arrayData)) {
            throw ODataException::createPreConditionFailedError(
                'Data not resolvable to key-value array.'
            );
        }

        $controlClass = $goal['controller'];
        $method = $goal['method'];
        $paramList = $goal['parameters'];
        $controller = App::make($controlClass);
        $parms = $this->createUpdateDeleteProcessInput($arrayData, $paramList, $sourceEntityInstance);
        unset($data);

        $result = call_user_func_array(array($controller, $method), $parms);

        return $this->createUpdateDeleteProcessOutput($result);
    }

    /**
     * Delete resource from a resource set.
     *
     * @param ResourceSet $sourceResourceSet
     * @param object      $sourceEntityInstance
     *
     * @return bool true if resources sucessfully deteled, otherwise false
     * @throws \Exception
     */
    public function deleteResource(
        ResourceSet $sourceResourceSet,
        $sourceEntityInstance
    ) {
        $source = $this->unpackSourceEntity($sourceEntityInstance);

        $verb = 'delete';
        if (!($source instanceof Model)) {
            throw new InvalidArgumentException('Source entity must be an Eloquent model.');
        }

        $class = $sourceResourceSet->getResourceType()->getInstanceType()->getName();
        $id = $source->getKey();
        $name = $source->getKeyName();
        $data = [$name => $id];

        $data = $this->createUpdateDeleteCore($source, $data, $class, $verb);

        $success = isset($data['id']);
        if ($success) {
            return true;
        }
        throw new ODataException('Target model not successfully deleted', 422);
    }

    /**
     * @param ResourceSet     $resourceSet          The entity set containing the entity to fetch
     * @param Model|Relation  $sourceEntityInstance The source entity instance
     * @param object          $data                 the New data for the entity instance
     *
     * @return Model|null                           returns the newly created model if successful,
     *                                              or null if model creation failed.
     * @throws \Exception
     */
    public function createResourceforResourceSet(
        ResourceSet $resourceSet,
        $sourceEntityInstance,
        $data
    ) {
        $verb = 'create';
        return $this->createUpdateMainWrapper($resourceSet, $sourceEntityInstance, $data, $verb);
    }

    /**
     * Updates a resource.
     *
     * @param ResourceSet       $sourceResourceSet    The entity set containing the source entity
     * @param Model|Relation    $sourceEntityInstance The source entity instance
     * @param KeyDescriptor     $keyDescriptor        The key identifying the entity to fetch
     * @param object            $data                 the New data for the entity instance
     * @param bool              $shouldUpdate         Should undefined values be updated or reset to default
     *
     * @return Model|null the new resource value if it is assignable or throw exception for null
     * @throws \Exception
     */
    public function updateResource(
        ResourceSet $sourceResourceSet,
        $sourceEntityInstance,
        KeyDescriptor $keyDescriptor,
        $data,
        $shouldUpdate = false
    ) {
        $verb = 'update';
        return $this->createUpdateMainWrapper($sourceResourceSet, $sourceEntityInstance, $data, $verb);
    }

    /**
     * Puts an entity instance to entity set identified by a key.
     *
     * @param ResourceSet   $resourceSet   The entity set containing the entity to update
     * @param KeyDescriptor $keyDescriptor The key identifying the entity to update
     * @param $data
     *
     * @return bool|null Returns result of executing query
     */
    public function putResource(
        ResourceSet $resourceSet,
        KeyDescriptor $keyDescriptor,
        $data
    ) {
        // TODO: Implement putResource() method.
        return true;
    }


    /**
     * @param ResourceSet $resourceSet
     * @param Model|Relation|null $sourceEntityInstance
     * @param mixed $data
     * @param mixed $verb
     * @return Model|null
     * @throws InvalidOperationException
     * @throws ODataException
     */
    protected function createUpdateMainWrapper(ResourceSet $resourceSet, $sourceEntityInstance, $data, $verb)
    {
        /** @var Model|null $source */
        $source = $this->unpackSourceEntity($sourceEntityInstance);

        $result = $this->createUpdateCoreWrapper($resourceSet, $data, $verb, $source);
        if (null !== $result) {
            LaravelQuery::queueModel($result);
        }
        return $result;
    }
}
