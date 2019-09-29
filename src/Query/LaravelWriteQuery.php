<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 29/09/19
 * Time: 6:08 PM
 */

namespace AlgoWeb\PODataLaravel\Query;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use POData\Common\InvalidOperationException;
use POData\Common\ODataException;
use POData\Providers\Metadata\ResourceSet;

class LaravelWriteQuery extends LaravelBaseQuery
{


    /**
     * @param $data
     * @param $paramList
     * @param Model|null $sourceEntityInstance
     * @return array
     */
    public function createUpdateDeleteProcessInput($data, $paramList, Model $sourceEntityInstance)
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
    public function createUpdateDeleteProcessOutput($result)
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
    public function createUpdateCoreWrapper(ResourceSet $sourceResourceSet, $data, $verb, Model $source = null)
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
    public function createUpdateDeleteCore($sourceEntityInstance, $data, $class, $verb)
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
}
