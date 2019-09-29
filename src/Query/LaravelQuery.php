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
    protected $writer;
    public $queryProviderClassName;
    private static $touchList = [];
    private static $inBatch;

    public function __construct(AuthInterface $auth = null)
    {
        parent::__construct($auth);
        /* MySQLExpressionProvider();*/
        $this->expression = new LaravelExpressionProvider(); //PHPExpressionProvider('expression');
        $this->queryProviderClassName = get_class($this);
        $this->reader = new LaravelReadQuery($this->getAuth());
        $this->modelHook = new LaravelHookQuery($this->getAuth());
        $this->bulk = new LaravelBulkQuery($this, $this->getAuth());
        $this->writer = new LaravelWriteQuery($this->getAuth());

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
        return $this->getWriter()->updateResource(
            $sourceResourceSet,
            $sourceEntityInstance,
            $keyDescriptor,
            $data,
            $shouldUpdate
        );
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
        return $this->getWriter()->deleteResource($sourceResourceSet, $sourceEntityInstance);
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
        return $this->getWriter()->createResourceforResourceSet($resourceSet, $sourceEntityInstance, $data);
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
        return $this->getWriter()->putResource($resourceSet, $keyDescriptor, $data);
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
     * @return LaravelWriteQuery
     */
    public function getWriter()
    {
        return $this->writer;
    }
}
