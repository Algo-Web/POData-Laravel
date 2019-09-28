<?php

namespace AlgoWeb\PODataLaravel\Query;

use AlgoWeb\PODataLaravel\Auth\NullAuthProvider;
use AlgoWeb\PODataLaravel\Controllers\MetadataControllerContainer;
use AlgoWeb\PODataLaravel\Enums\ActionVerb;
use AlgoWeb\PODataLaravel\Interfaces\AuthInterface;
use AlgoWeb\PODataLaravel\Providers\MetadataProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use POData\Common\InvalidOperationException;
use POData\Common\ODataException;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Query\QueryResult;
use POData\Providers\Query\QueryType;
use POData\UriProcessor\QueryProcessor\ExpressionParser\FilterInfo;
use POData\UriProcessor\QueryProcessor\OrderByParser\InternalOrderByInfo;
use POData\UriProcessor\QueryProcessor\SkipTokenParser\SkipTokenInfo;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\KeyDescriptor;
use Symfony\Component\Process\Exception\InvalidArgumentException;

class LaravelBulkQuery
{
    protected $auth;
    protected $metadataProvider;
    protected $query;
    protected $controllerContainer;

    public function __construct(LaravelQuery &$query, AuthInterface $auth = null)
    {
        $this->auth = isset($auth) ? $auth : new NullAuthProvider();
        $this->metadataProvider = new MetadataProvider(App::make('app'));
        $this->query = $query;
        $this->controllerContainer = App::make('metadataControllers');
    }

    /**
     * Create multiple new resources in a resource set.
     *
     * @param ResourceSet $sourceResourceSet The entity set containing the entity to fetch
     * @param object[] $data The new data for the entity instance
     *
     * @return object[] returns the newly created model if successful, or throws an exception if model creation failed
     * @throws \Exception
     * @throws InvalidOperationException
     * @throws \ReflectionException
     */
    public function createBulkResourceforResourceSet(
        ResourceSet $sourceResourceSet,
        array $data
    ) {
        $verbName = 'bulkCreate';
        $mapping = $this->getOptionalVerbMapping($sourceResourceSet, $verbName);

        $result = [];
        try {
            DB::beginTransaction();
            if (null === $mapping) {
                foreach ($data as $newItem) {
                    $raw = $this->getQuery()->createResourceforResourceSet($sourceResourceSet, null, $newItem);
                    if (null === $raw) {
                        throw new \Exception('Bulk model creation failed');
                    }
                    $result[] = $raw;
                }
            } else {
                $keyDescriptor = null;
                $pastVerb = 'created';
                $result = $this->processBulkCustom($sourceResourceSet, $data, $mapping, $pastVerb, $keyDescriptor);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
        return $result;
    }

    /**
     * Updates a group of resources in a resource set.
     *
     * @param ResourceSet     $sourceResourceSet    The entity set containing the source entity
     * @param object          $sourceEntityInstance The source entity instance
     * @param KeyDescriptor[] $keyDescriptor        The key identifying the entity to fetch
     * @param object[]        $data                 The new data for the entity instances
     * @param bool            $shouldUpdate         Should undefined values be updated or reset to default
     *
     * @return object[] the new resource value if it is assignable, or throw exception for null
     * @throws \Exception
     * @throws InvalidOperationException
     */
    public function updateBulkResource(
        ResourceSet $sourceResourceSet,
        $sourceEntityInstance,
        array $keyDescriptor,
        array $data,
        $shouldUpdate = false
    ) {
        $numKeys = count($keyDescriptor);
        if ($numKeys !== count($data)) {
            $msg = 'Key descriptor array and data array must be same length';
            throw new \InvalidArgumentException($msg);
        }
        $result = [];

        $verbName = 'bulkUpdate';
        $mapping = $this->getOptionalVerbMapping($sourceResourceSet, $verbName);

        try {
            DB::beginTransaction();
            if (null === $mapping) {
                for ($i = 0; $i < $numKeys; $i++) {
                    $newItem = $data[$i];
                    $newKey = $keyDescriptor[$i];
                    $raw = $this->getQuery()->
                        updateResource($sourceResourceSet, $sourceEntityInstance, $newKey, $newItem);
                    if (null === $raw) {
                        throw new \Exception('Bulk model update failed');
                    }
                    $result[] = $raw;
                }
            } else {
                $pastVerb = 'updated';
                $result = $this->processBulkCustom($sourceResourceSet, $data, $mapping, $pastVerb, $keyDescriptor);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
        return $result;
    }

    /**
     * Prepare bulk request from supplied data.  If $keyDescriptors is not null, its elements are assumed to
     * correspond 1-1 to those in $data.
     *
     * @param $paramList
     * @param array                $data
     * @param KeyDescriptor[]|null $keyDescriptors
     * @return array
     *
     * @throws InvalidOperationException
     */
    protected function prepareBulkRequestInput($paramList, array $data, array $keyDescriptors = null)
    {
        $parms = [];
        $isCreate = null === $keyDescriptors;

        // for moment, we're only processing parameters of type Request
        foreach ($paramList as $spec) {
            $varType = isset($spec['type']) ? $spec['type'] : null;
            if (null !== $varType) {
                $var = new $varType();
                if ($spec['isRequest']) {
                    $var->setMethod($isCreate ? 'POST' : 'PUT');
                    $bulkData = ['data' => $data];
                    if (null !== $keyDescriptors) {
                        $keys = [];
                        foreach ($keyDescriptors as $desc) {
                            if (!($desc instanceof KeyDescriptor)) {
                                $msg = get_class($desc);
                                throw new InvalidOperationException($msg);
                            }
                            $rawPayload = $desc->getNamedValues();
                            $keyPayload = [];
                            foreach ($rawPayload as $keyName => $keyVal) {
                                $keyPayload[$keyName] = $keyVal[0];
                            }
                            $keys[] = $keyPayload;
                        }
                        $bulkData['keys'] = $keys;
                    }
                    $var->request = new \Symfony\Component\HttpFoundation\ParameterBag($bulkData);
                }
                $parms[] = $var;
            }
        }
        return $parms;
    }

    /**
     * @param ResourceSet $sourceResourceSet
     * @param array $data
     * @param $mapping
     * @param $pastVerb
     * @param  KeyDescriptor[]|null $keyDescriptor
     * @throws ODataException
     * @throws \ReflectionException
     * @throws InvalidOperationException
     * @return array
     */
    protected function processBulkCustom(
        ResourceSet $sourceResourceSet,
        array $data,
        $mapping,
        $pastVerb,
        array $keyDescriptor = null
    ) {
        $class = $sourceResourceSet->getResourceType()->getInstanceType()->getName();
        $controlClass = $mapping['controller'];
        $method = $mapping['method'];
        $paramList = $mapping['parameters'];
        $controller = App::make($controlClass);
        $parms = $this->prepareBulkRequestInput($paramList, $data, $keyDescriptor);

        $callResult = call_user_func_array(array($controller, $method), $parms);
        $payload = $this->createUpdateDeleteProcessOutput($callResult);
        $success = isset($payload['id']) && is_array($payload['id']);

        if ($success) {
            try {
                // return array of Model objects underlying collection returned by findMany
                $result = $class::findMany($payload['id'])->flatten()->all();
                foreach ($result as $model) {
                    LaravelQuery::queueModel($model);
                }
                return $result;
            } catch (\Exception $e) {
                throw new ODataException($e->getMessage(), 500);
            }
        } else {
            $msg = 'Target models not successfully ' . $pastVerb;
            throw new ODataException($msg, 422);
        }
    }

    /**
     * @param ResourceSet $sourceResourceSet
     * @param $verbName
     * @return array|null
     * @throws InvalidOperationException
     * @throws \ReflectionException
     */
    protected function getOptionalVerbMapping(ResourceSet $sourceResourceSet, $verbName)
    {
        // dig up target class name
        $type = $sourceResourceSet->getResourceType()->getInstanceType();
        if (!($type instanceof \ReflectionClass)) {
            $msg = null == $type ? 'Null' : get_class($type);
            throw new InvalidOperationException($msg);
        }
        $modelName = $type->getName();
        return $this->getControllerContainer()->getMapping($modelName, $verbName);
    }

    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Dig out local copy of controller metadata mapping.
     *
     * @return MetadataControllerContainer
     * @throws InvalidOperationException
     */
    public function getControllerContainer()
    {
        if (null === $this->controllerContainer) {
            throw new InvalidOperationException('Controller container must not be null');
        }
        return $this->controllerContainer;
    }

    /**
     * @param $result
     * @throws ODataException
     * @return array|mixed
     */
    protected function createUpdateDeleteProcessOutput(JsonResponse $result)
    {
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
}
