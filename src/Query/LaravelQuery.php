<?php

namespace AlgoWeb\PODataLaravel\Query;

use AlgoWeb\PODataLaravel\Auth\NullAuthProvider;
use AlgoWeb\PODataLaravel\Controllers\MetadataControllerContainer;
use AlgoWeb\PODataLaravel\Enums\ActionVerb;
use AlgoWeb\PODataLaravel\Interfaces\AuthInterface;
use AlgoWeb\PODataLaravel\Providers\MetadataProvider;
use Illuminate\Database\Eloquent\Model;
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

class LaravelQuery extends LaravelBaseQuery implements IQueryProvider
{
    protected $expression;
    protected $reader;
    protected $modelHook;
    protected $bulk;
    public $queryProviderClassName;
    private $verbMap = [];
    protected $metadataProvider;
    protected $controllerContainer;
    private static $touchList = [];
    private static $inBatch;

    public function __construct(AuthInterface $auth = null)
    {
        parent::__construct($auth);
        /* MySQLExpressionProvider();*/
        $this->expression = new LaravelExpressionProvider(); //PHPExpressionProvider('expression');
        $this->queryProviderClassName = get_class($this);
        $this->reader = new LaravelReadQuery($this->auth);
        $this->modelHook = new LaravelHookQuery($this->auth);
        $this->bulk = new LaravelBulkQuery($this, $this->auth);
        $this->metadataProvider = new MetadataProvider(App::make('app'));
        $this->controllerContainer = App::make('metadataControllers');
        self::$touchList = [];
        self::$inBatch = false;
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
     * Gets the LaravelHookQuery instance used to handle hook/unhook queries (repetitious, nyet?).
     *
     * @return LaravelHookQuery
     */
    public function getModelHook()
    {
        return $this->modelHook;
    }

    /**
     * Gets the LaravelBulkQuery instance used to handle bulk queries (repetitious, nyet?).
     *
     * @return LaravelBulkQuery
     */
    public function getBulk()
    {
        return $this->bulk;
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
     * @throws InvalidOperationException
     */
    public function getControllerContainer()
    {
        if (null === $this->controllerContainer) {
            throw new InvalidOperationException('Controller container must not be null');
        }
        return $this->controllerContainer;
    }

    public function getVerbMap()
    {
        if (0 == count($this->verbMap)) {
            $this->verbMap['create'] = ActionVerb::CREATE();
            $this->verbMap['update'] = ActionVerb::UPDATE();
            $this->verbMap['delete'] = ActionVerb::DELETE();
        }
        return $this->verbMap;
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
     * @param string[]|null            $eagerLoad            array of relations to eager load
     * @param Model|Relation|null      $sourceEntityInstance Starting point of query
     *
     * @return QueryResult
     * @throws InvalidOperationException
     * @throws ODataException
     * @throws \ReflectionException
     */
    public function getResourceSet(
        QueryType $queryType,
        ResourceSet $resourceSet,
        $filterInfo = null,
        $orderBy = null,
        $top = null,
        $skip = null,
        $skipToken = null,
        array $eagerLoad = null,
        $sourceEntityInstance = null
    ) {
        /** @var Model|Relation|null $source */
        $source = $this->unpackSourceEntity($sourceEntityInstance);
        return $this->getReader()->getResourceSet(
            $queryType,
            $resourceSet,
            $filterInfo,
            $orderBy,
            $top,
            $skip,
            $skipToken,
            $eagerLoad,
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
     * @param string[]|null      $eagerLoad     array of relations to eager load
     *
     * @return Model|null Returns entity instance if found else null
     * @throws \Exception
     */
    public function getResourceFromResourceSet(
        ResourceSet $resourceSet,
        KeyDescriptor $keyDescriptor = null,
        array $eagerLoad = null
    ) {
        return $this->getReader()->getResourceFromResourceSet($resourceSet, $keyDescriptor, $eagerLoad);
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
     * @throws \Exception
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
     * @throws \Exception
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
     * @return Model|null The related resource if found else null
     * @throws \Exception
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
     * @throws \Exception
     * @return Model|null
     */
    protected function createUpdateCoreWrapper(ResourceSet $sourceResourceSet, $data, $verb, Model $source = null)
    {
        $lastWord = 'update' == $verb ? 'updated' : 'created';
        $class = $sourceResourceSet->getResourceType()->getInstanceType()->getName();
        if (!$this->auth->canAuth($this->getVerbMap()[$verb], $class, $source)) {
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
    private function createUpdateDeleteProcessOutput($result)
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
     * @throws InvalidOperationException
     * @throws \ReflectionException
     * @throw  \Exception
     */
    public function createBulkResourceforResourceSet(
        ResourceSet $sourceResourceSet,
        array $data
    ) {
        return $this->getBulk()->createBulkResourceForResourceSet($sourceResourceSet, $data);
    }

    /**
     * Updates a group of resources in a resource set.
     *
     * @param ResourceSet     $sourceResourceSet    The entity set containing the source entity
     * @param Model|Relation  $sourceEntityInstance The source entity instance
     * @param KeyDescriptor[] $keyDescriptor        The key identifying the entity to fetch
     * @param object[]        $data                 The new data for the entity instances
     * @param bool            $shouldUpdate         Should undefined values be updated or reset to default
     *
     * @return object[] the new resource value if it is assignable, or throw exception for null
     * @throw  \Exception
     * @throws InvalidOperationException
     */
    public function updateBulkResource(
        ResourceSet $sourceResourceSet,
        $sourceEntityInstance,
        array $keyDescriptor,
        array $data,
        $shouldUpdate = false
    ) {
        return $this->getBulk()
            ->updateBulkResource(
                $sourceResourceSet,
                $sourceEntityInstance,
                $keyDescriptor,
                $data,
                $shouldUpdate
            );
    }

    /**
     * Attaches child model to parent model.
     *
     * @param ResourceSet $sourceResourceSet
     * @param Model       $sourceEntityInstance
     * @param ResourceSet $targetResourceSet
     * @param Model       $targetEntityInstance
     * @param $navPropName
     *
     * @return bool
     * @throws InvalidOperationException
     */
    public function hookSingleModel(
        ResourceSet $sourceResourceSet,
        $sourceEntityInstance,
        ResourceSet $targetResourceSet,
        $targetEntityInstance,
        $navPropName
    ) {
        return $this->getModelHook()->hookSingleModel(
            $sourceResourceSet,
            $sourceEntityInstance,
            $targetResourceSet,
            $targetEntityInstance,
            $navPropName
        );
    }

    /**
     * Removes child model from parent model.
     *
     * @param ResourceSet $sourceResourceSet
     * @param Model       $sourceEntityInstance
     * @param ResourceSet $targetResourceSet
     * @param Model       $targetEntityInstance
     * @param $navPropName
     *
     * @return bool
     * @throws InvalidOperationException
     */
    public function unhookSingleModel(
        ResourceSet $sourceResourceSet,
        $sourceEntityInstance,
        ResourceSet $targetResourceSet,
        $targetEntityInstance,
        $navPropName
    ) {
        return $this->getModelHook()->unhookSingleModel(
            $sourceResourceSet,
            $sourceEntityInstance,
            $targetResourceSet,
            $targetEntityInstance,
            $navPropName
        );
    }

    /**
     * Start database transaction.
     * @param bool $isBulk
     */
    public function startTransaction($isBulk = false)
    {
        self::$touchList = [];
        self::$inBatch = true === $isBulk;
        DB::beginTransaction();
    }

    /**
     * Commit database transaction.
     */
    public function commitTransaction()
    {
        // fire model save again, to give Laravel app final chance to finalise anything that needs finalising after
        // batch processing
        foreach (self::$touchList as $model) {
            $model->save();
        }

        DB::commit();
        self::$touchList = [];
        self::$inBatch = false;
    }

    /**
     * Abort database transaction.
     */
    public function rollBackTransaction()
    {
        DB::rollBack();
        self::$touchList = [];
        self::$inBatch = false;
    }

    public static function queueModel(Model &$model)
    {
        // if we're not processing a batch, don't queue anything
        if (!self::$inBatch) {
            return;
        }
        // if we are in a batch, add to queue to process on transaction commit
        self::$touchList[] = $model;
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
            self::queueModel($result);
        }
        return $result;
    }
}
