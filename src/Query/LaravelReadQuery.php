<?php

declare(strict_types=1);

namespace AlgoWeb\PODataLaravel\Query;

use AlgoWeb\PODataLaravel\Enums\ActionVerb;
use AlgoWeb\PODataLaravel\Models\MetadataTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\App;
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

class LaravelReadQuery extends LaravelBaseQuery
{
    use LaravelReadQueryUtilityTrait;

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
     * @throws InvalidArgumentException
     * @throws InvalidOperationException
     * @throws \ReflectionException
     * @throws ODataException
     * @return QueryResult
     */
    public function getResourceSet(
        QueryType $queryType,
        ResourceSet $resourceSet,
        FilterInfo $filterInfo = null,
        $orderBy = null,
        $top = null,
        $skip = null,
        SkipTokenInfo $skipToken = null,
        array $eagerLoad = null,
        $sourceEntityInstance = null
    ) {
        $rawLoad = $this->processEagerLoadList($eagerLoad);

        $sourceEntityInstance = $this->checkSourceInstance($sourceEntityInstance, $resourceSet);

        /** @var MetadataTrait $model */
        $model     = $sourceEntityInstance instanceof Model ? $sourceEntityInstance : $sourceEntityInstance->getRelated();
        $modelLoad = $model->getEagerLoad();
        $tableName = $model->getTable();
        $rawLoad   = array_unique(array_merge($rawLoad, $modelLoad));

        $checkInstance = $sourceEntityInstance instanceof Model ? $sourceEntityInstance : null;
        $this->checkAuth($sourceEntityInstance, $checkInstance);

        $result          = new QueryResult();
        $result->results = null;
        $result->count   = null;

        $sourceEntityInstance = $this->buildOrderBy($sourceEntityInstance, $tableName, $orderBy);

        // throttle up for trench run
        if (null != $skipToken) {
            $sourceEntityInstance = $this->processSkipToken($skipToken, $sourceEntityInstance);
        }

        if (!isset($skip)) {
            $skip = 0;
        }
        if (!isset($top)) {
            $top = PHP_INT_MAX;
        }

        $nullFilter = !isset($filterInfo);
        $isvalid    = null;
        if (isset($filterInfo)) {
            $method  = 'return ' . $filterInfo->getExpressionAsString() . ';';
            $clln    = $resourceSet->getResourceType()->getName();
            $isvalid = function ($inputD) use ($clln, $method) {
                ${$clln} = $inputD;
                return eval($method);
            };
        }

        list($bulkSetCount, $resultSet, $resultCount, $skip) = $this->applyFiltering(
            $sourceEntityInstance,
            $nullFilter,
            $rawLoad,
            $top,
            $skip,
            $isvalid
        );

        if (isset($top)) {
            $resultSet = $resultSet->take($top);
        }

        $this->packageResourceSetResults($queryType, $skip, $result, $resultSet, $resultCount, $bulkSetCount);
        return $result;
    }

    /**
     * Get related resource set for a resource
     * IE: http://host/EntitySet(1L)/NavigationPropertyToCollection
     * http://host/EntitySet?$expand=NavigationPropertyToCollection.
     *
     * @param QueryType          $queryType            Is this is a query for a count, entities, or entities-with-count
     * @param ResourceSet        $sourceResourceSet    The entity set containing the source entity
     * @param Model              $sourceEntityInstance The source entity instance
     * @param ResourceSet        $targetResourceSet    The resource set pointed to by the navigation property
     * @param ResourceProperty   $targetProperty       The navigation property to retrieve
     * @param FilterInfo|null    $filter               The $filter parameter of the OData query.  NULL if none specified
     * @param mixed|null         $orderBy              sorted order if we want to get the data in some specific order
     * @param int|null           $top                  number of records which need to be retrieved
     * @param int|null           $skip                 number of records which need to be skipped
     * @param SkipTokenInfo|null $skipToken            value indicating what records to skip
     *
     * @throws InvalidOperationException
     * @throws ODataException
     * @throws \ReflectionException
     * @return QueryResult
     */
    public function getRelatedResourceSet(
        QueryType $queryType,
        ResourceSet $sourceResourceSet,
        Model $sourceEntityInstance,
        /* @noinspection PhpUnusedParameterInspection */
        ResourceSet $targetResourceSet,
        ResourceProperty $targetProperty,
        FilterInfo $filter = null,
        $orderBy = null,
        $top = null,
        $skip = null,
        SkipTokenInfo $skipToken = null
    ) {
        $this->checkAuth($sourceEntityInstance);

        $propertyName = $targetProperty->getName();
        /** @var Relation $results */
        $results = $sourceEntityInstance->{$propertyName}();

        return $this->getResourceSet(
            $queryType,
            $sourceResourceSet,
            $filter,
            $orderBy,
            $top,
            $skip,
            $skipToken,
            null,
            $results
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
     * @throws \Exception;
     * @return Model|null  Returns entity instance if found else null
     */
    public function getResourceFromResourceSet(
        ResourceSet $resourceSet,
        KeyDescriptor $keyDescriptor = null,
        array $eagerLoad = null
    ) {
        return $this->getResource($resourceSet, $keyDescriptor, [], $eagerLoad);
    }


    /**
     * Common method for getResourceFromRelatedResourceSet() and getResourceFromResourceSet().
     *
     * @param ResourceSet|null    $resourceSet
     * @param KeyDescriptor|null  $keyDescriptor
     * @param Model|Relation|null $sourceEntityInstance Starting point of query
     * @param array               $whereCondition
     * @param string[]|null       $eagerLoad            array of relations to eager load
     *
     * @throws \Exception
     * @return Model|null
     */
    public function getResource(
        ResourceSet $resourceSet = null,
        KeyDescriptor $keyDescriptor = null,
        array $whereCondition = [],
        array $eagerLoad = null,
        $sourceEntityInstance = null
    ) {
        if (null == $resourceSet && null == $sourceEntityInstance) {
            $msg = 'Must supply at least one of a resource set and source entity.';
            throw new \Exception($msg);
        }

        $sourceEntityInstance = $this->checkSourceInstance($sourceEntityInstance, $resourceSet);

        $this->checkAuth($sourceEntityInstance);
        $modelLoad = null;
        if ($sourceEntityInstance instanceof Model) {
            $modelLoad = $sourceEntityInstance->getEagerLoad();
        } elseif ($sourceEntityInstance instanceof Relation) {
            /** @var MetadataTrait $model */
            $model     = $sourceEntityInstance->getRelated();
            $modelLoad = $model->getEagerLoad();
        }
        if (!(isset($modelLoad))) {
            throw new InvalidOperationException('');
        }

        $this->processKeyDescriptor($sourceEntityInstance, $keyDescriptor);
        foreach ($whereCondition as $fieldName => $fieldValue) {
            $sourceEntityInstance = $sourceEntityInstance->where($fieldName, $fieldValue);
        }

        $sourceEntityInstance = $sourceEntityInstance->get();
        return $sourceEntityInstance->first();
    }

    /**
     * Get related resource for a resource
     * IE: http://host/EntitySet(1L)/NavigationPropertyToSingleEntity
     * http://host/EntitySet?$expand=NavigationPropertyToSingleEntity.
     *
     * @param ResourceSet      $sourceResourceSet    The entity set containing the source entity
     * @param Model            $sourceEntityInstance the source entity instance
     * @param ResourceSet      $targetResourceSet    The entity set containing the entity pointed to by the nav property
     * @param ResourceProperty $targetProperty       The navigation property to fetch
     *
     * @throws ODataException
     * @throws InvalidOperationException
     * @throws \ReflectionException
     * @return Model|null                The related resource if found else null
     */
    public function getRelatedResourceReference(
        /* @noinspection PhpUnusedParameterInspection */
        ResourceSet $sourceResourceSet,
        Model $sourceEntityInstance,
        ResourceSet $targetResourceSet,
        ResourceProperty $targetProperty
    ) {
        $this->checkAuth($sourceEntityInstance);

        $propertyName = $targetProperty->getName();
        $propertyName = $this->getLaravelRelationName($propertyName);
        /** @var Model|null $result */
        $result = $sourceEntityInstance->{$propertyName}()->first();
        if (null === $result) {
            return null;
        }
        if ($targetProperty->getResourceType()->getInstanceType()->getName() != get_class($result)) {
            return null;
        }
        return $result;
    }

    /**
     * Gets a related entity instance from an entity set identified by a key
     * IE: http://host/EntitySet(1L)/NavigationPropertyToCollection(33).
     *
     * @param ResourceSet      $sourceResourceSet    The entity set containing the source entity
     * @param Model            $sourceEntityInstance the source entity instance
     * @param ResourceSet      $targetResourceSet    The entity set containing the entity to fetch
     * @param ResourceProperty $targetProperty       the metadata of the target property
     * @param KeyDescriptor    $keyDescriptor        The key identifying the entity to fetch
     *
     * @throws InvalidOperationException
     * @throws \Exception
     * @return Model|null                Returns entity instance if found else null
     */
    public function getResourceFromRelatedResourceSet(
        /* @noinspection PhpUnusedParameterInspection */
        ResourceSet $sourceResourceSet,
        Model $sourceEntityInstance,
        ResourceSet $targetResourceSet,
        ResourceProperty $targetProperty,
        KeyDescriptor $keyDescriptor
    ) {
        $propertyName = $targetProperty->getName();
        if (!method_exists($sourceEntityInstance, $propertyName)) {
            $msg = 'Relation method, ' . $propertyName . ', does not exist on supplied entity.';
            throw new InvalidArgumentException($msg);
        }
        // take key descriptor and turn it into where clause here, rather than in getResource call
        /** @var Relation $sourceEntityInstance */
        $sourceEntityInstance = $sourceEntityInstance->{$propertyName}();
        $this->processKeyDescriptor($sourceEntityInstance, $keyDescriptor);
        $result = $this->getResource(null, null, [], [], $sourceEntityInstance);
        if (!(null == $result || $result instanceof Model)) {
            $msg = 'GetResourceFromRelatedResourceSet must return an entity or null';
            throw new InvalidOperationException($msg);
        }
        return $result;
    }

    /**
     * @param Model|Relation|null $sourceEntityInstance
     * @param null|mixed          $checkInstance
     *
     * @throws ODataException
     */
    private function checkAuth($sourceEntityInstance, $checkInstance = null)
    {
        $check = array_reduce([$sourceEntityInstance, $checkInstance], function ($carry, $item) {
            if ($item instanceof Model || $item instanceof Relation) {
                return $item;
            }
        }, null);
        $sourceName = null !== $check ? get_class($check) : null;
        if (!$this->getAuth()->canAuth(ActionVerb::READ(), $sourceName, $check)) {
            throw new ODataException('Access denied', 403);
        }
    }

    /**
     * @param  Model|Builder             $sourceEntityInstance
     * @param  bool                      $nullFilter
     * @param  array                     $rawLoad
     * @param  int                       $top
     * @param  int                       $skip
     * @param  callable|null             $isvalid
     * @throws InvalidOperationException
     * @return array
     */
    protected function applyFiltering(
        $sourceEntityInstance,
        bool $nullFilter,
        array $rawLoad = [],
        int $top = PHP_INT_MAX,
        int $skip = 0,
        callable $isvalid = null
    ) {
        $bulkSetCount = $sourceEntityInstance->count();
        $bigSet       = 20000 < $bulkSetCount;

        if ($nullFilter) {
            // default no-filter case, palm processing off to database engine - is a lot faster
            $resultSet = $sourceEntityInstance
                ->skip($skip)
                ->take($top)->with($rawLoad)
                ->get();
            $resultCount = $bulkSetCount;
        } elseif ($bigSet) {
            if (!(isset($isvalid))) {
                $msg = 'Filter closure not set';
                throw new InvalidOperationException($msg);
            }
            $resultSet = new Collection([]);
            $rawCount  = 0;
            $rawTop    = min($top, $bulkSetCount);

            // loop thru, chunk by chunk, to reduce chances of exhausting memory
            $sourceEntityInstance->chunk(
                5000,
                function (Collection $results) use ($isvalid, &$skip, &$resultSet, &$rawCount, $rawTop) {
                    // apply filter
                    $results = $results->filter($isvalid);
                    // need to iterate through full result set to find count of items matching filter,
                    // so we can't bail out early
                    $rawCount += $results->count();
                    // now bolt on filtrate to accumulating result set if we haven't accumulated enough bitz
                    if ($rawTop > $resultSet->count() + $skip) {
                        $resultSet = collect(array_merge($resultSet->all(), $results->all()));
                        $sliceAmount = min($skip, $resultSet->count());
                        $resultSet = $resultSet->slice($sliceAmount);
                        $skip -= $sliceAmount;
                    }
                }
            );

            // clean up residual to-be-skipped records
            $resultSet   = $resultSet->slice($skip);
            $resultCount = $rawCount;
        } else {
            /** @var Collection $resultSet */
            $resultSet   = $sourceEntityInstance->with($rawLoad)->get();
            $resultSet   = $resultSet->filter($isvalid);
            $resultCount = $resultSet->count();

            $resultSet = $resultSet->slice($skip);
        }
        return [$bulkSetCount, $resultSet, $resultCount, $skip];
    }

    /**
     * @param $sourceEntityInstance
     * @param  string                   $tableName
     * @param  InternalOrderByInfo|null $orderBy
     * @return mixed
     */
    protected function buildOrderBy($sourceEntityInstance, string $tableName, InternalOrderByInfo $orderBy = null)
    {
        if (null != $orderBy) {
            foreach ($orderBy->getOrderByInfo()->getOrderByPathSegments() as $order) {
                foreach ($order->getSubPathSegments() as $subOrder) {
                    $subName              = $subOrder->getName();
                    $subName              = $tableName . '.' . $subName;
                    $sourceEntityInstance = $sourceEntityInstance->orderBy(
                        $subName,
                        $order->isAscending() ? 'asc' : 'desc'
                    );
                }
            }
        }
        return $sourceEntityInstance;
    }

    /**
     * @param QueryType   $queryType
     * @param int         $skip
     * @param QueryResult $result
     * @param $resultSet
     * @param int $resultCount
     * @param int $bulkSetCount
     */
    protected function packageResourceSetResults(
        QueryType $queryType,
        int $skip,
        QueryResult $result,
        $resultSet,
        int $resultCount,
        int $bulkSetCount
    ) {
        $qVal = $queryType;
        if (QueryType::ENTITIES() == $qVal || QueryType::ENTITIES_WITH_COUNT() == $qVal) {
            $result->results = [];
            foreach ($resultSet as $res) {
                $result->results[] = $res;
            }
        }
        if (QueryType::COUNT() == $qVal || QueryType::ENTITIES_WITH_COUNT() == $qVal) {
            $result->count = $resultCount;
        }
        $hazMore         = $bulkSetCount > $skip + count($resultSet);
        $result->hasMore = $hazMore;
    }
}
