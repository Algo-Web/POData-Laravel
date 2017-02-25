<?php

namespace AlgoWeb\PODataLaravel\Query;

use Illuminate\Support\Facades\App;

use AlgoWeb\PODataLaravel\Controllers\MetadataControllerContainer;
use AlgoWeb\PODataLaravel\Models\TestCase as TestCase;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Metadata\ResourceProperty;
use AlgoWeb\PODataLaravel\Models\TestMorphManySource;
use AlgoWeb\PODataLaravel\Models\TestMorphTarget;
use POData\Providers\Query\QueryType;
use POData\Providers\Query\QueryResult;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\KeyDescriptor;
use POData\Providers\Metadata\SimpleMetadataProvider;
use AlgoWeb\PODataLaravel\Models\TestModel;
use AlgoWeb\PODataLaravel\Controllers\TestController;
use Mockery as m;
use Symfony\Component\Process\Exception\InvalidArgumentException;

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

    protected $mapping;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        parent::setUp();
//        $this->object = new \AlgoWeb\PODataLaravel\Query\LaravelQuery();
        $this->mapping = [
            TestModel::class =>
                [
                    'create' => 'storeTestModel',
                    'read' => 'showTestModel',
                    'update' => 'updateTestModel',
                    'delete' => 'destroyTestModel'
                ]
        ];
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    public function tearDown()
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
        $foo = new LaravelQuery();
        $result = $foo->getExpressionProvider();
        $this->assertTrue($result instanceof LaravelExpressionProvider);
        $this->assertNull($result->getIteratorName());
        $this->assertNull($result->getResourceType());
    }


    /**
     * @covers \AlgoWeb\PODataLaravel\Query\LaravelQuery::getResourceSet
     * @todo   Implement testGetResourceSet().
     */
    public function testGetResourceSet()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testGetResourceSetBadFilterInfoInstanceThrowException()
    {
        $query = m::mock(QueryType::class);
        $resourceSet = m::mock(ResourceSet::class);
        $filter = new \DateTime();

        $foo = new LaravelQuery();

        $expected = 'Filter info must be either null or instance of FilterInfo.';
        $actual = null;

        try {
            $foo->getResourceSet($query, $resourceSet, $filter);
        } catch (InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers \AlgoWeb\PODataLaravel\Query\LaravelQuery::getResourceSet
     */
    public function testGetResourceSetWithEntitiesAndCount()
    {
        $instanceType = new \StdClass();
        $instanceType->name = 'AlgoWeb\\PODataLaravel\\Models\\TestMorphManySource';

        $mockResource = \Mockery::mock(ResourceSet::class);
        $mockResource->shouldReceive('getResourceType->getInstanceType')->andReturn($instanceType);

        $queryType = QueryType::ENTITIES_WITH_COUNT();

        $property = \Mockery::mock(ResourceProperty::class);
        $property->shouldReceive('getName')->withNoArgs()->andReturn('morphTarget');

        $rawBuilder = $this->getBuilder();

        $rawResult = \Mockery::mock(\Illuminate\Database\Eloquent\Builder::class)
            ->makePartial();
        $rawResult->shouldReceive('get')->andReturn(collect(['eins', 'zwei', 'polizei']));
        $rawResult->setQuery($rawBuilder);
        $this->assertTrue(null != ($rawResult->getQuery()->getProcessor()));

        $sourceEntity = \Mockery::mock(TestMorphManySource::class);
        $sourceEntity->shouldReceive('morphTarget')->andReturn($rawResult);

        $foo = \Mockery::mock(LaravelQuery::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getSourceEntityInstance')->andReturn($rawResult);

        $expected = ['eins', 'zwei', 'polizei'];

        $result = $foo->getResourceSet($queryType, $mockResource);
        $this->assertTrue($result instanceof QueryResult);
        $this->assertEquals(3, $result->count);
        $this->assertEquals($expected, $result->results);
    }


    /**
     * @covers \AlgoWeb\PODataLaravel\Query\LaravelQuery::getResourceFromResourceSet
     * @todo   Implement testGetResourceFromResourceSet().
     */
    public function testGetResourceFromResourceSet()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }


    /**
     * @covers \AlgoWeb\PODataLaravel\Query\LaravelQuery::getRelatedResourceSet
     * @todo   Implement testGetRelatedResourceSet().
     */
    public function testGetRelatedResourceSet()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testGetRelatedResourceSetNullSourceInstance()
    {
        $srcResource = m::mock(ResourceSet::class);
        $dstResource = m::mock(ResourceSet::class);
        $property = m::mock(ResourceProperty::class);
        $property->shouldReceive('getName')->andReturn('name');
        $key = m::mock(KeyDescriptor::class);
        $sourceEntity = null;

        $query = m::mock(QueryType::class);

        $foo = new LaravelQuery();

        $expected = 'Source entity must be an Eloquent model.';
        $actual = null;

        try {
            $foo->getRelatedResourceSet($query, $srcResource, $sourceEntity, $dstResource, $property);
        } catch (InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
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

        $query = new QueryResult();
        $query->count = 3;
        $query->results = ['eins', 'zwei', 'polizei'];

        $foo = m::mock(LaravelQuery::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getResourceSet')->withAnyArgs()->andReturn($query);

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

        $query = new QueryResult();
        $query->count = 3;

        $sourceEntity = \Mockery::mock(TestMorphManySource::class)->makePartial();

        $foo = m::mock(LaravelQuery::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getResourceSet')->withAnyArgs()->andReturn($query);
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

        $query = new QueryResult();
        $query->count = 3;

        $sourceEntity = \Mockery::mock(TestMorphManySource::class)->makePartial();
        $sourceEntity->shouldReceive('morphTarget')->andReturn($rawResult);

        $foo = m::mock(LaravelQuery::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getResourceSet')->withAnyArgs()->andReturn($query);
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

    public function testGetResourceFromRelatedResourceSetNullSourceInstance()
    {
        $srcResource = m::mock(ResourceSet::class);
        $dstResource = m::mock(ResourceSet::class);
        $property = m::mock(ResourceProperty::class);
        $property->shouldReceive('getName')->andReturn('name');
        $key = m::mock(KeyDescriptor::class);
        $sourceEntity = null;

        $foo = new LaravelQuery();

        $expected = 'Source entity must be an Eloquent model.';
        $actual = null;

        try {
            $foo->getResourceFromRelatedResourceSet($srcResource, $sourceEntity, $dstResource, $property, $key);
        } catch (InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }


    /**
     * @covers \AlgoWeb\PODataLaravel\Query\LaravelQuery::getResourceFromRelatedResourceSet
     * @todo   Implement testGetResourceFromRelatedResourceSet().
     */
    public function testGetResourceFromRelatedResourceSet()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }


    /**
     * @covers \AlgoWeb\PODataLaravel\Query\LaravelQuery::getRelatedResourceReference
     * @todo   Implement testGetRelatedResourceReference().
     */
    public function testGetRelatedResourceReference()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testGetRelatedResourceReferenceNullSourceInstance()
    {
        $srcResource = m::mock(ResourceSet::class);
        $dstResource = m::mock(ResourceSet::class);
        $property = m::mock(ResourceProperty::class);
        $property->shouldReceive('getName')->andReturn('name');
        $sourceEntity = null;

        $foo = new LaravelQuery();

        $expected = 'Source entity must be an Eloquent model.';
        $actual = null;

        try {
            $foo->getRelatedResourceReference($srcResource, $sourceEntity, $dstResource, $property);
        } catch (InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testAttemptUpdateBadSourceInstanceThrowException()
    {
        $mockResource = m::mock(ResourceSet::class);
        $model = new \DateTime();
        $keyDesc = m::mock(KeyDescriptor::class);
        $data = null;

        $foo = new LaravelQuery();
        $expected = 'Source entity must either be null or an Eloquent model.';
        $actual = null;
        try {
            $result = $foo->updateResource($mockResource, $model, $keyDesc, $data);
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testAttemptUpdate()
    {
        $controller = new TestController();

        $testName = TestController::class;

        $this->seedControllerMetadata($controller);

        $metaProv = new SimpleMetadataProvider('Data', 'Data');

        $fqModelName = TestModel::class;
        $meta = [];
        $meta['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false];
        $meta['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true];
        $meta['added_at'] = ['type' => 'datetime', 'nullable' => true, 'fillable' => true];
        $meta['weight'] = ['type' => 'integer', 'nullable' => true, 'fillable' => true];
        $meta['code'] = ['type' => 'string', 'nullable' => false, 'fillable' => true];

        $instance = new TestModel($meta);
        $type = $instance->getXmlSchema();
        $result = $metaProv->addResourceSet(strtolower($fqModelName), $type);
        App::instance('metadata', $metaProv);

        $std = new \StdClass();
        $std->name = TestModel::class;
        $mockResource = \Mockery::mock(ResourceSet::class);
        $mockResource->shouldReceive('getResourceType->getInstanceType')->andReturn($std);
        $model = new TestModel();
        $model->id = 42;
        $keyDesc = \Mockery::mock(KeyDescriptor::class);
        $data = new \StdClass;
        $data->name = 'Wibble';
        $data->added_at = new \DateTime;
        $data->weight = 0;
        $data->code = 'Enigma';
        $data->success = false;
        $shouldUpdate = false;


        $foo = new LaravelQuery();
        $expected = 'Target model not successfully updated';
        $actual = '';
        try {
            $result = $foo->updateResource($mockResource, $model, $keyDesc, $data);
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testAttemptCreateBadSourceInstanceThrowException()
    {
        $mockResource = m::mock(ResourceSet::class);
        $model = new \DateTime();
        $data = null;

        $foo = new LaravelQuery();
        $expected = 'Source entity must either be null or an Eloquent model.';
        $actual = null;
        try {
            $result = $foo->createResourceforResourceSet($mockResource, $model, $data);
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testAttemptCreate()
    {
        $controller = new TestController();

        $testName = TestController::class;

        $this->seedControllerMetadata($controller);

        $metaProv = new SimpleMetadataProvider('Data', 'Data');

        $fqModelName = TestModel::class;
        $meta = [];
        $meta['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false];
        $meta['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true];
        $meta['added_at'] = ['type' => 'datetime', 'nullable' => true, 'fillable' => true];
        $meta['weight'] = ['type' => 'integer', 'nullable' => true, 'fillable' => true];
        $meta['code'] = ['type' => 'string', 'nullable' => false, 'fillable' => true];

        $instance = new TestModel($meta);
        $type = $instance->getXmlSchema();
        $result = $metaProv->addResourceSet(strtolower($fqModelName), $type);
        App::instance('metadata', $metaProv);

        $std = new \StdClass();
        $std->name = TestModel::class;
        $mockResource = \Mockery::mock(ResourceSet::class);
        $mockResource->shouldReceive('getResourceType->getInstanceType')->andReturn($std);
        $model = new TestModel();
        $model->id = 42;
        $keyDesc = \Mockery::mock(KeyDescriptor::class);
        $data = new \StdClass;
        $data->name = 'Wibble';
        $data->added_at = new \DateTime;
        $data->weight = 0;
        $data->code = 'Enigma';
        $data->success = false;
        $shouldUpdate = false;

        $foo = new LaravelQuery();
        $expected = 'Target model not successfully created';
        $actual = '';
        try {
            $result = $foo->createResourceForResourceSet($mockResource, $model, $data);
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testAttemptDeleteBadSourceInstanceThrowException()
    {
        $mockResource = m::mock(ResourceSet::class);
        $model = new \DateTime();

        $foo = new LaravelQuery();
        $expected = 'Source entity must be an Eloquent model.';
        $actual = null;
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

        $fqModelName = TestModel::class;
        $meta = [];
        $meta['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false];
        $meta['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true];
        $meta['added_at'] = ['type' => 'datetime', 'nullable' => true, 'fillable' => true];
        $meta['weight'] = ['type' => 'integer', 'nullable' => true, 'fillable' => true];
        $meta['code'] = ['type' => 'string', 'nullable' => false, 'fillable' => true];

        $instance = new TestModel($meta);
        $type = $instance->getXmlSchema();
        $result = $metaProv->addResourceSet(strtolower($fqModelName), $type);
        App::instance('metadata', $metaProv);

        $std = new \StdClass();
        $std->name = TestModel::class;
        $mockResource = \Mockery::mock(ResourceSet::class);
        $mockResource->shouldReceive('getResourceType->getInstanceType')->andReturn($std);
        $model = new TestModel();
        $model->id = null;
        $keyDesc = \Mockery::mock(KeyDescriptor::class);
        $data = new \StdClass;
        $data->name = 'Wibble';
        $data->added_at = new \DateTime;
        $data->weight = 0;
        $data->code = 'Enigma';
        $data->success = false;
        $shouldUpdate = false;

        $foo = new LaravelQuery();
        $expected = 'Target model not successfully deleted';
        $actual = '';
        try {
            $result = $foo->deleteResource($mockResource, $model);
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    private function seedControllerMetadata(TestController $controller = null)
    {
        $translator = \Mockery::mock(\Illuminate\Translation\Translator::class)->makePartial();
        $validate = new \Illuminate\Validation\Factory($translator);
        App::instance('validator', $validate);

        $mapping = isset($controller) ? $controller : new TestController();
        $mapping = $mapping->getMappings();

        $container = new MetadataControllerContainer();
        $container->setMetadata($mapping);
        // now that we've manually set up the controller metadata container, insert it
        App::instance('metadataControllers', $container);
    }
}
