<?php

namespace AlgoWeb\PODataLaravel\Query;

use AlgoWeb\PODataLaravel\Enums\ActionVerb;
use Illuminate\Database\Eloquent\Relations\Relation;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourceSet;
use POData\UriProcessor\QueryProcessor\Expression\Parser\IExpressionProvider;
use POData\UriProcessor\QueryProcessor\ExpressionParser\FilterInfo;
use POData\UriProcessor\QueryProcessor\OrderByParser\InternalOrderByInfo;
use POData\UriProcessor\QueryProcessor\SkipTokenParser\SkipTokenInfo;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\KeyDescriptor;
use POData\Providers\Query\IQueryProvider;
use POData\Providers\Expression\MySQLExpressionProvider;
use POData\Providers\Query\QueryType;
use POData\Providers\Query\QueryResult;
use POData\Providers\Expression\PHPExpressionProvider;
use \POData\Common\ODataException;
use AlgoWeb\PODataLaravel\Interfaces\AuthInterface;
use AlgoWeb\PODataLaravel\Auth\NullAuthProvider;
use Illuminate\Support\Facades\App;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Process\Exception\InvalidArgumentException;

class LaravelQuery implements IQueryProvider
{
    protected $expression;
    protected $auth;
    protected $reader;
    public $queryProviderClassName;
    private $verbMap = [];

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
    }

    /**
     * Indicates if the QueryProvider can handle ordered paging, this means respecting order, skip, and top parameters
     * If the query provider can not handle ordered paging, it must return the entire result set and POData will
     * perform the ordering and paging
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
     * Gets the LaravelReadQuery instance used to handle read queries (repetitious, nyet?)
     *
     * @return LaravelReadQuery
     */
    public function getReader()
    {
        return $this->reader;
    }

    /**
     * Gets collection of entities belongs to an entity set
     * IE: http://host/EntitySet
     *  http://host/EntitySet?$skip=10&$top=5&filter=Prop gt Value
     *
     * @param QueryType                 $queryType   indicates if this is a query for a count, entities, or entities with a count
     * @param ResourceSet               $resourceSet The entity set containing the entities to fetch
     * @param FilterInfo                $filterInfo  represents the $filter parameter of the OData query.  NULL if no $filter specified
     * @param null|InternalOrderByInfo  $orderBy     sorted order if we want to get the data in some specific order
     * @param int                       $top         number of records which need to be retrieved
     * @param int                       $skip        number of records which need to be skipped
     * @param SkipTokenInfo|null        $skipToken   value indicating what records to skip
     * @param Model|Relation|null       $sourceEntityInstance Starting point of query
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
        return $this->getReader()->getResourceSet(
            $queryType,
            $resourceSet,
            $filterInfo,
            $orderBy,
            $top,
            $skip,
            $skipToken,
            $sourceEntityInstance
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
        return $this->getReader()->getResourceFromResourceSet($resourceSet, $keyDescriptor);
    }

    /**
     * Get related resource set for a resource
     * IE: http://host/EntitySet(1L)/NavigationPropertyToCollection
     * http://host/EntitySet?$expand=NavigationPropertyToCollection
     *
     * @param QueryType             $queryType            indicates if this is a query for a count, entities, or entities with a count
     * @param ResourceSet           $sourceResourceSet    The entity set containing the source entity
     * @param object                $sourceEntityInstance The source entity instance
     * @param ResourceSet           $targetResourceSet    The resource set of containing the target of the navigation property
     * @param ResourceProperty      $targetProperty       The navigation property to retrieve
     * @param FilterInfo            $filter               represents the $filter parameter of the OData query.  NULL if no $filter specified
     * @param mixed                 $orderBy              sorted order if we want to get the data in some specific order
     * @param int                   $top                  number of records which need to be retrieved
     * @param int                   $skip                 number of records which need to be skipped
     * @param SkipTokenInfo|null    $skipToken            value indicating what records to skip
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
        $skip = null,
        $skipToken = null
    ) {
        return $this->getReader()->getRelatedResourceSet(
            $queryType,
            $sourceResourceSet,
            $sourceEntityInstance,
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
        return $this->getReader()->getResourceFromRelatedResourceSet(
            $sourceResourceSet,
            $sourceEntityInstance,
            $targetResourceSet,
            $targetProperty,
            $keyDescriptor
        );
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
        $result = $this->getReader()->getRelatedResourceReference(
            $sourceResourceSet,
            $sourceEntityInstance,
            $targetResourceSet,
            $targetProperty
        );
        return $result;
    }

    /**
     * Updates a resource
     *
     * @param ResourceSet      $sourceResourceSet    The entity set containing the source entity
     * @param object           $sourceEntityInstance The source entity instance
     * @param KeyDescriptor    $keyDescriptor        The key identifying the entity to fetch
     * @param object           $data                 The New data for the entity instance.
     * @param bool             $shouldUpdate        Should undefined values be updated or reset to default
     *
     * @return object|null The new resource value if it is assignable or throw exception for null.
     */
    public function updateResource(
        ResourceSet $sourceResourceSet,
        $sourceEntityInstance,
        KeyDescriptor $keyDescriptor,
        $data,
        $shouldUpdate = false
    ) {
        $verb = 'update';
        return $this->createUpdateCoreWrapper($sourceResourceSet, $sourceEntityInstance, $data, $verb);
    }
    /**
     * Delete resource from a resource set.
     * @param ResourceSet|null $sourceResourceSet
     * @param object           $sourceEntityInstance
     *
     * return bool true if resources sucessfully deteled, otherwise false.
     */
    public function deleteResource(
        ResourceSet $sourceResourceSet,
        $sourceEntityInstance
    ) {
        $verb = 'delete';
        if (!($sourceEntityInstance instanceof Model)) {
            throw new InvalidArgumentException('Source entity must be an Eloquent model.');
        }

        $class = $sourceResourceSet->getResourceType()->getInstanceType()->getName();
        $id = $sourceEntityInstance->getKey();
        $name = $sourceEntityInstance->getKeyName();
        $data = [$name => $id];

        $data = $this->createUpdateDeleteCore($sourceEntityInstance, $data, $class, $verb);

        $success = isset($data['id']);
        if ($success) {
            return true;
        }
        throw new ODataException('Target model not successfully deleted', 422);
    }
    /**
     * @param ResourceSet      $resourceSet   The entity set containing the entity to fetch
     * @param object           $sourceEntityInstance The source entity instance
     * @param object           $data                 The New data for the entity instance.
     *
     * returns object|null returns the newly created model if sucessful or null if model creation failed.
     */
    public function createResourceforResourceSet(
        ResourceSet $resourceSet,
        $sourceEntityInstance,
        $data
    ) {
        $verb = 'create';
        return $this->createUpdateCoreWrapper($resourceSet, $sourceEntityInstance, $data, $verb);
    }

    /**
     * @param $sourceEntityInstance
     * @param $data
     * @param $class
     * @param string $verb
     * @return array|mixed
     * @throws ODataException
     * @throws \POData\Common\InvalidOperationException
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

        assert($data != null, "Data must not be null");
        if (is_object($data)) {
            $data = (array) $data;
        }
        if (!is_array($data)) {
            throw \POData\Common\ODataException::createPreConditionFailedError(
                'Data not resolvable to key-value array.'
            );
        }

        $controlClass = $goal['controller'];
        $method = $goal['method'];
        $paramList = $goal['parameters'];
        $controller = App::make($controlClass);
        $parms = $this->createUpdateDeleteProcessInput($sourceEntityInstance, $data, $paramList);
        unset($data);

        $result = call_user_func_array(array($controller, $method), $parms);

        return $this->createUpdateDeleteProcessOutput($result);
    }

    /**
     * Puts an entity instance to entity set identified by a key.
     *
     * @param ResourceSet $resourceSet The entity set containing the entity to update
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
     * @param $sourceEntityInstance
     * @param $data
     * @param $verb
     * @return mixed
     * @throws ODataException
     * @throws \POData\Common\InvalidOperationException
     */
    private function createUpdateCoreWrapper(ResourceSet $sourceResourceSet, $sourceEntityInstance, $data, $verb)
    {
        $lastWord = 'update' == $verb ? 'updated' : 'created';
        if (!(null == $sourceEntityInstance || $sourceEntityInstance instanceof Model)) {
            throw new InvalidArgumentException('Source entity must either be null or an Eloquent model.');
        }

        $class = $sourceResourceSet->getResourceType()->getInstanceType()->getName();
        if (!$this->auth->canAuth($this->verbMap[$verb], $class, $sourceEntityInstance)) {
            throw new ODataException("Access denied", 403);
        }

        $data = $this->createUpdateDeleteCore($sourceEntityInstance, $data, $class, $verb);

        $success = isset($data['id']);

        if ($success) {
            try {
                return $class::findOrFail($data['id']);
            } catch (\Exception $e) {
                throw new ODataException($e->getMessage(), 500);
            }
        }
        throw new ODataException('Target model not successfully '.$lastWord, 422);
    }

    /**
     * @param $sourceEntityInstance
     * @param $data
     * @param $paramList
     * @return array
     */
    private function createUpdateDeleteProcessInput($sourceEntityInstance, $data, $paramList)
    {
        $parms = [];

        foreach ($paramList as $spec) {
            $varType = isset($spec['type']) ? $spec['type'] : null;
            $varName = $spec['name'];
            if (null == $varType) {
                $parms[] = $sourceEntityInstance->$varName;
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
     * @return array|mixed
     * @throws ODataException
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
}
