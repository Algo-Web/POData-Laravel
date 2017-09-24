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
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use POData\Common\InvalidOperationException;
use POData\Common\ODataException;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Query\IQueryProvider;
use POData\Providers\Query\QueryResult;
use POData\Providers\Query\QueryType;
use POData\UriProcessor\QueryProcessor\ExpressionParser\FilterInfo;
use POData\UriProcessor\QueryProcessor\OrderByParser\InternalOrderByInfo;
use POData\UriProcessor\QueryProcessor\SkipTokenParser\SkipTokenInfo;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\KeyDescriptor;
use Symfony\Component\Process\Exception\InvalidArgumentException;

class LaravelQuery implements IQueryProvider
{
    protected $expression;
    protected $auth;
    protected $reader;
    public $queryProviderClassName;
    private $verbMap = [];
    protected $metadataProvider;
    protected $controllerContainer;

    public function __construct(AuthInterface $auth = null)
    {
        /* MySQLExpressionProvider();*/
        $this->expression = new LaravelExpressionProvider(); //PHPExpressionProvider('expression');
        $this->queryProviderClassName = get_class($this);
        $this->auth = isset($auth) ? $auth : new NullAuthProvider();
        $this->reader = new LaravelReadQuery($this->auth);
        $this->verbMap['create'] = ActionVerb::CREATE();
        $this->verbMap['update'] = ActionVerb::UPDATE();
        $this->verbMap['delete'] = ActionVerb::DELETE();
        $this->metadataProvider = new MetadataProvider(App::make('app'));
        $this->controllerContainer = App::make('metadataControllers');
    }

    /**
     * Indicates if the QueryProvider can handle ordered paging, this means respecting order, skip, and top parameters
     * If the query provider can not handle ordered paging, it must return the entire result set and POData will
     * perform the ordering and paging.
     *
     * @return Boolean True if the query provider can handle ordered paging, false if POData should perform the paging
     */
    public function handlesOrderedPaging()
    {
        return true;
    }

    /**
     * Gets the expression provider used by to compile OData expressions into expression used by this query provider.
     *
     * @return \POData\Providers\Expression\IExpressionProvider
     */
    public function getExpressionProvider()
    {
        return $this->expression;
    }

    /**
     * Gets the LaravelReadQuery instance used to handle read queries (repetitious, nyet?).
     *
     * @return LaravelReadQuery
     */
    public function getReader()
    {
        return $this->reader;
    }

    /**
     * Dig out local copy of POData-Laravel metadata provider.
     *
     * @return MetadataProvider
     */
    public function getMetadataProvider()
    {
        return $this->metadataProvider;
    }

    /**
     * Dig out local copy of controller metadata mapping.
     *
     * @return MetadataControllerContainer
     */
    public function getControllerContainer()
    {
        assert(null !== $this->controllerContainer, get_class($this->controllerContainer));
        return $this->controllerContainer;
    }

    /**
     * Gets collection of entities belongs to an entity set
     * IE: http://host/EntitySet
     *  http://host/EntitySet?$skip=10&$top=5&filter=Prop gt Value.
     *
     * @param QueryType                $queryType            Is this is a query for a count, entities,
     *                                                       or entities-with-count?
     * @param ResourceSet              $resourceSet          The entity set containing the entities to fetch
     * @param FilterInfo|null          $filterInfo           The $filter parameter of the OData query.  NULL if absent
     * @param null|InternalOrderByInfo $orderBy              sorted order if we want to get the data in some
     *                                                       specific order
     * @param int|null                 $top                  number of records which need to be retrieved
     * @param int|null                 $skip                 number of records which need to be skipped
     * @param SkipTokenInfo|null       $skipToken            value indicating what records to skip
     * @param Model|Relation|null      $sourceEntityInstance Starting point of query
     *
     * @return QueryResult
     */
    public function getResourceSet(
        QueryType $queryType,
        ResourceSet $resourceSet,
        $filterInfo = null,
        $orderBy = null,
        $top = null,
        $skip = null,
        $skipToken = null,
        $sourceEntityInstance = null
    ) {
        $source = $this->unpackSourceEntity($sourceEntityInstance);
        return $this->getReader()->getResourceSet(
            $queryType,
            $resourceSet,
            $filterInfo,
            $orderBy,
            $top,
            $skip,
            $skipToken,
            $source
        );
    }
    /**
     * Gets an entity instance from an entity set identified by a key
     * IE: http://host/EntitySet(1L)
     * http://host/EntitySet(KeyA=2L,KeyB='someValue').
     *
     * @param ResourceSet        $resourceSet   The entity set containing the entity to fetch
     * @param KeyDescriptor|null $keyDescriptor The key identifying the entity to fetch
     *
     * @return Model|null Returns entity instance if found else null
     */
    public function getResourceFromResourceSet(
        ResourceSet $resourceSet,
        KeyDescriptor $keyDescriptor = null
    ) {
        return $this->getReader()->getResourceFromResourceSet($resourceSet, $keyDescriptor);
    }

    /**
     * Get related resource set for a resource
     * IE: http://host/EntitySet(1L)/NavigationPropertyToCollection
     * http://host/EntitySet?$expand=NavigationPropertyToCollection.
     *
     * @param QueryType          $queryType            Is this is a query for a count, entities, or entities-with-count
     * @param ResourceSet        $sourceResourceSet    The entity set containing the source entity
     * @param object             $sourceEntityInstance The source entity instance
     * @param ResourceSet        $targetResourceSet    The resource set pointed to by the navigation property
     * @param ResourceProperty   $targetProperty       The navigation property to retrieve
     * @param FilterInfo|null    $filter               The $filter parameter of the OData query.  NULL if none specified
     * @param mixed|null         $orderBy              sorted order if we want to get the data in some specific order
     * @param int|null           $top                  number of records which need to be retrieved
     * @param int|null           $skip                 number of records which need to be skipped
     * @param SkipTokenInfo|null $skipToken            value indicating what records to skip
     *
     * @return QueryResult
     */
    public function getRelatedResourceSet(
        QueryType $queryType,
        ResourceSet $sourceResourceSet,
        $sourceEntityInstance,
        ResourceSet $targetResourceSet,
        ResourceProperty $targetProperty,
        FilterInfo $filter = null,
        $orderBy = null,
        $top = null,
        $skip = null,
        $skipToken = null
    ) {
        $source = $this->unpackSourceEntity($sourceEntityInstance);
        return $this->getReader()->getRelatedResourceSet(
            $queryType,
            $sourceResourceSet,
            $source,
            $targetResourceSet,
            $targetProperty,
            $filter,
            $orderBy,
            $top,
            $skip,
            $skipToken
        );
    }

    /**
     * Gets a related entity instance from an entity set identified by a key
     * IE: http://host/EntitySet(1L)/NavigationPropertyToCollection(33).
     *
     * @param ResourceSet      $sourceResourceSet    The entity set containing the source entity
     * @param object           $sourceEntityInstance the source entity instance
     * @param ResourceSet      $targetResourceSet    The entity set containing the entity to fetch
     * @param ResourceProperty $targetProperty       the metadata of the target property
     * @param KeyDescriptor    $keyDescriptor        The key identifying the entity to fetch
     *
     * @return Model|null Returns entity instance if found else null
     */
    public function getResourceFromRelatedResourceSet(
        ResourceSet $sourceResourceSet,
        $sourceEntityInstance,
        ResourceSet $targetResourceSet,
        ResourceProperty $targetProperty,
        KeyDescriptor $keyDescriptor
    ) {
        $source = $this->unpackSourceEntity($sourceEntityInstance);
        return $this->getReader()->getResourceFromRelatedResourceSet(
            $sourceResourceSet,
            $source,
            $targetResourceSet,
            $targetProperty,
            $keyDescriptor
        );
    }

    /**
     * Get related resource for a resource
     * IE: http://host/EntitySet(1L)/NavigationPropertyToSingleEntity
     * http://host/EntitySet?$expand=NavigationPropertyToSingleEntity.
     *
     * @param ResourceSet      $sourceResourceSet    The entity set containing the source entity
     * @param object           $sourceEntityInstance the source entity instance
     * @param ResourceSet      $targetResourceSet    The entity set containing the entity pointed to by the nav property
     * @param ResourceProperty $targetProperty       The navigation property to fetch
     *
     * @return object|null The related resource if found else null
     */
    public function getRelatedResourceReference(
        ResourceSet $sourceResourceSet,
        $sourceEntityInstance,
        ResourceSet $targetResourceSet,
        ResourceProperty $targetProperty
    ) {
        $source = $this->unpackSourceEntity($sourceEntityInstance);

        $result = $this->getReader()->getRelatedResourceReference(
            $sourceResourceSet,
            $source,
            $targetResourceSet,
            $targetProperty
        );
        return $result;
    }

    /**
     * Updates a resource.
     *
     * @param ResourceSet   $sourceResourceSet    The entity set containing the source entity
     * @param object        $sourceEntityInstance The source entity instance
     * @param KeyDescriptor $keyDescriptor        The key identifying the entity to fetch
     * @param object        $data                 the New data for the entity instance
     * @param bool          $shouldUpdate         Should undefined values be updated or reset to default
     *
     * @return object|null the new resource value if it is assignable or throw exception for null
     */
    public function updateResource(
        ResourceSet $sourceResourceSet,
        $sourceEntityInstance,
        KeyDescriptor $keyDescriptor,
        $data,
        $shouldUpdate = false
    ) {
        $source = $this->unpackSourceEntity($sourceEntityInstance);

        $verb = 'update';
        return $this->createUpdateCoreWrapper($sourceResourceSet, $data, $verb, $source);
    }
    /**
     * Delete resource from a resource set.
     *
     * @param ResourceSet $sourceResourceSet
     * @param object      $sourceEntityInstance
     *
     * return bool true if resources sucessfully deteled, otherwise false
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
     * @param ResourceSet $resourceSet          The entity set containing the entity to fetch
     * @param object      $sourceEntityInstance The source entity instance
     * @param object      $data                 the New data for the entity instance
     *
     * @returns object|null                     returns the newly created model if successful,
     *                                          or null if model creation failed.
     */
    public function createResourceforResourceSet(
        ResourceSet $resourceSet,
        $sourceEntityInstance,
        $data
    ) {
        $source = $this->unpackSourceEntity($sourceEntityInstance);

        $verb = 'create';
        return $this->createUpdateCoreWrapper($resourceSet, $data, $verb, $source);
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
    private function createUpdateDeleteCore($sourceEntityInstance, $data, $class, $verb)
    {
        $raw = App::make('metadataControllers');
        $map = $raw->getMetadata();

        if (!array_key_exists($class, $map)) {
            throw new \POData\Common\InvalidOperationException('Controller mapping missing for class '.$class.'.');
        }
        $goal = $raw->getMapping($class, $verb);
        if (null == $goal) {
            throw new \POData\Common\InvalidOperationException(
                'Controller mapping missing for '.$verb.' verb on class '.$class.'.'
            );
        }

        assert(null !== $data, 'Data must not be null');
        if (is_object($data)) {
            $arrayData = (array) $data;
        } else {
            $arrayData = $data;
        }
        if (!is_array($arrayData)) {
            throw \POData\Common\ODataException::createPreConditionFailedError(
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
     * @param ResourceSet $sourceResourceSet
     * @param $data
     * @param                            $verb
     * @param  Model|null                $source
     * @throws InvalidOperationException
     * @throws ODataException
     * @return mixed
     */
    private function createUpdateCoreWrapper(ResourceSet $sourceResourceSet, $data, $verb, Model $source = null)
    {
        $lastWord = 'update' == $verb ? 'updated' : 'created';
        $class = $sourceResourceSet->getResourceType()->getInstanceType()->getName();
        if (!$this->auth->canAuth($this->verbMap[$verb], $class, $source)) {
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
        throw new ODataException('Target model not successfully '.$lastWord, 422);
    }

    /**
     * @param $data
     * @param $paramList
     * @param  Model|null $sourceEntityInstance
     * @return array
     */
    private function createUpdateDeleteProcessInput($data, $paramList, Model $sourceEntityInstance = null)
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
    private function createUpdateDeleteProcessOutput($result)
    {
        if (!($result instanceof \Illuminate\Http\JsonResponse)) {
            throw ODataException::createInternalServerError('Controller response not well-formed json.');
        }
        $outData = $result->getData();
        if (is_object($outData)) {
            $outData = (array)$outData;
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
     * @param $sourceEntityInstance
     * @return mixed|null|\object[]
     */
    private function unpackSourceEntity($sourceEntityInstance)
    {
        if ($sourceEntityInstance instanceof QueryResult) {
            $source = $sourceEntityInstance->results;
            $source = (is_array($source)) ? $source[0] : $source;
            return $source;
        }
        return $sourceEntityInstance;
    }

    /**
     * Create multiple new resources in a resource set.
     *
     * @param ResourceSet $sourceResourceSet The entity set containing the entity to fetch
     * @param object[]    $data              The new data for the entity instance
     *
     * @return object[] returns the newly created model if successful, or throws an exception if model creation failed
     * @throw  \Exception
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
                    $raw = $this->createResourceforResourceSet($sourceResourceSet, null, $newItem);
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
     * @throw  \Exception
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
                    $raw = $this->updateResource($sourceResourceSet, $sourceEntityInstance, $newKey, $newItem);
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
     * Attaches child model to parent model.
     *
     * @param ResourceSet $sourceResourceSet
     * @param object      $sourceEntityInstance
     * @param ResourceSet $targetResourceSet
     * @param object      $targetEntityInstance
     * @param $navPropName
     *
     * @return bool
     */
    public function hookSingleModel(
        ResourceSet $sourceResourceSet,
        $sourceEntityInstance,
        ResourceSet $targetResourceSet,
        $targetEntityInstance,
        $navPropName
    ) {
        $relation = $this->isModelHookInputsOk($sourceEntityInstance, $targetEntityInstance, $navPropName);
        assert(
            $sourceEntityInstance instanceof Model && $targetEntityInstance instanceof Model,
            'Both input entities must be Eloquent models'
        );
        // in case the fake 'PrimaryKey' attribute got set inbound for a polymorphic-affected model, flatten it now
        unset($targetEntityInstance->PrimaryKey);

        if ($relation instanceof BelongsTo) {
            $relation->associate($targetEntityInstance);
        } elseif ($relation instanceof BelongsToMany) {
            $relation->attach($targetEntityInstance);
        } elseif ($relation instanceof HasOneOrMany) {
            $relation->save($targetEntityInstance);
        }
        return true;
    }

    /**
     * Removes child model from parent model.
     *
     * @param ResourceSet $sourceResourceSet
     * @param object      $sourceEntityInstance
     * @param ResourceSet $targetResourceSet
     * @param object      $targetEntityInstance
     * @param $navPropName
     *
     * @return bool
     */
    public function unhookSingleModel(
        ResourceSet $sourceResourceSet,
        $sourceEntityInstance,
        ResourceSet $targetResourceSet,
        $targetEntityInstance,
        $navPropName
    ) {
        $relation = $this->isModelHookInputsOk($sourceEntityInstance, $targetEntityInstance, $navPropName);
        assert(
            $sourceEntityInstance instanceof Model && $targetEntityInstance instanceof Model,
            'Both input entities must be Eloquent models'
        );
        // in case the fake 'PrimaryKey' attribute got set inbound for a polymorphic-affected model, flatten it now
        unset($targetEntityInstance->PrimaryKey);

        if ($relation instanceof BelongsTo) {
            $relation->dissociate();
        } elseif ($relation instanceof BelongsToMany) {
            $relation->detach($targetEntityInstance);
        } elseif ($relation instanceof HasOneOrMany) {
            // dig up inverse property name, so we can pass it to unhookSingleModel with source and target elements
            // swapped
            $otherPropName = $this->getMetadataProvider()
                ->resolveReverseProperty($sourceEntityInstance, $targetEntityInstance, $navPropName);
            if (null === $otherPropName) {
                $msg = 'Bad navigation property, '.$navPropName.', on source model '.get_class($sourceEntityInstance);
                throw new \InvalidArgumentException($msg);
            }
            $this->unhookSingleModel(
                $targetResourceSet,
                $targetEntityInstance,
                $sourceResourceSet,
                $sourceEntityInstance,
                $otherPropName
            );
        }
        return true;
    }

    /**
     * @param $sourceEntityInstance
     * @param $targetEntityInstance
     * @param $navPropName
     * @throws \InvalidArgumentException
     * @return Relation
     */
    protected function isModelHookInputsOk($sourceEntityInstance, $targetEntityInstance, $navPropName)
    {
        if (!$sourceEntityInstance instanceof Model || !$targetEntityInstance instanceof Model) {
            $msg = 'Both source and target must be Eloquent models';
            throw new \InvalidArgumentException($msg);
        }
        $relation = $sourceEntityInstance->$navPropName();
        if (!$relation instanceof Relation) {
            $msg = 'Navigation property must be an Eloquent relation';
            throw new \InvalidArgumentException($msg);
        }
        $targType = $relation->getRelated();
        if (!$targetEntityInstance instanceof $targType) {
            $msg = 'Target instance must be of type compatible with relation declared in method '.$navPropName;
            throw new \InvalidArgumentException($msg);
        }
        return $relation;
    }

    /**
     * @param ResourceSet $sourceResourceSet
     * @param $verbName
     * @return array|null
     */
    protected function getOptionalVerbMapping(ResourceSet $sourceResourceSet, $verbName)
    {
        // dig up target class name
        $type = $sourceResourceSet->getResourceType()->getInstanceType();
        assert($type instanceof \ReflectionClass, get_class($type));
        $modelName = $type->getName();
        return $this->getControllerContainer()->getMapping($modelName, $verbName);
    }

    /**
     * Prepare bulk request from supplied data.  If $keyDescriptors is not null, its elements are assumed to
     * correspond 1-1 to those in $data.
     *
     * @param $paramList
     * @param array                $data
     * @param KeyDescriptor[]|null $keyDescriptors
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
                    $bulkData = [ 'data' => $data];
                    if (null !== $keyDescriptors) {
                        $keys = [];
                        foreach ($keyDescriptors as $desc) {
                            assert($desc instanceof KeyDescriptor, get_class($desc));
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
     * @param array       $data
     * @param $mapping
     * @param $pastVerb
     * @param  KeyDescriptor[]|null $keyDescriptor
     * @throws ODataException
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
                $result = $class::findMany($payload['id'])->toArray()[0];
                return $result;
            } catch (\Exception $e) {
                throw new ODataException($e->getMessage(), 500);
            }
        } else {
            throw new ODataException('Target models not successfully ' . $pastVerb, 422);
        }
    }

    /**
     * Start database transaction.
     */
    public function startTransaction()
    {
        DB::beginTransaction();
    }

    /**
     * Commit database transaction.
     */
    public function commitTransaction()
    {
        DB::commit();
    }

    /**
     * Abort database transaction.
     */
    public function rollBackTransaction()
    {
        DB::rollBack();
    }
}
