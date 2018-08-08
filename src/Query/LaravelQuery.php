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
    protected $modelHook;
    protected $bulk;
    public $queryProviderClassName;
    private $verbMap = [];
    protected $metadataProvider;
    protected $controllerContainer;
    private static $touchList = [];
    private static $inBatch;

    /**
     * @param AuthInterface|Mockery_56_AlgoWeb_PODataLaravel_Interfaces_AuthInterface $auth
     */
    public function __construct(AuthInterface $auth = null)
    {
        /* MySQLExpressionProvider();*/
        $this->expression = new LaravelExpressionProvider(); //PHPExpressionProvider('expression');
        $this->queryProviderClassName = get_class($this);
        $this->auth = isset($auth) ? $auth : new NullAuthProvider();
        $this->reader = new LaravelReadQuery($this->auth);
        $this->modelHook = new LaravelHookQuery($this->auth);
        $this->bulk = new LaravelBulkQuery($this, $this->auth);
        $this->metadataProvider = new MetadataProvider(App::make('app'));
        $this->controllerContainer = App::make('metadataControllers');
        self::$touchList = [];
        self::$inBatch = false;
    }

    /**
     * @return bool
     */
    public function handlesOrderedPaging()
    {
        return true;
    }

    /**
     * @return AlgoWeb\PODataLaravel\Query\LaravelExpressionProvider
     */
    public function getExpressionProvider()
    {
        return $this->expression;
    }

    /**
     * @return AlgoWeb\PODataLaravel\Query\LaravelReadQuery
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
     * @return AlgoWeb\PODataLaravel\Providers\MetadataProvider
     */
    public function getMetadataProvider()
    {
        return $this->metadataProvider;
    }

    /**
     * @return AlgoWeb\PODataLaravel\Controllers\MetadataControllerContainer
     */
    public function getControllerContainer()
    {
        if (null === $this->controllerContainer) {
            throw new InvalidOperationException('Controller container must not be null');
        }
        return $this->controllerContainer;
    }

    /**
     * @return AlgoWeb\PODataLaravel\Enums\ActionVerb[]
     */
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
     * @param Mockery_66_POData_Providers_Query_QueryType|QueryType        $queryType
     * @param Mockery_40_POData_Providers_Metadata_ResourceSet|ResourceSet $resourceSet
     * @param DateTime|null                                                $filterInfo
     * @param null                                                         $orderBy
     * @param null                                                         $top
     * @param null                                                         $skip
     * @param null                                                         $skipToken
     * @param null                                                         $eagerLoad
     * @param AlgoWeb\PODataLaravel\Models\TestModel|DateTime|string       $sourceEntityInstance
     *
     * @return void
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
     * @param Mockery_40_POData_Providers_Metadata_ResourceSet|ResourceSet                                   $resourceSet
     * @param KeyDescriptor|Mockery_55_POData_UriProcessor_ResourcePathProcessor_SegmentParser_KeyDescriptor $keyDescriptor
     * @param mixed                                                                                          $eagerLoad
     *
     * @return null
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
     * @param Mockery_40_POData_Providers_Metadata_ResourceSet|ResourceSet                                   $sourceResourceSet
     * @param Mockery_68_AlgoWeb_PODataLaravel_Models_TestMorphManySource                                    $sourceEntityInstance
     * @param Mockery_40_POData_Providers_Metadata_ResourceSet|ResourceSet                                   $targetResourceSet
     * @param Mockery_53_POData_Providers_Metadata_ResourceProperty|ResourceProperty                         $targetProperty
     * @param KeyDescriptor|Mockery_55_POData_UriProcessor_ResourcePathProcessor_SegmentParser_KeyDescriptor $keyDescriptor
     *
     * @return Mockery_77_AlgoWeb_PODataLaravel_Models_TestMorphTarget|void
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
     * @param Mockery_40_POData_Providers_Metadata_ResourceSet|ResourceSet                                                  $sourceResourceSet
     * @param Mockery_21_AlgoWeb_PODataLaravel_Models_TestModel|Mockery_68_AlgoWeb_PODataLaravel_Models_TestMorphManySource $sourceEntityInstance
     * @param Mockery_40_POData_Providers_Metadata_ResourceSet|ResourceSet                                                  $targetResourceSet
     * @param Mockery_53_POData_Providers_Metadata_ResourceProperty|ResourceProperty                                        $targetProperty
     *
     * @return AlgoWeb\PODataLaravel\Models\TestModel|null
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
     * @param Mockery_40_POData_Providers_Metadata_ResourceSet|ResourceSet                                   $sourceResourceSet
     * @param AlgoWeb\PODataLaravel\Models\TestModel                                                         $sourceEntityInstance
     * @param KeyDescriptor|Mockery_55_POData_UriProcessor_ResourcePathProcessor_SegmentParser_KeyDescriptor $keyDescriptor
     * @param stdClass                                                                                       $data
     * @param mixed                                                                                          $shouldUpdate
     *
     * @return null
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
     * @param Mockery_40_POData_Providers_Metadata_ResourceSet|ResourceSet $sourceResourceSet
     * @param AlgoWeb\PODataLaravel\Models\TestModel|DateTime              $sourceEntityInstance
     *
     * @return bool|null
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
     * @param Mockery_40_POData_Providers_Metadata_ResourceSet|ResourceSet                         $resourceSet
     * @param AlgoWeb\PODataLaravel\Models\TestModel|Mockery_54_Illuminate_Database_Eloquent_Model $sourceEntityInstance
     * @param stdClass|string|null                                                                 $data
     *
     * @return null
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
     * @param AlgoWeb\PODataLaravel\Models\TestModel $sourceEntityInstance
     * @param null[]|stdClass|string                 $data
     * @param string                                 $class
     * @param string                                 $verb
     *
     * @return array[]|null[]|null
     */
    private function createUpdateDeleteCore($sourceEntityInstance, $data, $class, $verb)
    {
        $raw = App::make('metadataControllers');
        $map = $raw->getMetadata();

        if (!array_key_exists($class, $map)) {
            throw new \POData\Common\InvalidOperationException('Controller mapping missing for class ' . $class . '.');
        }
        $goal = $raw->getMapping($class, $verb);
        if (null == $goal) {
            throw new \POData\Common\InvalidOperationException(
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
     * @param Mockery_40_POData_Providers_Metadata_ResourceSet|ResourceSet                                   $resourceSet
     * @param KeyDescriptor|Mockery_55_POData_UriProcessor_ResourcePathProcessor_SegmentParser_KeyDescriptor $keyDescriptor
     * @param array                                                                                          $data
     *
     * @return bool
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
     * @param Mockery_40_POData_Providers_Metadata_ResourceSet|ResourceSet                               $sourceResourceSet
     * @param stdClass|string|null                                                                       $data
     * @param string                                                                                     $verb
     * @param AlgoWeb\PODataLaravel\Models\TestModel|Mockery_54_Illuminate_Database_Eloquent_Model|Model $source
     *
     * @return null
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
     * @param false[]|null[]                               $data
     * @param array[]                                      $paramList
     * @param AlgoWeb\PODataLaravel\Models\TestModel|Model $sourceEntityInstance
     *
     * @return AlgoWeb\PODataLaravel\Requests\TestRequest[]|int[]|null[]
     */
    protected function createUpdateDeleteProcessInput($data, $paramList, Model $sourceEntityInstance = null)
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
     * @param Illuminate\Http\JsonResponse|Mockery_64_Illuminate_Http_JsonResponse|null $result
     *
     * @return array[]|null[]|null
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
     * @param AlgoWeb\PODataLaravel\Models\TestModel|DateTime|Mockery_21_AlgoWeb_PODataLaravel_Models_TestModel|Mockery_54_Illuminate_Database_Eloquent_Model|Mockery_68_AlgoWeb_PODataLaravel_Models_TestMorphManySource|string|null $sourceEntityInstance
     *
     * @return AlgoWeb\PODataLaravel\Models\TestModel|DateTime|Mockery_21_AlgoWeb_PODataLaravel_Models_TestModel|Mockery_54_Illuminate_Database_Eloquent_Model|Mockery_68_AlgoWeb_PODataLaravel_Models_TestMorphManySource|string|null
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
        return $this->getBulk()->createBulkResourceForResourceSet($sourceResourceSet, $data);
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
        return $this->getModelHook()->unhookSingleModel(
            $sourceResourceSet,
            $sourceEntityInstance,
            $targetResourceSet,
            $targetEntityInstance,
            $navPropName
        );
    }

    /**
     * @param bool $isBulk
     *
     * @return void
     */
    public function startTransaction($isBulk = false)
    {
        self::$touchList = [];
        self::$inBatch = true === $isBulk;
        DB::beginTransaction();
    }

    /**
     * @return void
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
     * @return void
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
     * @param Mockery_40_POData_Providers_Metadata_ResourceSet|ResourceSet                         $resourceSet
     * @param AlgoWeb\PODataLaravel\Models\TestModel|Mockery_54_Illuminate_Database_Eloquent_Model $sourceEntityInstance
     * @param stdClass|string|null                                                                 $data
     * @param string                                                                               $verb
     *
     * @return null
     */
    protected function createUpdateMainWrapper(ResourceSet $resourceSet, $sourceEntityInstance, $data, $verb)
    {
        $source = $this->unpackSourceEntity($sourceEntityInstance);

        $result = $this->createUpdateCoreWrapper($resourceSet, $data, $verb, $source);
        self::queueModel($result);
        return $result;
    }
}
