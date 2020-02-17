<?php

declare(strict_types=1);

namespace Tests\Legacy\AlgoWeb\PODataLaravel\Unit\Query;

use AlgoWeb\PODataLaravel\Auth\NullAuthProvider;
use AlgoWeb\PODataLaravel\Controllers\MetadataControllerContainer;
use AlgoWeb\PODataLaravel\Interfaces\AuthInterface;
use AlgoWeb\PODataLaravel\Providers\MetadataProvider;
use AlgoWeb\PODataLaravel\Query\LaravelExpressionProvider;
use AlgoWeb\PODataLaravel\Query\LaravelHookQuery;
use AlgoWeb\PODataLaravel\Query\LaravelQuery;
use AlgoWeb\PODataLaravel\Query\LaravelReadQuery;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Mockery as m;
use POData\Common\InvalidOperationException;
use POData\Common\ODataException;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\SimpleMetadataProvider;
use POData\Providers\Metadata\Type\Binary;
use POData\Providers\Metadata\Type\IType;
use POData\Providers\Query\QueryResult;
use POData\Providers\Query\QueryType;
use POData\UriProcessor\QueryProcessor\ExpressionParser\FilterInfo;
use POData\UriProcessor\QueryProcessor\OrderByParser\InternalOrderByInfo;
use POData\UriProcessor\QueryProcessor\OrderByParser\OrderByPathSegment;
use POData\UriProcessor\QueryProcessor\OrderByParser\OrderBySubPathSegment;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\KeyDescriptor;
use Symfony\Component\Process\Exception\InvalidArgumentException;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Controllers\TestController;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestModel;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMonomorphicSource;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMonomorphicTarget;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMorphManySource;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMorphManyToManySource;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMorphManyToManyTarget;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMorphOneSource;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMorphTarget;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Query\LaravelHookQueryDummy;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Query\LaravelQueryDummy;
use Tests\Legacy\AlgoWeb\PODataLaravel\TestCase as TestCase;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
/**
 * Generated Test Class.
 */
class LaravelQueryTest extends TestCase
{
    /**
     * @var \AlgoWeb\PODataLaravel\Query\LaravelQuery
     */
    protected $object;

    /**
     * @var \AlgoWeb\PODataLaravel\Query\LaravelReadQuery
     */
    protected $reader;

    protected $mapping;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    public function setUp(): void
    {
        parent::setUp();
        //$this->object = new \AlgoWeb\PODataLaravel\Query\LaravelQuery();
        $this->mapping = [
            TestModel::class =>
                [
                    'create' => 'storeTestModel',
                    'read' => 'showTestModel',
                    'update' => 'updateTestModel',
                    'delete' => 'destroyTestModel'
                ]
        ];

        $foo = new LaravelQuery();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    public function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @covers \AlgoWeb\PODataLaravel\Query\LaravelQuery::handlesOrderedPaging
     */
    public function testHandlesOrderedPaging()
    {
        $foo = new LaravelQuery();
        $this->assertTrue($foo->handlesOrderedPaging());
    }


    /**
     * @covers \AlgoWeb\PODataLaravel\Query\LaravelQuery::getExpressionProvider
     */
    public function testGetExpressionProvider()
    {
        $foo    = new LaravelQuery();
        $result = $foo->getExpressionProvider();
        $this->assertTrue($result instanceof LaravelExpressionProvider);
        $this->assertNull($result->getIteratorName());
        $this->assertNull($result->getResourceType());
    }

    public function testGetResourceSetBadFilterInfoInstanceThrowException()
    {
        $query       = m::mock(QueryType::class);
        $resourceSet = m::mock(ResourceSet::class);
        $filter      = new \DateTime();

        $foo = new LaravelQuery();

        $phpVersion = phpversion();
        if ($phpVersion > 6) {
            $this->expectException(\TypeError::class);
        } else {
            $this->expectException(\ErrorException::class);
        }
        $foo->getResourceSet($query, $resourceSet, $filter);
    }

    public function testGetResourceSetBadSourceInstanceButStillObject()
    {
        $query       = m::mock(QueryType::class);
        $resourceSet = m::mock(ResourceSet::class);
        $source      = new \DateTime();

        $foo = new LaravelQuery();

        $expected = 'Source entity instance must be null, a model, or a relation.';
        $actual   = null;

        try {
            $foo->getResourceSet($query, $resourceSet, null, null, null, null, null, null, $source);
        } catch (InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetResourceSetBadSourceInstanceButNotObject()
    {
        $query       = m::mock(QueryType::class);
        $resourceSet = m::mock(ResourceSet::class);
        $source      = 'aybabtu';

        $foo = new LaravelQuery();

        $expected = 'Source entity instance must be null, a model, or a relation.';
        $actual   = null;

        try {
            $foo->getResourceSet($query, $resourceSet, null, null, null, null, null, null, $source);
        } catch (InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetResourceSetAccessDenied()
    {
        $query       = m::mock(QueryType::class);
        $resourceSet = m::mock(ResourceSet::class);
        $source      = new TestModel();

        $auth = m::mock(AuthInterface::class);
        $auth->shouldReceive('canAuth')->withAnyArgs()->andReturn(false)->once();

        $foo = new LaravelQuery($auth);

        $expected     = 'Access denied';
        $actual       = null;
        $expectedCode = 403;
        $actualCode   = null;

        try {
            $foo->getResourceSet($query, $resourceSet, null, null, null, null, null, null, $source);
        } catch (ODataException $e) {
            $actual     = $e->getMessage();
            $actualCode = $e->getStatusCode();
        }
        $this->assertEquals($expected, $actual);
        $this->assertEquals($expectedCode, $actualCode);
    }

    /**
     * @covers \AlgoWeb\PODataLaravel\Query\LaravelQuery::getResourceSet
     */
    public function testGetResourceSetWithEntitiesAndCount()
    {
        $instanceType       = new \stdClass();
        $instanceType->name = 'AlgoWeb\\PODataLaravel\\Models\\TestMorphManySource';

        $mockResource = \Mockery::mock(ResourceSet::class);
        $mockResource->shouldReceive('getResourceType->getInstanceType')->andReturn($instanceType);

        $queryType = QueryType::ENTITIES_WITH_COUNT();

        $property = \Mockery::mock(ResourceProperty::class);
        $property->shouldReceive('getName')->withNoArgs()->andReturn('morphTarget');

        $rawBuilder = $this->getBuilder();

        $rawResult = \Mockery::mock(\Illuminate\Database\Eloquent\Builder::class)
            ->makePartial();
        $rawResult->shouldReceive('get')->andReturn(collect(['eins', 'zwei', 'polizei']))->once();
        $rawResult->setQuery($rawBuilder);
        $rawResult->shouldReceive('take')->andReturnSelf()->once();
        $rawResult->shouldReceive('with')->andReturnSelf()->once();
        $this->assertTrue(null != ($rawResult->getQuery()->getProcessor()));

        $sourceEntity = \Mockery::mock(TestMorphManySource::class)->makePartial();
        $sourceEntity->shouldReceive('getKeyName')->andReturn('id');
        $sourceEntity->shouldReceive('getEagerLoad')->andReturn([]);
        $sourceEntity->shouldReceive('morphTarget')->andReturn($rawResult);
        $sourceEntity->shouldReceive('newQuery')->andReturnSelf()->never();
        $sourceEntity->shouldReceive('count')->andReturn(3)->once();
        $sourceEntity->shouldReceive('skip')->andReturn($rawResult)->once();
        App::instance($instanceType->name, $sourceEntity);

        $reader = new LaravelReadQuery();
        $foo    = \Mockery::mock(LaravelQuery::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getReader')->andReturn($reader);
        //$foo->shouldReceive('getSourceEntityInstance')->andReturn($rawResult);

        $expected = ['eins', 'zwei', 'polizei'];

        $result = $foo->getResourceSet($queryType, $mockResource);
        $this->assertTrue($result instanceof QueryResult);
        $this->assertEquals(3, $result->count);
        $this->assertEquals($expected, $result->results);
    }

    public function testGetResourceSetWithNoInstance()
    {
        $rawResult = \Mockery::mock(\Illuminate\Database\Eloquent\Builder::class)
            ->makePartial();
        $rawResult->shouldReceive('take')->andReturnSelf()->once();
        $rawResult->shouldReceive('get')->andReturn(collect(['a', 'b']))->once();

        $testModel = m::mock(TestModel::class)->makePartial();
        $testModel->shouldReceive('count')->andReturn(2)->once();
        $testModel->shouldReceive('skip')->andReturn($rawResult)->once();
        App::instance(TestModel::class, $testModel);

        $instance       = new \stdClass();
        $instance->name = TestModel::class;

        $queryType = QueryType::ENTITIES();
        $type      = m::mock(ResourceType::class);
        $type->shouldReceive('getInstanceType')->andReturn($instance);

        $resource = m::mock(ResourceSet::class);
        $resource->shouldReceive('getResourceType')->andReturn($type);
        $sourceEntityInstance = null;

        $reader = new LaravelReadQuery();
        $foo    = \Mockery::mock(LaravelQuery::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getReader')->andReturn($reader);

        $expected = ['a', 'b'];
        $result   = $foo->getResourceSet($queryType, $resource);
        $this->assertTrue(is_array($result->results));
        $this->assertEquals(2, count($result->results));
        $this->assertEquals($expected, $result->results);
        $this->assertNull($result->count);
    }

    public function testGetResourceWithNoInstance()
    {
        $where = ['foo' => 2];

        $mod1 = m::mock(TestModel::class)->makePartial();
        $mod1->shouldReceive('getKey')->andReturn('foo');
        $mod2 = m::mock(TestModel::class)->makePartial();
        $mod2->shouldReceive('getKey')->andReturn('bar');

        $testModel = m::mock(TestModel::class)->makePartial();
        $testModel->shouldReceive('get')->andReturn(collect([$mod1, $mod2]))->once();
        $testModel->shouldReceive('where')->andReturn($testModel);
        App::instance(TestModel::class, $testModel);

        $key = null;

        $instance       = new \stdClass();
        $instance->name = TestModel::class;

        $type = m::mock(ResourceType::class);
        $type->shouldReceive('getInstanceType')->andReturn($instance);

        $resource = m::mock(ResourceSet::class);
        $resource->shouldReceive('getResourceType')->andReturn($type);
        $sourceEntityInstance = null;

        $reader = new LaravelReadQuery();
        $foo    = \Mockery::mock(LaravelQuery::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getReader')->andReturn($reader);

        $result = $reader->getResource($resource, $key, $where);
        $this->assertEquals($mod1, $result);
    }

    public function testGetResourceSetWithSuppliedOrderAndFilterInfo()
    {
        $instanceType       = new \stdClass();
        $instanceType->name = 'AlgoWeb\\PODataLaravel\\Models\\TestMorphManySource';

        $resourceType = m::mock(ResourceType::class);
        $resourceType->shouldReceive('getInstanceType')->andReturn($instanceType);
        $resourceType->shouldReceive('getName')->andReturn('name');

        $mockResource = \Mockery::mock(ResourceSet::class);
        $mockResource->shouldReceive('getResourceType')->andReturn($resourceType);

        $queryType = QueryType::COUNT();

        $property = \Mockery::mock(ResourceProperty::class);
        $property->shouldReceive('getName')->withNoArgs()->andReturn('morphTarget');

        $conn = m::mock(Connection::class)->makePartial();
        $conn->shouldReceive('getName')->andReturn('default');

        $proc = m::mock(Processor::class)->makePartial();

        $rawBuilder = $this->getBuilder($conn, $proc);

        $morphTarg = new TestMorphTarget();

        $rawResult = \Mockery::mock(\Illuminate\Database\Eloquent\Builder::class)
            ->makePartial();
        $rawResult->shouldReceive('get')->andReturn(collect(['eins', 'zwei', 'polizei']));
        $rawResult->setQuery($rawBuilder);
        $rawResult->shouldReceive('with')->andReturnSelf()->never();
        $this->assertTrue(null != ($rawResult->getQuery()->getProcessor()));

        $modelArray = [$morphTarg, $morphTarg, $morphTarg];

        $resultSet = m::mock(\Illuminate\Support\Collection::class)->makePartial();
        $resultSet->shouldReceive('get')->andReturn(collect($modelArray));
        $resultSet->shouldReceive('count')->andReturn(3);
        $resultSet->shouldReceive('slice')->andReturnSelf()->once();
        $resultSet->shouldReceive('take')->andReturnSelf()->once();
        $resultSet->shouldReceive('filter')->andReturnSelf()->once();

        $sourceEntity = \Mockery::mock(TestMorphManySource::class)->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $sourceEntity->shouldReceive('getKeyName')->andReturn('id');
        $sourceEntity->shouldReceive('get')->andReturn($resultSet);
        $sourceEntity->shouldReceive('newBaseQueryBuilder')->andReturn($rawBuilder);
        $sourceEntity->shouldReceive('getEagerLoad')->andReturn([]);
        $sourceEntity->shouldReceive('morphTarget')->andReturn($rawResult);
        $sourceEntity->shouldReceive('orderBy')->withArgs(['testmorphmanytarget.hammer', 'asc'])
            ->andReturnSelf()->once();
        $sourceEntity->shouldReceive('orderBy')->withArgs(['testmorphmanytarget.hammer', 'desc'])
            ->andReturnSelf()->once();
        $sourceEntity->shouldReceive('count')->andReturn(3)->once();
        $sourceEntity->shouldReceive('skip')->andReturn($sourceEntity);
        $sourceEntity->shouldReceive('take')->andReturn($sourceEntity);
        $sourceEntity->shouldReceive('with')->andReturn($sourceEntity);
        $sourceEntity->shouldReceive('newInstance')->andReturn($sourceEntity);
        $sourceEntity->shouldReceive('get')->andReturn(collect($modelArray));

        $subPathSegment = m::mock(OrderBySubPathSegment::class);
        $subPathSegment->shouldReceive('getName')->andReturn('hammer');

        $orderByPathSegment1 = m::mock(OrderByPathSegment::class);
        $orderByPathSegment1->shouldReceive('getSubPathSegments')
            ->andReturn([$subPathSegment, $subPathSegment])->once();
        $orderByPathSegment1->shouldReceive('isAscending')->andReturn(true, false);

        $segments = [$orderByPathSegment1];

        $order = m::mock(InternalOrderByInfo::class)->makePartial();
        $order->shouldReceive('getOrderByInfo->getOrderByPathSegments')->andReturn($segments)->once();

        $filter = m::mock(FilterInfo::class);
        $filter->shouldReceive('getExpressionAsString')->andReturn('')->once();

        $reader = new LaravelReadQuery();
        $foo    = \Mockery::mock(LaravelQuery::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getReader')->andReturn($reader);
        $foo->shouldReceive('getSourceEntityInstance')->andReturn($rawResult);

        $result = $foo->getResourceSet($queryType, $mockResource, $filter, $order, 5, 2, null, null, $sourceEntity);
        $this->assertTrue($result instanceof QueryResult);
        $this->assertEquals(3, $result->count);
        $this->assertEquals(null, $result->results);
    }

    public function testGetResourceSetFromRelation()
    {
        $instanceType       = new \StdClass();
        $instanceType->name = 'AlgoWeb\\PODataLaravel\\Models\\TestMorphManySource';

        $resourceType = m::mock(ResourceType::class);
        $resourceType->shouldReceive('getInstanceType')->andReturn($instanceType);
        $resourceType->shouldReceive('getName')->andReturn('name');

        $mockResource = \Mockery::mock(ResourceSet::class);
        $mockResource->shouldReceive('getResourceType')->andReturn($resourceType);

        $queryType = QueryType::ENTITIES_WITH_COUNT();

        $related = new TestMorphManySource();

        $rawResult = \Mockery::mock(\Illuminate\Database\Eloquent\Builder::class)
            ->makePartial();
        $rawResult->shouldReceive('with')->andReturnSelf()->once();
        $rawResult->shouldReceive('get')->andReturn(collect(['a']))->once();

        $source = m::mock(HasMany::class)->makePartial();
        $source->shouldReceive('getRelated')->andReturn($related);
        $source->shouldReceive('skip')->andReturn($source)->once();
        $source->shouldReceive('take')->andReturn($rawResult)->once();
        $source->shouldReceive('count')->andReturn(1)->once();

        $auth = new NullAuthProvider();
        $foo  = m::mock(LaravelReadQuery::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getAuth')->andReturn($auth);

        $result = $foo->getResourceSet($queryType, $mockResource, null, null, null, null, null, null, $source);
        $this->assertEquals(1, $result->count);
        $this->assertEquals(1, count($result->results));
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetResourceSetWithBigSetAndFilter()
    {
        $query = m::mock(Builder::class)->makePartial();

        $instanceType       = new \StdClass();
        $instanceType->name = 'AlgoWeb\\PODataLaravel\\Models\\TestMorphManySource';

        $resourceType = m::mock(ResourceType::class);
        $resourceType->shouldReceive('getInstanceType')->andReturn($instanceType);
        $resourceType->shouldReceive('getName')->andReturn('name');

        $mockResource = \Mockery::mock(ResourceSet::class);
        $mockResource->shouldReceive('getResourceType')->andReturn($resourceType);

        $filter = m::mock(FilterInfo::class)->makePartial();
        $filter->shouldReceive('getExpressionAsString')->andReturn('true');

        $queryType = QueryType::ENTITIES_WITH_COUNT();

        $collet = collect([0, 1, 0, 1]);
        $source = m::mock(TestModel::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $source->shouldReceive('enforceOrderBy')->andReturnNull();
        $source->shouldReceive('count')->andReturn(20001)->once();
        //$source->shouldReceive('skip->take->with->get')->withAnyArgs()->andReturn(collect([1, 0]))->once();
        $source->shouldReceive('newQuery')->andReturn($query);
        //$source->shouldReceive('forPage')->withAnyArgs()->andReturn($source, collect([]));

        $resultCollect = m::mock(Collection::class);
        $resultCollect->shouldReceive('count')->andReturn(20001);
        $resultCollect->shouldReceive('filter')->andReturn($resultCollect);
        $resultCollect->shouldReceive('all')->andReturn([0, 1, 0]);
        $source->shouldReceive('forPage->get')->withAnyArgs()->andReturn($resultCollect);

        $auth = new NullAuthProvider();
        $foo  = m::mock(LaravelReadQuery::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getAuth')->andReturn($auth);

        $result = $foo->getResourceSet($queryType, $mockResource, $filter, null, 2, 1, null, null, $source);
        $this->assertEquals(20001, $result->count);
        $this->assertEquals(2, count($result->results));
        $res = $result->results;
        $this->assertEquals(1, $res[0]);
        $this->assertEquals(0, $res[1]);
    }

    /**
     * @covers \AlgoWeb\PODataLaravel\Query\LaravelQuery::getResourceFromResourceSet
     */
    public function testGetResourceFromResourceSetEmptyResult()
    {
        $instanceType       = new \StdClass();
        $instanceType->name = 'AlgoWeb\\PODataLaravel\\Models\\TestMorphManySource';

        $resourceType = m::mock(ResourceType::class);
        $resourceType->shouldReceive('getInstanceType')->andReturn($instanceType);
        $resourceType->shouldReceive('getName')->andReturn('name');

        $mockResource = \Mockery::mock(ResourceSet::class);
        $mockResource->shouldReceive('getResourceType')->andReturn($resourceType);

        $key = m::mock(KeyDescriptor::class);
        $key->shouldReceive('getValidatedNamedValues')->andReturn(['a' => 'b'])->once();

        $source = m::mock(TestMorphManySource::class)->makePartial();
        $source->shouldReceive('where')->withAnyArgs()->andReturnSelf()->times(1);
        $source->shouldReceive('get')->andReturn(collect([]))->times(1);
        App::instance($instanceType->name, $source);

        $foo    = new LaravelQuery();
        $result = $foo->getResourceFromResourceSet($mockResource, $key);
        $this->assertNull($result);
    }

    public function testGetResourceFromResourceSetUsingReaderEmptyResult()
    {
        $auth         = new NullAuthProvider();
        $mockResource = \Mockery::mock(ResourceSet::class);
        $foo          = m::mock(LaravelReadQuery::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getResource')->andReturn(null)->once();
        $foo->shouldReceive('getAuth')->andReturn($auth);

        $result = $foo->getResourceFromResourceSet($mockResource);
        $this->assertNull($result);
    }

    /**
     * @covers \AlgoWeb\PODataLaravel\Query\LaravelQuery::getRelatedResourceSet
     */
    public function testGetRelatedResourceSetWithEntitiesAndCount()
    {
        $mockResource = \Mockery::mock(ResourceSet::class);

        $queryType = QueryType::ENTITIES_WITH_COUNT();

        $property = \Mockery::mock(ResourceProperty::class);
        $property->shouldReceive('getName')->withNoArgs()->andReturn('morphTarget');

        $rawResult = \Mockery::mock(\Illuminate\Database\Eloquent\Builder::class);
        $rawResult->shouldReceive('get')->andReturn(collect(['eins', 'zwei', 'polizei']));

        $sourceEntity = \Mockery::mock(TestMorphManySource::class)->makePartial();
        $sourceEntity->shouldReceive('get')->andReturn(collect([]));

        $query          = new QueryResult();
        $query->count   = 3;
        $query->results = ['eins', 'zwei', 'polizei'];

        $auth   = new NullAuthProvider();
        $reader = m::mock(LaravelReadQuery::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $reader->shouldReceive('getResourceSet')->withAnyArgs()->andReturn($query);
        $reader->shouldReceive('getAuth')->andReturn($auth);
        $foo = \Mockery::mock(LaravelQuery::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getReader')->andReturn($reader);

        $expected = ['eins', 'zwei', 'polizei'];

        $result = $foo->getRelatedResourceSet($queryType, $mockResource, $sourceEntity, $mockResource, $property);
        $this->assertTrue($result instanceof QueryResult);
        $this->assertEquals(3, $result->count);
        $this->assertEquals($expected, $result->results);
    }

    public function testGetRelatedResourcesCountOnlyNoSkipNoTake()
    {
        $mockResource = \Mockery::mock(ResourceSet::class);

        $queryType = QueryType::COUNT();

        $property = \Mockery::mock(ResourceProperty::class);
        $property->shouldReceive('getName')->withNoArgs()->andReturn('morphTarget');

        $rawResult = \Mockery::mock(\Illuminate\Database\Eloquent\Builder::class);
        $rawResult->shouldReceive('get')->andReturn(collect(['eins', 'zwei', 'polizei']));

        $query        = new QueryResult();
        $query->count = 3;

        $sourceEntity = \Mockery::mock(TestMorphManySource::class)->makePartial();

        $auth   = new NullAuthProvider();
        $reader = m::mock(LaravelReadQuery::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $reader->shouldReceive('getResourceSet')->withAnyArgs()->andReturn($query);
        $reader->shouldReceive('getAuth')->andReturn($auth);
        $foo = \Mockery::mock(LaravelQuery::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getReader')->andReturn($reader);
        $result = $foo->getRelatedResourceSet($queryType, $mockResource, $sourceEntity, $mockResource, $property);
        $this->assertTrue($result instanceof QueryResult);
        $this->assertEquals(3, $result->count);
        $this->assertEquals(null, $result->results);
    }

    public function testGetRelatedResourcesCountOnlyTwoSkipTwoTakeWithOneResultingRecord()
    {
        $mockResource = \Mockery::mock(ResourceSet::class);

        $queryType = QueryType::COUNT();

        $property = \Mockery::mock(ResourceProperty::class);
        $property->shouldReceive('getName')->withNoArgs()->andReturn('morphTarget');

        $finalResult = \Mockery::mock(\Illuminate\Database\Eloquent\Builder::class);
        $finalResult->shouldReceive('get')->andReturn(collect(['eins', 'zwei', 'polizei']));
        $finalResult->shouldReceive('count')->andReturn(3);
        $finalResult->shouldReceive('slice')->withArgs([2])->andReturn(collect(['polizei']));

        $rawResult = \Mockery::mock(\Illuminate\Database\Eloquent\Builder::class);
        $rawResult->shouldReceive('get')->withAnyArgs()->andReturn($finalResult);
        $rawResult->shouldReceive('getRelated')->andReturn(TestMorphTarget::class);

        $query        = new QueryResult();
        $query->count = 3;

        $sourceEntity = \Mockery::mock(TestMorphManySource::class)->makePartial();
        $sourceEntity->shouldReceive('morphTarget')->andReturn($rawResult);

        $auth   = new NullAuthProvider();
        $reader = m::mock(LaravelReadQuery::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $reader->shouldReceive('getResourceSet')->withAnyArgs()->andReturn($query);
        $reader->shouldReceive('getAuth')->andReturn($auth);
        $foo = \Mockery::mock(LaravelQuery::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getReader')->andReturn($reader);
        $result = $foo->getRelatedResourceSet(
            $queryType,
            $mockResource,
            $sourceEntity,
            $mockResource,
            $property,
            null,
            null,
            2,
            2
        );
        $this->assertTrue($result instanceof QueryResult);
        $this->assertEquals(3, $result->count);
        $this->assertEquals(null, $result->results);
    }

    public function testGetResourceFromRelatedResourceSetNonNullSourceInstanceMissingPropertyThrowException()
    {
        $srcResource = m::mock(ResourceSet::class);
        $dstResource = m::mock(ResourceSet::class);
        $property    = m::mock(ResourceProperty::class);
        $property->shouldReceive('getName')->andReturn('name');
        $key = m::mock(KeyDescriptor::class);
        $key->shouldReceive('getValidatedNamedValues')->andReturn([])->never();
        $sourceEntity = \Mockery::mock(TestMorphManySource::class)->makePartial();
        $sourceEntity->shouldReceive('where')->andReturnSelf();

        $foo = new LaravelQuery();

        $expected = 'Relation method, name, does not exist on supplied entity.';
        $actual   = null;

        try {
            $foo->getResourceFromRelatedResourceSet($srcResource, $sourceEntity, $dstResource, $property, $key);
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetResourceFromRelatedResourceSetNonNullSourceInstanceRemix()
    {
        $srcResource = m::mock(ResourceSet::class);
        $dstResource = m::mock(ResourceSet::class);
        $property    = m::mock(ResourceProperty::class);
        $property->shouldReceive('getName')->andReturn('morphTarget');
        $key = m::mock(KeyDescriptor::class);
        $key->shouldReceive('getValidatedNamedValues')->andReturn(['a' => 'b'])->once();

        $finalResult = \Mockery::mock(\Illuminate\Database\Eloquent\Builder::class);
        $finalResult->shouldReceive('get')->andReturn(collect(['eins', 'zwei', 'polizei']));
        $finalResult->shouldReceive('count')->andReturn(3);
        $finalResult->shouldReceive('slice')->withArgs([2])->andReturn(collect(['polizei']));

        $rawResult = \Mockery::mock(\Illuminate\Database\Eloquent\Builder::class);
        $rawResult->shouldReceive('get')->withAnyArgs()->andReturn($finalResult);
        $rawResult->shouldReceive('getRelated')->andReturn(TestMorphTarget::class);

        $targSource = m::mock(TestMorphTarget::class)->makePartial();
        $targSource->shouldReceive('getKey')->andReturn('theSecret')->atLeast(1);


        $sourceEntity = \Mockery::mock(TestMorphManySource::class)->makePartial();
        $sourceEntity->shouldReceive('where')->andReturnSelf();
        $sourceEntity->shouldReceive('orderBy')->andReturnSelf();
        $sourceEntity->shouldReceive('morphTarget')->andReturnSelf()->once();
        $sourceEntity->shouldReceive('get')->andReturn(collect([$targSource]))->once();

        $foo = new LaravelQuery();

        $result = $foo->getResourceFromRelatedResourceSet($srcResource, $sourceEntity, $dstResource, $property, $key);
        $this->assertTrue($result instanceof TestMorphTarget);
    }

    public function testGetResourceWithNullResourceSetAndEntityInstance()
    {
        $foo = new LaravelReadQuery();

        $expected = 'Must supply at least one of a resource set and source entity.';
        $actual   = null;

        try {
            $foo->getResource();
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers \AlgoWeb\PODataLaravel\Query\LaravelQuery::getRelatedResourceReference
     */
    public function testGetRelatedResourceReference()
    {
        $srcResource = m::mock(ResourceSet::class);
        $dstResource = m::mock(ResourceSet::class);
        $property    = m::mock(ResourceProperty::class);
        $property->shouldReceive('getName')->andReturn('morphTarget');

        $finalResult = \Mockery::mock(\Illuminate\Database\Eloquent\Builder::class);
        $finalResult->shouldReceive('get')->andReturn(collect(['eins', 'zwei', 'polizei']));
        $finalResult->shouldReceive('count')->andReturn(3);
        $finalResult->shouldReceive('slice')->withArgs([2])->andReturn(collect(['polizei']));

        $rawResult = \Mockery::mock(\Illuminate\Database\Eloquent\Builder::class);
        $rawResult->shouldReceive('get')->withAnyArgs()->andReturn($finalResult);
        $rawResult->shouldReceive('getRelated')->andReturn(TestMorphTarget::class);

        $sourceEntity = \Mockery::mock(TestMorphManySource::class)->makePartial();
        $sourceEntity->shouldReceive('where')->andReturnSelf();
        $sourceEntity->shouldReceive('orderBy')->andReturnSelf();
        $sourceEntity->shouldReceive('morphTarget')->andReturnSelf()->once();
        $sourceEntity->shouldReceive('first')->andReturnSelf()->once();
        $property->shouldReceive('getResourceType->getInstanceType->getName')->andReturn(get_class($sourceEntity));

        $foo = new LaravelQuery();

        $result = $foo->getRelatedResourceReference($srcResource, $sourceEntity, $dstResource, $property);
    }

    public function testGetRelatedResourceReferenceWithValidGubbins()
    {
        $mod1       = new TestModel();
        $mod1->name = 'Hammer, MC';

        $model = m::mock(TestModel::class)->makePartial();
        $model->shouldReceive('name->first')->andReturn($mod1);

        $source   = m::mock(ResourceSet::class);
        $targ     = m::mock(ResourceSet::class);
        $property = m::mock(ResourceProperty::class);
        $property->shouldReceive('getName')->andReturn('name');
        $property->shouldReceive('getResourceType->getInstanceType->getName')->andReturn(TestModel::class);

        $foo    = new LaravelQuery();
        $result = $foo->getRelatedResourceReference($source, $model, $targ, $property);
        $this->assertEquals($mod1->name, $result->name);
    }

    public function testGetRelatedResourceReferenceWithInValidGubbins()
    {
        $mod1       = new TestModel();
        $mod1->name = 'Hammer, MC';

        $model = m::mock(TestModel::class)->makePartial();
        $model->shouldReceive('name->first')->andReturn($mod1);

        $source   = m::mock(ResourceSet::class);
        $targ     = m::mock(ResourceSet::class);
        $property = m::mock(ResourceProperty::class);
        $property->shouldReceive('getName')->andReturn('name');
        $property->shouldReceive('getResourceType->getInstanceType->getName')->andReturn('name');

        $foo    = new LaravelQuery();
        $result = $foo->getRelatedResourceReference($source, $model, $targ, $property);
        $this->assertEquals(null, $result);
    }

    public function testGetRelatedResourceReferenceWithExpandedPropertyName()
    {
        $mod1       = new TestModel();
        $mod1->name = 'Hammer, MC';

        $model = m::mock(TestModel::class)->makePartial();
        $model->shouldReceive('noname->first')->andReturn(null);

        $source   = m::mock(ResourceSet::class);
        $targ     = m::mock(ResourceSet::class);
        $property = m::mock(ResourceProperty::class);
        $property->shouldReceive('getName')->andReturn('noname_unpack')->once();
        $property->shouldReceive('getResourceType->getInstanceType->getName')->andReturn('name')->never();

        $foo    = new LaravelQuery();
        $result = $foo->getRelatedResourceReference($source, $model, $targ, $property);
        $this->assertEquals(null, $result);
    }

    public function testAttemptUpdate()
    {
        $controller = new TestController();

        $testName = TestController::class;

        $this->seedControllerMetadata($controller);

        $metaProv = new SimpleMetadataProvider('Data', 'Data');

        App::instance('metadata', $metaProv);

        $std = m::mock(IType::class);
        $std->shouldReceive('getName')->andReturn(TestModel::class);
        $mockResource = \Mockery::mock(ResourceSet::class);
        $mockResource->shouldReceive('getResourceType->getInstanceType')->andReturn($std);
        $model          = new TestModel();
        $model->id      = 42;
        $keyDesc        = \Mockery::mock(KeyDescriptor::class);
        $data           = new \StdClass();
        $data->name     = 'Wibble';
        $data->added_at = new \DateTime();
        $data->weight   = 0;
        $data->code     = 'Enigma';
        $data->success  = false;
        $data           = (array) $data;
        $shouldUpdate   = false;

        $foo          = new LaravelQuery();
        $expected     = 'Target model not successfully updated';
        $actual       = '';
        $expectedCode = 422;
        $actualCode   = null;
        try {
            $result = $foo->updateResource($mockResource, $model, $keyDesc, $data);
        } catch (ODataException $e) {
            $actual     = $e->getMessage();
            $actualCode = $e->getStatusCode();
        }
        $this->assertEquals($expected, $actual);
        $this->assertEquals($expectedCode, $actualCode);
    }

    public function testAttemptCreatePermissionDenied()
    {
        $auth = m::mock(AuthInterface::class);
        $auth->shouldReceive('canAuth')->withAnyArgs()->andReturn(false)->once();
        $type         = new Binary();
        $mockResource = m::mock(ResourceSet::class);
        $mockResource->shouldReceive('getResourceType->getInstanceType')->andReturn($type);
        $model = m::mock(Model::class);
        $data  = null;

        $foo          = new LaravelQuery($auth);
        $expected     = 'Access denied';
        $actual       = null;
        $expectedCode = 403;
        $actualCode   = null;

        try {
            $result = $foo->createResourceforResourceSet($mockResource, $model, $data);
        } catch (ODataException $e) {
            $actual     = $e->getMessage();
            $actualCode = $e->getStatusCode();
        }
        $this->assertEquals($expected, $actual);
        $this->assertEquals($expectedCode, $actualCode);
    }

    public function testAttemptCreate()
    {
        $controller = new TestController();

        $testName = TestController::class;

        $this->seedControllerMetadata($controller);

        $metaProv = new SimpleMetadataProvider('Data', 'Data');

        App::instance('metadata', $metaProv);

        $std = m::mock(IType::class);
        $std->shouldReceive('getName')->andReturn(TestModel::class);
        $mockResource = \Mockery::mock(ResourceSet::class);
        $mockResource->shouldReceive('getResourceType->getInstanceType')->andReturn($std);
        $model          = new TestModel();
        $model->id      = 42;
        $keyDesc        = \Mockery::mock(KeyDescriptor::class);
        $data           = new \StdClass();
        $data->name     = 'Wibble';
        $data->added_at = new \DateTime();
        $data->weight   = 0;
        $data->code     = 'Enigma';
        $data->success  = false;
        $shouldUpdate   = false;
        $data           = (array) $data;

        $foo      = new LaravelQuery();
        $expected = 'Target model not successfully created';
        $actual   = '';
        try {
            $result = $foo->createResourceForResourceSet($mockResource, $model, $data);
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testAttemptCreateNonResolvableData()
    {
        $controller = new TestController();

        $testName = TestController::class;

        $this->seedControllerMetadata($controller);

        $metaProv = new SimpleMetadataProvider('Data', 'Data');

        App::instance('metadata', $metaProv);

        $std = m::mock(IType::class);
        $std->shouldReceive('getName')->andReturn(TestModel::class);
        $mockResource = \Mockery::mock(ResourceSet::class);
        $mockResource->shouldReceive('getResourceType->getInstanceType')->andReturn($std);
        $model        = new TestModel();
        $model->id    = 42;
        $keyDesc      = \Mockery::mock(KeyDescriptor::class);
        $data         = 'Wibble';
        $shouldUpdate = false;

        $this->expectException(\Throwable::class);

        $foo    = new LaravelQuery();
        $result = $foo->createResourceForResourceSet($mockResource, $model, $data);
    }

    public function testAttemptUpdateBadIdThrowException()
    {
        $controller = new TestController();

        $json = m::mock(JsonResponse::class)->makePartial();
        $json->shouldReceive('getData')->andReturn(['id' => -1, 'status' => 'success', 'errors' => null])->once();

        $testName       = TestController::class;
        $mockController = m::mock($testName)->makePartial();
        $mockController->shouldReceive('updateTestModel')->withAnyArgs()->andReturn($json)->once();

        $this->seedControllerMetadata($controller);

        $metaProv = new SimpleMetadataProvider('Data', 'Data');

        App::instance('metadata', $metaProv);
        App::instance($testName, $mockController);

        $std = m::mock(IType::class);
        $std->shouldReceive('getName')->andReturn(TestModel::class);
        $mockResource = \Mockery::mock(ResourceSet::class);
        $mockResource->shouldReceive('getResourceType->getInstanceType')->andReturn($std);
        $model     = new TestModel();
        $model->id = null;

        $data           = new \StdClass();
        $data->name     = 'Wibble';
        $data->added_at = new \DateTime();
        $data->weight   = 0;
        $data->code     = 'Enigma';
        $data->success  = false;
        $data           = (array) $data;
        $key            = m::mock(KeyDescriptor::class);

        $foo          = new LaravelQuery();
        $expected     = 'No query results for model ['. \Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestModel::class . ']';
        $actual       = null;
        $expectedCode = 500;
        $actualCode   = null;
        try {
            $result = $foo->updateResource($mockResource, $model, $key, $data);
        } catch (ODataException $e) {
            $actual     = $e->getMessage();
            $actualCode = $e->getStatusCode();
        }
        $this->assertStringStartsWith($expected, $actual);
        $this->assertEquals($expectedCode, $actualCode);
    }


    public function testAttemptDeleteBadSourceInstanceThrowException()
    {
        $mockResource = m::mock(ResourceSet::class);
        $model        = new \DateTime();

        $foo      = new LaravelQuery();
        $expected = 'Source entity must be an Eloquent model.';
        $actual   = null;
        try {
            $result = $foo->deleteResource($mockResource, $model);
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testAttemptDelete()
    {
        $controller = new TestController();

        $testName = TestController::class;

        $this->seedControllerMetadata($controller);

        $metaProv = new SimpleMetadataProvider('Data', 'Data');

        App::instance('metadata', $metaProv);

        $std = m::mock(IType::class);
        $std->shouldReceive('getName')->andReturn(TestModel::class);
        $mockResource = \Mockery::mock(ResourceSet::class);
        $mockResource->shouldReceive('getResourceType->getInstanceType')->andReturn($std);
        $model          = new TestModel();
        $model->id      = null;
        $keyDesc        = \Mockery::mock(KeyDescriptor::class);
        $data           = new \StdClass();
        $data->name     = 'Wibble';
        $data->added_at = new \DateTime();
        $data->weight   = 0;
        $data->code     = 'Enigma';
        $data->success  = false;
        $shouldUpdate   = false;

        $foo          = new LaravelQuery();
        $expected     = 'Target model not successfully deleted';
        $actual       = '';
        $expectedCode = 422;
        $actualCode   = null;
        try {
            $result = $foo->deleteResource($mockResource, $model);
        } catch (ODataException $e) {
            $actual     = $e->getMessage();
            $actualCode = $e->getStatusCode();
        }
        $this->assertEquals($expected, $actual);
        $this->assertEquals($expectedCode, $actualCode);
    }

    public function testAttemptDeleteIncompleteResponseData()
    {
        $controller = new TestController();

        $json = m::mock(JsonResponse::class)->makePartial();
        $json->shouldReceive('getData')->andReturn([])->once();

        $testName       = TestController::class;
        $mockController = m::mock($testName)->makePartial();
        $mockController->shouldReceive('destroyTestModel')->withAnyArgs()->andReturn($json)->once();

        $this->seedControllerMetadata($controller);

        $metaProv = new SimpleMetadataProvider('Data', 'Data');

        App::instance('metadata', $metaProv);
        App::instance($testName, $mockController);

        $std = m::mock(IType::class);
        $std->shouldReceive('getName')->andReturn(TestModel::class);
        $mockResource = \Mockery::mock(ResourceSet::class);
        $mockResource->shouldReceive('getResourceType->getInstanceType')->andReturn($std);
        $model     = new TestModel();
        $model->id = null;

        $foo          = new LaravelQuery();
        $expected     = 'Controller response array missing at least one of id, status and/or errors fields.';
        $actual       = null;
        $expectedCode = 500;
        $actualCode   = null;
        try {
            $result = $foo->deleteResource($mockResource, $model);
        } catch (ODataException $e) {
            $actual     = $e->getMessage();
            $actualCode = $e->getStatusCode();
        }
        $this->assertEquals($expected, $actual);
        $this->assertEquals($expectedCode, $actualCode);
    }

    public function testAttemptDeleteSuccessful()
    {
        $controller = new TestController();

        $json = m::mock(JsonResponse::class)->makePartial();
        $json->shouldReceive('getData')->andReturn(['id' => 0, 'status' => null, 'errors' => null])->once();

        $testName       = TestController::class;
        $mockController = m::mock($testName)->makePartial();
        $mockController->shouldReceive('destroyTestModel')->withAnyArgs()->andReturn($json)->once();

        $this->seedControllerMetadata($controller);

        $metaProv = new SimpleMetadataProvider('Data', 'Data');

        App::instance('metadata', $metaProv);
        App::instance($testName, $mockController);

        $std = m::mock(IType::class);
        $std->shouldReceive('getName')->andReturn(TestModel::class);
        $mockResource = \Mockery::mock(ResourceSet::class);
        $mockResource->shouldReceive('getResourceType->getInstanceType')->andReturn($std);
        $model     = new TestModel();
        $model->id = null;

        $foo    = new LaravelQuery();
        $result = $foo->deleteResource($mockResource, $model);
        $this->assertTrue($result);
    }

    public function testAttemptDeleteIncompleteResponseDataOnlyHasId()
    {
        $controller = new TestController();

        $json = m::mock(JsonResponse::class)->makePartial();
        $json->shouldReceive('getData')->andReturn(['id' => null])->once();

        $testName       = TestController::class;
        $mockController = m::mock($testName)->makePartial();
        $mockController->shouldReceive('destroyTestModel')->withAnyArgs()->andReturn($json)->once();

        $this->seedControllerMetadata($controller);

        $metaProv = new SimpleMetadataProvider('Data', 'Data');

        App::instance('metadata', $metaProv);
        App::instance($testName, $mockController);

        $std = m::mock(IType::class);
        $std->shouldReceive('getName')->andReturn(TestModel::class);
        $mockResource = \Mockery::mock(ResourceSet::class);
        $mockResource->shouldReceive('getResourceType->getInstanceType')->andReturn($std);
        $model     = new TestModel();
        $model->id = null;

        $foo          = new LaravelQuery();
        $expected     = 'Controller response array missing at least one of id, status and/or errors fields.';
        $actual       = null;
        $expectedCode = 500;
        $actualCode   = null;
        try {
            $result = $foo->deleteResource($mockResource, $model);
        } catch (ODataException $e) {
            $actual     = $e->getMessage();
            $actualCode = $e->getStatusCode();
        }
        $this->assertEquals($expected, $actual);
        $this->assertEquals($expectedCode, $actualCode);
    }

    public function testAttemptDeleteIncompleteResponseDataOnlyHasStatus()
    {
        $controller = new TestController();

        $json = m::mock(JsonResponse::class)->makePartial();
        $json->shouldReceive('getData')->andReturn(['status' => null])->once();

        $testName       = TestController::class;
        $mockController = m::mock($testName)->makePartial();
        $mockController->shouldReceive('destroyTestModel')->withAnyArgs()->andReturn($json)->once();

        $this->seedControllerMetadata($controller);

        $metaProv = new SimpleMetadataProvider('Data', 'Data');

        App::instance('metadata', $metaProv);
        App::instance($testName, $mockController);

        $std = m::mock(IType::class);
        $std->shouldReceive('getName')->andReturn(TestModel::class);
        $mockResource = \Mockery::mock(ResourceSet::class);
        $mockResource->shouldReceive('getResourceType->getInstanceType')->andReturn($std);
        $model     = new TestModel();
        $model->id = null;

        $foo          = new LaravelQuery();
        $expected     = 'Controller response array missing at least one of id, status and/or errors fields.';
        $actual       = null;
        $expectedCode = 500;
        $actualCode   = null;
        try {
            $result = $foo->deleteResource($mockResource, $model);
        } catch (ODataException $e) {
            $actual     = $e->getMessage();
            $actualCode = $e->getStatusCode();
        }
        $this->assertEquals($expected, $actual);
        $this->assertEquals($expectedCode, $actualCode);
    }

    public function testAttemptDeleteIncompleteResponseDataOnlyHasErrors()
    {
        $controller = new TestController();

        $json = m::mock(JsonResponse::class)->makePartial();
        $json->shouldReceive('getData')->andReturn(['errors' => null])->once();

        $testName       = TestController::class;
        $mockController = m::mock($testName)->makePartial();
        $mockController->shouldReceive('destroyTestModel')->withAnyArgs()->andReturn($json)->once();

        $this->seedControllerMetadata($controller);

        $metaProv = new SimpleMetadataProvider('Data', 'Data');

        App::instance('metadata', $metaProv);
        App::instance($testName, $mockController);

        $std = m::mock(IType::class);
        $std->shouldReceive('getName')->andReturn(TestModel::class);
        $mockResource = \Mockery::mock(ResourceSet::class);
        $mockResource->shouldReceive('getResourceType->getInstanceType')->andReturn($std);
        $model     = new TestModel();
        $model->id = null;

        $foo          = new LaravelQuery();
        $expected     = 'Controller response array missing at least one of id, status and/or errors fields.';
        $actual       = null;
        $expectedCode = 500;
        $actualCode   = null;
        try {
            $result = $foo->deleteResource($mockResource, $model);
        } catch (ODataException $e) {
            $actual     = $e->getMessage();
            $actualCode = $e->getStatusCode();
        }
        $this->assertEquals($expected, $actual);
        $this->assertEquals($expectedCode, $actualCode);
    }

    public function testAttemptDeleteWithControllerMappingMissing()
    {
        $controller = new TestController();

        $json = m::mock(JsonResponse::class)->makePartial();
        $json->shouldReceive('getData')->andReturn([])->never();

        $testName       = TestController::class;
        $mockController = m::mock($testName)->makePartial();
        $mockController->shouldReceive('destroyTestModel')->withAnyArgs()->andReturn($json)->never();

        $this->seedControllerMetadata($controller);

        $metaProv = new SimpleMetadataProvider('Data', 'Data');

        $container = m::mock(MetadataControllerContainer::class)->makePartial();
        $container->shouldReceive('getMetadata')->andReturn([])->once();
        App::instance('metadata', $metaProv);
        App::instance($testName, $mockController);
        App::instance('metadataControllers', $container);

        $std = m::mock(IType::class);
        $std->shouldReceive('getName')->andReturn(TestModel::class);
        $mockResource = \Mockery::mock(ResourceSet::class);
        $mockResource->shouldReceive('getResourceType->getInstanceType')->andReturn($std);
        $model     = new TestModel();
        $model->id = null;

        $foo      = new LaravelQuery();
        $expected = 'Controller mapping missing for class ' . \Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestModel::class . '.';
        $actual   = null;

        try {
            $result = $foo->deleteResource($mockResource, $model);
        } catch (InvalidOperationException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testAttemptDeleteWithControllerVerbMappingMissing()
    {
        $controller = new TestController();

        $json = m::mock(JsonResponse::class)->makePartial();
        $json->shouldReceive('getData')->andReturn([])->never();

        $testName       = TestController::class;
        $mockController = m::mock($testName)->makePartial();
        $mockController->shouldReceive('destroyTestModel')->withAnyArgs()->andReturn($json)->never();

        $this->seedControllerMetadata($controller);

        $metaProv = new SimpleMetadataProvider('Data', 'Data');

        $container = m::mock(MetadataControllerContainer::class)->makePartial();
        $container->shouldReceive('getMetadata')->andReturn([TestModel::class => ''])->once();
        $container->shouldReceive('getMapping')->withAnyArgs()->andReturn(null)->once();
        App::instance('metadata', $metaProv);
        App::instance($testName, $mockController);
        App::instance('metadataControllers', $container);

        $std = m::mock(IType::class);
        $std->shouldReceive('getName')->andReturn(TestModel::class);
        $mockResource = \Mockery::mock(ResourceSet::class);
        $mockResource->shouldReceive('getResourceType->getInstanceType')->andReturn($std);
        $model     = new TestModel();
        $model->id = null;

        $foo      = new LaravelQuery();
        $expected = 'Controller mapping missing for delete verb on class ' . \Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestModel::class . '.';
        $actual   = null;

        try {
            $result = $foo->deleteResource($mockResource, $model);
        } catch (InvalidOperationException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testPutResource()
    {
        $resource = \Mockery::mock(ResourceSet::class);
        $key      = m::mock(KeyDescriptor::class);

        $foo = new LaravelQuery();
        $this->assertTrue($foo->putResource($resource, $key, []));
    }

    public function testUnpackSourceEntityInstanceFromSingleton()
    {
        $source = m::mock(ResourceSet::class);
        $target = m::mock(ResourceSet::class);
        $rProp  = m::mock(ResourceProperty::class);
        $key    = m::mock(KeyDescriptor::class);

        $entity          = new QueryResult();
        $entity->results = m::mock(TestModel::class);

        $reader = m::mock(LaravelReadQuery::class);
        // Set things up to respond if an only if the ReadQuery call receives an Eloquent model, not a QueryResult
        $reader->shouldReceive('getResourceFromRelatedResourceSet')
            ->with($source, m::type(Model::class), $target, $rProp, $key)
            ->andReturn(null)->once();

        $foo = m::mock(LaravelQuery::class)->makePartial();
        $foo->shouldReceive('getReader')->andReturn($reader);

        $result = $foo->getResourceFromRelatedResourceSet($source, $entity, $target, $rProp, $key);
        $this->assertNull($result);
    }

    public function testUnpackSourceEntityInstanceFromArray()
    {
        $source = m::mock(ResourceSet::class);
        $target = m::mock(ResourceSet::class);
        $rProp  = m::mock(ResourceProperty::class);
        $key    = m::mock(KeyDescriptor::class);

        $entity          = new QueryResult();
        $entity->results = [m::mock(TestModel::class)];

        $reader = m::mock(LaravelReadQuery::class);
        // Set things up to respond if an only if the ReadQuery call receives an Eloquent model, not a QueryResult
        $reader->shouldReceive('getResourceFromRelatedResourceSet')
            ->with($source, m::type(Model::class), $target, $rProp, $key)
            ->andReturn(null)->once();

        $foo = m::mock(LaravelQuery::class)->makePartial();
        $foo->shouldReceive('getReader')->andReturn($reader);

        $result = $foo->getResourceFromRelatedResourceSet($source, $entity, $target, $rProp, $key);
        $this->assertNull($result);
    }

    public function testHookSingleModelWithBadRelation()
    {
        $source       = m::mock(ResourceSet::class);
        $target       = m::mock(ResourceSet::class);
        $srcInstance  = $this->generateTestModelWithMetadata();
        $targInstance = $this->generateTestModelWithMetadata();
        $navPropName  = 'metadata';

        $hook = m::mock(LaravelHookQuery::class)->makePartial();
        $foo  = m::mock(LaravelQuery::class)->makePartial();
        $foo->shouldReceive('getModelHook')->andReturn($hook);

        $expected = 'Navigation property must be an Eloquent relation';
        $actual   = null;

        try {
            $foo->hookSingleModel($source, $srcInstance, $target, $targInstance, $navPropName);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testHookSingleModelWithMispointedRelation()
    {
        $meta             = [];
        $meta['id']       = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $meta['name']     = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $meta['added_at'] = ['type' => 'datetime', 'nullable' => true, 'fillable' => true, 'default' => null];
        $meta['weight']   = ['type' => 'integer', 'nullable' => true, 'fillable' => true, 'default' => null];
        $meta['code']     = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];

        $source       = m::mock(ResourceSet::class);
        $target       = m::mock(ResourceSet::class);
        $srcInstance  = new TestMonomorphicSource($meta);
        $targInstance = new TestMonomorphicSource($meta);
        $navPropName  = 'manySource';

        $hook = m::mock(LaravelHookQuery::class)->makePartial();
        $foo  = m::mock(LaravelQuery::class)->makePartial();
        $foo->shouldReceive('getModelHook')->andReturn($hook);

        $expected = 'Target instance must be of type compatible with relation declared in method '.$navPropName;
        $actual   = null;

        try {
            $foo->hookSingleModel($source, $srcInstance, $target, $targInstance, $navPropName);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testHookSingleModelOneToOneOrMany()
    {
        $meta             = [];
        $meta['id']       = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $meta['name']     = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $meta['added_at'] = ['type' => 'datetime', 'nullable' => true, 'fillable' => true, 'default' => null];
        $meta['weight']   = ['type' => 'integer', 'nullable' => true, 'fillable' => true, 'default' => null];
        $meta['code']     = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];

        $source = m::mock(ResourceSet::class);
        $target = m::mock(ResourceSet::class);

        $relInstance = new TestMonomorphicTarget($meta);
        $hasMany     = m::mock(HasMany::class)->makePartial();
        $hasMany->shouldReceive('getRelated')->andReturn($relInstance);
        $hasMany->shouldReceive('save')->andReturn(null)->once();
        $srcInstance  = m::mock(TestMonomorphicSource::class)->makePartial();
        $targInstance = new TestMonomorphicTarget($meta);
        $navPropName  = 'manySource';

        $hook = m::mock(LaravelHookQuery::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $hook->shouldReceive('isModelHookInputsOk')->andReturn($hasMany);

        $foo = m::mock(LaravelQuery::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getModelHook')->andReturn($hook);

        $foo->hookSingleModel($source, $srcInstance, $target, $targInstance, $navPropName);
    }

    public function testHookSingleModelMorphOneToOneOrMany()
    {
        $meta             = [];
        $meta['id']       = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $meta['name']     = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $meta['added_at'] = ['type' => 'datetime', 'nullable' => true, 'fillable' => true, 'default' => null];
        $meta['weight']   = ['type' => 'integer', 'nullable' => true, 'fillable' => true, 'default' => null];
        $meta['code']     = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];

        $source = m::mock(ResourceSet::class);
        $target = m::mock(ResourceSet::class);

        $relInstance = new TestMorphTarget($meta);
        $morphOne    = m::mock(MorphOne::class)->makePartial();
        $morphOne->shouldReceive('getRelated')->andReturn($relInstance);
        $morphOne->shouldReceive('save')->andReturn(null)->once();
        $srcInstance  = m::mock(TestMorphOneSource::class)->makePartial();
        $targInstance = new TestMorphTarget($meta);
        $navPropName  = 'morphTarget';

        $hook = m::mock(LaravelHookQuery::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $hook->shouldReceive('isModelHookInputsOk')->andReturn($morphOne);

        $foo = m::mock(LaravelQuery::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getModelHook')->andReturn($hook);

        $foo->hookSingleModel($source, $srcInstance, $target, $targInstance, $navPropName);
    }

    public function testHookSingleModelMorphManyToMany()
    {
        $meta             = [];
        $meta['id']       = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $meta['name']     = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $meta['added_at'] = ['type' => 'datetime', 'nullable' => true, 'fillable' => true, 'default' => null];
        $meta['weight']   = ['type' => 'integer', 'nullable' => true, 'fillable' => true, 'default' => null];
        $meta['code']     = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];

        $source = m::mock(ResourceSet::class);
        $target = m::mock(ResourceSet::class);

        $relInstance = new TestMorphManyToManyTarget($meta);
        $morphToMany = m::mock(MorphToMany::class)->makePartial();
        $morphToMany->shouldReceive('getRelated')->andReturn($relInstance);
        $morphToMany->shouldReceive('attach')->andReturn(null)->once();
        $srcInstance  = m::mock(TestMorphManyToManySource::class)->makePartial();
        $targInstance = new TestMorphManyToManyTarget($meta);
        $navPropName  = 'morphTarget';

        $hook = m::mock(LaravelHookQuery::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $hook->shouldReceive('isModelHookInputsOk')->andReturn($morphToMany);

        $foo = m::mock(LaravelQuery::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getModelHook')->andReturn($hook);

        $this->assertTrue($foo->hookSingleModel($source, $srcInstance, $target, $targInstance, $navPropName));
    }

    public function testHookSingleModelFromKnownPolymorphicSide()
    {
        $meta             = [];
        $meta['id']       = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $meta['name']     = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $meta['added_at'] = ['type' => 'datetime', 'nullable' => true, 'fillable' => true, 'default' => null];
        $meta['weight']   = ['type' => 'integer', 'nullable' => true, 'fillable' => true, 'default' => null];
        $meta['code']     = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];

        $source = m::mock(ResourceSet::class);
        $target = m::mock(ResourceSet::class);

        $relInstance = new TestMorphOneSource($meta);
        $morphOne    = m::mock(MorphTo::class)->makePartial();
        $morphOne->shouldReceive('getRelated')->andReturn($relInstance);
        $morphOne->shouldReceive('associate')->andReturn(null)->once();
        $srcInstance  = m::mock(TestMorphTarget::class)->makePartial();
        $targInstance = new TestMorphOneSource($meta);
        $navPropName  = 'morphTarget';

        $hook = m::mock(LaravelHookQuery::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $hook->shouldReceive('isModelHookInputsOk')->andReturn($morphOne);

        $foo = m::mock(LaravelQuery::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getModelHook')->andReturn($hook);

        $this->assertTrue($foo->hookSingleModel($source, $srcInstance, $target, $targInstance, $navPropName));
    }

    public function testUnhookSingleModelFromParentSide()
    {
        $meta             = [];
        $meta['id']       = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $meta['name']     = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $meta['added_at'] = ['type' => 'datetime', 'nullable' => true, 'fillable' => true, 'default' => null];
        $meta['weight']   = ['type' => 'integer', 'nullable' => true, 'fillable' => true, 'default' => null];
        $meta['code']     = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];

        $source = m::mock(ResourceSet::class);
        $target = m::mock(ResourceSet::class);

        $relParent = new TestMonomorphicTarget($meta);
        $relChild  = new TestMonomorphicSource($meta);

        $hasMany = m::mock(HasMany::class)->makePartial();
        $hasMany->shouldReceive('getRelated')->andReturn($relParent)->once();

        $belongsTo = m::mock(BelongsTo::class)->makePartial();
        $belongsTo->shouldReceive('getRelated')->andReturn($relChild)->once();
        $belongsTo->shouldReceive('dissociate')->andReturn(null)->once();

        $parent = m::mock(TestMonomorphicSource::class)->makePartial();
        $parent->shouldReceive('manySource')->andReturn($hasMany)->atLeast(1);
        $child = m::mock(TestMonomorphicTarget::class)->makePartial();
        $child->shouldReceive('manyTarget')->andReturn($belongsTo)->atLeast(1);

        $parentNavName = 'manySource';
        $childNavName  = 'manyTarget';

        $metaProv = m::mock(MetadataProvider::class);
        $metaProv->shouldReceive('resolveReverseProperty')->andReturn($childNavName);

        $hook = m::mock(LaravelHookQueryDummy::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $hook->setMetadataProvider($metaProv);

        $foo = m::mock(LaravelQueryDummy::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->setModelHook($hook);
        $this->assertTrue($foo->unhookSingleModel($source, $parent, $target, $child, $parentNavName));
    }

    public function testUnhookModelWithUnresolvableOppositeRelation()
    {
        $meta             = [];
        $meta['id']       = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $meta['name']     = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $meta['added_at'] = ['type' => 'datetime', 'nullable' => true, 'fillable' => true, 'default' => null];
        $meta['weight']   = ['type' => 'integer', 'nullable' => true, 'fillable' => true, 'default' => null];
        $meta['code']     = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];

        $source = m::mock(ResourceSet::class);
        $target = m::mock(ResourceSet::class);

        $hasMany = m::mock(HasMany::class)->makePartial();

        $parent = new TestMonomorphicSource($meta);
        $child  = new TestMonomorphicTarget($meta);

        $metaProv = m::mock(MetadataProvider::class);
        $metaProv->shouldReceive('resolveReverseProperty')->andReturn(null);

        $hook = m::mock(LaravelHookQuery::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $hook->shouldReceive('getMetadataProvider')->andReturn($metaProv);
        $hook->shouldReceive('isModelHookInputsOk')->andReturn($hasMany);

        $foo = m::mock(LaravelQuery::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getMetadataProvider')->andReturn($metaProv);
        $foo->shouldReceive('getModelHook')->andReturn($hook);

        $expected = 'Bad navigation property, manySource, on source model '
                    . \Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMonomorphicSource::class;
        $actual = null;

        try {
            $foo->unhookSingleModel($source, $parent, $target, $child, 'manySource');
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testUnhookModelManyToMany()
    {
        $meta             = [];
        $meta['id']       = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $meta['name']     = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $meta['added_at'] = ['type' => 'datetime', 'nullable' => true, 'fillable' => true, 'default' => null];
        $meta['weight']   = ['type' => 'integer', 'nullable' => true, 'fillable' => true, 'default' => null];
        $meta['code']     = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];

        $source = m::mock(ResourceSet::class);
        $target = m::mock(ResourceSet::class);

        $srcInstance  = new TestMorphManyToManySource($meta);
        $targInstance = new TestMorphManyToManyTarget($meta);

        $manyToMany = m::mock(BelongsToMany::class)->makePartial();
        $manyToMany->shouldReceive('detach')->andReturn(null)->once();

        $hook = m::mock(LaravelHookQuery::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $hook->shouldReceive('isModelHookInputsOk')->andReturn($manyToMany)->once();

        $foo = m::mock(LaravelQuery::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getModelHook')->andReturn($hook);

        $this->assertTrue($foo->unhookSingleModel($source, $srcInstance, $target, $targInstance, 'manySource'));
    }

    public function testStartTransaction()
    {
        $db = DB::getFacadeRoot();
        DB::shouldReceive('rollBack')->andReturnNull()->never();
        DB::shouldReceive('beginTransaction')->andReturnNull()->once();
        DB::shouldReceive('commit')->andReturnNull()->never();

        $foo = m::mock(LaravelQuery::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->startTransaction();
    }

    public function testCommitTransaction()
    {
        $db = DB::getFacadeRoot();
        DB::shouldReceive('rollBack')->andReturnNull()->never();
        DB::shouldReceive('beginTransaction')->andReturnNull()->never();
        DB::shouldReceive('commit')->andReturnNull()->once();

        $foo = m::mock(LaravelQuery::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->commitTransaction();
    }

    public function testRollBackTransaction()
    {
        $db = DB::getFacadeRoot();
        DB::shouldReceive('rollBack')->andReturnNull()->once();
        DB::shouldReceive('beginTransaction')->andReturnNull()->never();
        DB::shouldReceive('commit')->andReturnNull()->never();

        $foo = m::mock(LaravelQuery::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->rollBackTransaction();
    }

    public function testGetEmptyControllerContainer()
    {
        $foo = m::mock(LaravelQuery::class)->makePartial();

        $expected = 'Controller container must not be null';
        $actual   = null;

        try {
            $foo->getControllerContainer();
        } catch (InvalidOperationException $e) {
            $actual = $e->getMessage();
        }

        $this->assertEquals($expected, $actual);
    }

    private function seedControllerMetadata(TestController $controller = null)
    {
        $translator = \Mockery::mock(\Illuminate\Translation\Translator::class)->makePartial();
        $validate   = new \Illuminate\Validation\Factory($translator);
        App::instance('validator', $validate);

        $mapping = isset($controller) ? $controller : new TestController();
        $mapping = $mapping->getMappings();

        $container = new MetadataControllerContainer();
        $container->setMetadata($mapping);
        // now that we've manually set up the controller metadata container, insert it
        App::instance('metadataControllers', $container);
    }

    /**
     * @return TestModel
     */
    private function generateTestModelWithMetadata()
    {
        $meta             = [];
        $meta['id']       = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $meta['name']     = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $meta['added_at'] = ['type' => 'datetime', 'nullable' => true, 'fillable' => true, 'default' => null];
        $meta['weight']   = ['type' => 'integer', 'nullable' => true, 'fillable' => true, 'default' => null];
        $meta['code']     = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];

        $instance = new TestModel($meta);
        return $instance;
    }
}
