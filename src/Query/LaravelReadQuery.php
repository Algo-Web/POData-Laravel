<?php

namespace AlgoWeb\PODataLaravel\Query;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\App;
use Symfony\Component\Process\Exception\InvalidArgumentException;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Query\QueryResult;
use POData\Providers\Query\QueryType;
use POData\UriProcessor\QueryProcessor\ExpressionParser\FilterInfo;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\KeyDescriptor;

class LaravelReadQuery
{

    /**
     * Gets collection of entities belongs to an entity set
     * IE: http://host/EntitySet
     *  http://host/EntitySet?$skip=10&$top=5&filter=Prop gt Value
     *
     * @param QueryType $queryType indicates if this is a query for a count, entities, or entities with a count
     * @param ResourceSet $resourceSet The entity set containing the entities to fetch
     * @param FilterInfo $filterInfo represents the $filter parameter of the OData query.  NULL if no $filter specified
     * @param mixed $orderBy sorted order if we want to get the data in some specific order
     * @param int $top number of records which  need to be skip
     * @param String $skipToken value indicating what records to skip
     * @param Model|Relation|null $sourceEntityInstance Starting point of query
     *
     * @return QueryResult
     */
    public function getResourceSet(
        QueryType $queryType,
        ResourceSet $resourceSet,
        $filterInfo = null,
        $orderBy = null,
        $top = null,
        $skipToken = null,
        $sourceEntityInstance = null
    ) {
        if (null != $filterInfo && !($filterInfo instanceof FilterInfo)) {
            throw new InvalidArgumentException('Filter info must be either null or instance of FilterInfo.');
        }

        $this->checkSourceInstance($sourceEntityInstance);

        if (null == $sourceEntityInstance) {
            $sourceEntityInstance = $this->getSourceEntityInstance($resourceSet);
        }

        $result          = new QueryResult();
        $result->results = null;
        $result->count   = null;

        if (null != $orderBy) {
            foreach ($orderBy->getOrderByInfo()->getOrderByPathSegments() as $order) {
                foreach ($order->getSubPathSegments() as $subOrder) {
                    $sourceEntityInstance = $sourceEntityInstance->orderBy(
                        $subOrder->getName(),
                        $order->isAscending() ? 'asc' : 'desc'
                    );
                }
            }
        }

        if (!isset($skipToken)) {
            $skipToken = 0;
        }

        $nullFilter = true;
        $isvalid = null;
        if (isset($filterInfo)) {
            $method = "return ".$filterInfo->getExpressionAsString().";";
            $clln = "$".$resourceSet->getResourceType()->getName();
            $isvalid = create_function($clln, $method);
            $nullFilter = false;
        }

        $bulkSetCount = $sourceEntityInstance->count();
        $bigSet = 20000 < $bulkSetCount;

        if ($nullFilter) {
            // default no-filter case, palm processing off to database engine - is a lot faster
            $resultSet = $sourceEntityInstance->skip($skipToken)->take($top)->get();
            $resultCount = $bulkSetCount;
        } elseif ($bigSet) {
            assert(isset($isvalid), "Filter closure not set");
            $resultSet = collect([]);
            $rawCount = 0;
            $rawTop = null === $top ? $bulkSetCount : $top;

            // loop thru, chunk by chunk, to reduce chances of exhausting memory
            $sourceEntityInstance->chunk(
                5000,
                function ($results) use ($isvalid, &$skipToken, &$resultSet, &$rawCount, $rawTop) {
                    // apply filter
                    $results = $results->filter($isvalid);
                    // need to iterate through full result set to find count of items matching filter,
                    // so we can't bail out early
                    $rawCount += $results->count();
                    // now bolt on filtrate to accumulating result set if we haven't accumulated enough bitz
                    if ($rawTop > $resultSet->count() + $skipToken) {
                        $resultSet = collect(array_merge($resultSet->all(), $results->all()));
                        $sliceAmount = min($skipToken, $resultSet->count());
                        $resultSet = $resultSet->slice($sliceAmount);
                        $skipToken -= $sliceAmount;
                    }
                }
            );

            // clean up residual to-be-skipped records
            $resultSet = $resultSet->slice($skipToken);
            $resultCount = $rawCount;
        } else {
            $resultSet = $sourceEntityInstance->get();
            $resultSet = $resultSet->filter($isvalid);
            $resultCount = $resultSet->count();

            if (isset($skipToken)) {
                $resultSet = $resultSet->slice($skipToken);
            }
        }

        if (isset($top)) {
            $resultSet = $resultSet->take($top);
        }


        if (QueryType::ENTITIES() == $queryType || QueryType::ENTITIES_WITH_COUNT() == $queryType) {
            $result->results = array();
            foreach ($resultSet as $res) {
                $result->results[] = $res;
            }
        }
        if (QueryType::COUNT() == $queryType || QueryType::ENTITIES_WITH_COUNT() == $queryType) {
            $result->count = $resultCount;
        }
        return $result;
    }

    /**
     * Get related resource set for a resource
     * IE: http://host/EntitySet(1L)/NavigationPropertyToCollection
     * http://host/EntitySet?$expand=NavigationPropertyToCollection
     *
     * @param QueryType $queryType indicates if this is a query for a count, entities, or entities with a count
     * @param ResourceSet $sourceResourceSet The entity set containing the source entity
     * @param object $sourceEntityInstance The source entity instance.
     * @param ResourceSet $targetResourceSet The resource set of containing the target of the navigation property
     * @param ResourceProperty $targetProperty The navigation property to retrieve
     * @param FilterInfo $filter represents the $filter parameter of the OData query.  NULL if no $filter specified
     * @param mixed $orderBy sorted order if we want to get the data in some specific order
     * @param int $top number of records which  need to be skip
     * @param String $skip value indicating what records to skip
     *
     * @return QueryResult
     *
     */
    public function getRelatedResourceSet(
        QueryType $queryType,
        ResourceSet $sourceResourceSet,
        $sourceEntityInstance,
        ResourceSet $targetResourceSet,
        ResourceProperty $targetProperty,
        $filter = null,
        $orderBy = null,
        $top = null,
        $skip = null
    ) {
        if (!($sourceEntityInstance instanceof Model)) {
            throw new InvalidArgumentException('Source entity must be an Eloquent model.');
        }

        assert(null != $sourceEntityInstance, "Source instance must not be null");
        $this->checkSourceInstance($sourceEntityInstance);

        $propertyName = $targetProperty->getName();
        $results = $sourceEntityInstance->$propertyName();

        return $this->getResourceSet(
            $queryType,
            $sourceResourceSet,
            $filter,
            $orderBy,
            $top,
            $skip,
            $results
        );
    }

    /**
     * Gets an entity instance from an entity set identified by a key
     * IE: http://host/EntitySet(1L)
     * http://host/EntitySet(KeyA=2L,KeyB='someValue')
     *
     * @param ResourceSet $resourceSet The entity set containing the entity to fetch
     * @param KeyDescriptor $keyDescriptor The key identifying the entity to fetch
     *
     * @return object|null Returns entity instance if found else null
     */
    public function getResourceFromResourceSet(
        ResourceSet $resourceSet,
        KeyDescriptor $keyDescriptor = null
    ) {
        return $this->getResource($resourceSet, $keyDescriptor);
    }


    /**
     * Common method for getResourceFromRelatedResourceSet() and getResourceFromResourceSet()
     * @param ResourceSet|null $resourceSet
     * @param KeyDescriptor|null $keyDescriptor
     * @param Model|Relation|null $sourceEntityInstance Starting point of query
     */
    public function getResource(
        ResourceSet $resourceSet = null,
        KeyDescriptor $keyDescriptor = null,
        array $whereCondition = [],
        $sourceEntityInstance = null
    ) {
        if (null == $resourceSet && null == $sourceEntityInstance) {
            throw new \Exception('Must supply at least one of a resource set and source entity.');
        }

        $this->checkSourceInstance($sourceEntityInstance);

        if (null == $sourceEntityInstance) {
            assert(null != $resourceSet);
            $sourceEntityInstance = $this->getSourceEntityInstance($resourceSet);
        }

        if ($keyDescriptor) {
            foreach ($keyDescriptor->getValidatedNamedValues() as $key => $value) {
                $trimValue = trim($value[0], "\"'");
                $sourceEntityInstance = $sourceEntityInstance->where($key, $trimValue);
            }
        }
        foreach ($whereCondition as $fieldName => $fieldValue) {
            $sourceEntityInstance = $sourceEntityInstance->where($fieldName, $fieldValue);
        }
        $sourceEntityInstance = $sourceEntityInstance->get();
        return (0 == $sourceEntityInstance->count()) ? null : $sourceEntityInstance->first();
    }

    /**
     * Get related resource for a resource
     * IE: http://host/EntitySet(1L)/NavigationPropertyToSingleEntity
     * http://host/EntitySet?$expand=NavigationPropertyToSingleEntity
     *
     * @param ResourceSet $sourceResourceSet The entity set containing the source entity
     * @param object $sourceEntityInstance The source entity instance.
     * @param ResourceSet $targetResourceSet The entity set containing the entity pointed to by the navigation property
     * @param ResourceProperty $targetProperty The navigation property to fetch
     *
     * @return object|null The related resource if found else null
     */
    public function getRelatedResourceReference(
        ResourceSet $sourceResourceSet,
        $sourceEntityInstance,
        ResourceSet $targetResourceSet,
        ResourceProperty $targetProperty
    ) {
        if (!($sourceEntityInstance instanceof Model)) {
            throw new InvalidArgumentException('Source entity must be an Eloquent model.');
        }
        $this->checkSourceInstance($sourceEntityInstance);

        $propertyName = $targetProperty->getName();
        return $sourceEntityInstance->$propertyName;
    }

    /**
     * Gets a related entity instance from an entity set identified by a key
     * IE: http://host/EntitySet(1L)/NavigationPropertyToCollection(33)
     *
     * @param ResourceSet $sourceResourceSet The entity set containing the source entity
     * @param object $sourceEntityInstance The source entity instance.
     * @param ResourceSet $targetResourceSet The entity set containing the entity to fetch
     * @param ResourceProperty $targetProperty The metadata of the target property.
     * @param KeyDescriptor $keyDescriptor The key identifying the entity to fetch
     *
     * @return object|null Returns entity instance if found else null
     */
    public function getResourceFromRelatedResourceSet(
        ResourceSet $sourceResourceSet,
        $sourceEntityInstance,
        ResourceSet $targetResourceSet,
        ResourceProperty $targetProperty,
        KeyDescriptor $keyDescriptor
    ) {
        if (!($sourceEntityInstance instanceof Model)) {
            throw new InvalidArgumentException('Source entity must be an Eloquent model.');
        }
        $propertyName = $targetProperty->getName();
        return $this->getResource(null, $keyDescriptor, [], $sourceEntityInstance->$propertyName);
    }


    /**
     * @param ResourceSet $resourceSet
     * @return mixed
     */
    protected function getSourceEntityInstance(ResourceSet $resourceSet)
    {
        $entityClassName = $resourceSet->getResourceType()->getInstanceType()->name;
        return App::make($entityClassName);
    }

    /**
     * @param Model|Relation|null $source
     */
    protected function checkSourceInstance($source)
    {
        if (!(null == $source || $source instanceof Model || $source instanceof Relation)) {
            throw new InvalidArgumentException('Source entity instance must be null, a model, or a relation.');
        }
    }
}
