<?php

namespace AlgoWeb\PODataLaravel\Query;

use AlgoWeb\PODataLaravel\Controllers\MetadataControllerContainer;
use AlgoWeb\PODataLaravel\Controllers\TestController;
use AlgoWeb\PODataLaravel\Models\TestCase;
use AlgoWeb\PODataLaravel\Models\TestModel;
use AlgoWeb\PODataLaravel\Providers\MetadataProvider;
use AlgoWeb\PODataLaravel\Requests\TestRequest;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Mockery as m;
use POData\Providers\Metadata\ResourceEntityType;
use POData\Providers\Metadata\ResourceSet;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\KeyDescriptor;

class LaravelQueryBulkTest extends TestCase
{
    protected $origFacade = [];

    public function testContainerRetrieval()
    {
        $foo = new LaravelQuery();
        $result = $foo->getControllerContainer();
        $this->assertTrue($result instanceof MetadataControllerContainer);
    }

    public function testMetadataProviderRetrieval()
    {
        $foo = new LaravelQuery();
        $result = $foo->getMetadataProvider();
        $this->assertTrue($result instanceof MetadataProvider);
    }

    public function testBulkCreate()
    {
        $resultModel = m::mock(TestModel::class);
        $container = m::mock(MetadataControllerContainer::class);
        $container->shouldReceive('getMapping')->andReturn(null)->once();

        $db = DB::getFacadeRoot();
        $db->shouldReceive('rollBack')->andReturnNull()->never();
        $db->shouldReceive('beginTransaction')->andReturnNull()->once();
        $db->shouldReceive('commit')->andReturnNull()->once();

        $iType = new \ReflectionClass(TestModel::class);
        $type = m::mock(ResourceEntityType::class);
        $type->shouldReceive('getInstanceType')->andReturn($iType);
        $source = m::mock(ResourceSet::class);
        $source->shouldReceive('getResourceType')->andReturn($type);
        $data = [ ['data']];

        $foo = m::mock(LaravelQuery::class)->makePartial();
        $foo->shouldReceive('createResourceforResourceSet')->andReturn($resultModel);
        $foo->shouldReceive('getControllerContainer')->andReturn($container);

        $actual = $foo->createBulkResourceforResourceSet($source, $data);
        $this->assertEquals(1, count($actual));
        $this->assertTrue($actual[0] instanceof TestModel, get_class($actual[0]));
    }

    public function testBulkCreateFailure()
    {
        $container = m::mock(MetadataControllerContainer::class);
        $container->shouldReceive('getMapping')->andReturn(null)->once();
        $db = DB::getFacadeRoot();
        $db->shouldReceive('rollBack')->andReturnNull()->once();
        $db->shouldReceive('beginTransaction')->andReturnNull()->once();
        $db->shouldReceive('commit')->andReturnNull()->never();

        $iType = new \ReflectionClass(TestModel::class);
        $type = m::mock(ResourceEntityType::class);
        $type->shouldReceive('getInstanceType')->andReturn($iType);
        $source = m::mock(ResourceSet::class);
        $source->shouldReceive('getResourceType')->andReturn($type);
        $data = [ ['data']];

        $foo = m::mock(LaravelQuery::class)->makePartial();
        $foo->shouldReceive('createResourceforResourceSet')->andReturn(null);
        $foo->shouldReceive('getControllerContainer')->andReturn($container);

        $expected = 'Bulk model creation failed';
        $actual = null;

        try {
            $foo->createBulkResourceforResourceSet($source, $data);
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testBulkCustomCreateFailure()
    {
        $callResult = response()->json(['status' => 'error', 'id' => null, 'errors' => 'FAIL']);

        $testController = m::mock(TestController::class);
        $testController->shouldReceive('storeTestModel')->andReturn($callResult)->once();
        App::instance(TestController::class, $testController);

        $map = [
            'method' => 'storeTestModel',
            'controller' => TestController::class,
            'parameters' => ['request' => ['name' => 'request', 'type' => TestRequest::class, 'isRequest' => true]]
        ];

        $data = [[ 'data']];

        $container = m::mock(MetadataControllerContainer::class);
        $container->shouldReceive('getMapping')->andReturn($map)->once();

        $db = DB::getFacadeRoot();
        $db->shouldReceive('rollBack')->andReturnNull()->once();
        $db->shouldReceive('beginTransaction')->andReturnNull()->once();
        $db->shouldReceive('commit')->andReturnNull()->never();

        $iType = new \ReflectionClass(TestModel::class);
        $type = m::mock(ResourceEntityType::class);
        $type->shouldReceive('getInstanceType')->andReturn($iType);
        $source = m::mock(ResourceSet::class);
        $source->shouldReceive('getResourceType')->andReturn($type);

        $foo = m::mock(LaravelQuery::class)->makePartial();
        $foo->shouldReceive('getControllerContainer')->andReturn($container);

        $expected = 'Target models not successfully created';
        $actual = null;

        try {
            $foo->createBulkResourceforResourceSet($source, $data);
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testBulkCustomCreateBadLookup()
    {
        $callResult = response()->json(['status' => 'success', 'id' => [], 'errors' => null]);

        $testController = m::mock(TestController::class);
        $testController->shouldReceive('storeTestModel')->andReturn($callResult)->once();
        App::instance(TestController::class, $testController);

        $map = [
            'method' => 'storeTestModel',
            'controller' => TestController::class,
            'parameters' => ['request' => ['name' => 'request', 'type' => TestRequest::class, 'isRequest' => true]]
        ];

        $data = [[ 'data']];

        $container = m::mock(MetadataControllerContainer::class);
        $container->shouldReceive('getMapping')->andReturn($map)->once();

        $db = DB::getFacadeRoot();
        $db->shouldReceive('rollBack')->andReturnNull()->once();
        $db->shouldReceive('beginTransaction')->andReturnNull()->once();
        $db->shouldReceive('commit')->andReturnNull()->never();

        $iType = new \ReflectionClass(TestModel::class);
        $type = m::mock(ResourceEntityType::class);
        $type->shouldReceive('getInstanceType')->andReturn($iType);
        $source = m::mock(ResourceSet::class);
        $source->shouldReceive('getResourceType')->andReturn($type);

        $foo = m::mock(LaravelQuery::class)->makePartial();
        $foo->shouldReceive('getControllerContainer')->andReturn($container);

        $expected = 'No query results for model [AlgoWeb\PODataLaravel\Models\TestModel].';
        $actual = null;

        try {
            $foo->createBulkResourceforResourceSet($source, $data);
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testBulkCustomCreateGoodLookup()
    {
        $callResult = response()->json(['status' => 'success', 'id' => [2, 4, 6], 'errors' => null]);

        $testController = m::mock(TestController::class);
        $testController->shouldReceive('storeTestModel')->andReturn($callResult)->once();
        App::instance(TestController::class, $testController);

        $map = [
            'method' => 'storeTestModel',
            'controller' => TestController::class,
            'parameters' => ['request' => ['name' => 'request', 'type' => TestRequest::class, 'isRequest' => true]]
        ];

        $data = [['data']];

        $container = m::mock(MetadataControllerContainer::class);
        $container->shouldReceive('getMapping')->andReturn($map)->once();

        $db = DB::getFacadeRoot();
        $db->shouldReceive('rollBack')->andReturnNull()->never();
        $db->shouldReceive('beginTransaction')->andReturnNull()->once();
        $db->shouldReceive('commit')->andReturnNull()->once();

        $iType = new \ReflectionClass(TestModel::class);
        $type = m::mock(ResourceEntityType::class);
        $type->shouldReceive('getInstanceType')->andReturn($iType);
        $source = m::mock(ResourceSet::class);
        $source->shouldReceive('getResourceType')->andReturn($type);

        $foo = m::mock(LaravelQuery::class)->makePartial();
        $foo->shouldReceive('getControllerContainer')->andReturn($container);

        $result = $foo->createBulkResourceforResourceSet($source, $data);
        $this->assertTrue(is_array($result));
        $this->assertEquals(3, count($result));
    }

    public function testBulkUpdate()
    {
        $container = m::mock(MetadataControllerContainer::class);
        $container->shouldReceive('getMapping')->andReturn(null)->once();

        $db = DB::getFacadeRoot();
        $db->shouldReceive('rollBack')->andReturnNull()->never();
        $db->shouldReceive('beginTransaction')->andReturnNull()->once();
        $db->shouldReceive('commit')->andReturnNull()->once();

        $iType = new \ReflectionClass(TestModel::class);
        $type = m::mock(ResourceEntityType::class);
        $type->shouldReceive('getInstanceType')->andReturn($iType);
        $source = m::mock(ResourceSet::class);
        $source->shouldReceive('getResourceType')->andReturn($type);
        $data = [ ['data']];
        $keys = [ m::mock(KeyDescriptor::class)];
        $resultModel = m::mock(TestModel::class);

        $foo = m::mock(LaravelQuery::class)->makePartial();
        $foo->shouldReceive('updateResource')->andReturn($resultModel);
        $foo->shouldReceive('getControllerContainer')->andReturn($container);

        $actual = $foo->updateBulkResource($source, null, $keys, $data);
        $this->assertEquals(1, count($actual));
        $this->assertTrue($actual[0] instanceof TestModel, get_class($actual[0]));
    }

    public function testBulkUpdateFailure()
    {
        $container = m::mock(MetadataControllerContainer::class);
        $container->shouldReceive('getMapping')->andReturn(null)->once();
        $db = DB::getFacadeRoot();
        $db->shouldReceive('rollBack')->andReturnNull()->once();
        $db->shouldReceive('beginTransaction')->andReturnNull()->once();
        $db->shouldReceive('commit')->andReturnNull()->never();

        $iType = new \ReflectionClass(TestModel::class);
        $type = m::mock(ResourceEntityType::class);
        $type->shouldReceive('getInstanceType')->andReturn($iType);
        $source = m::mock(ResourceSet::class);
        $source->shouldReceive('getResourceType')->andReturn($type);
        $data = [ ['data']];
        $keys = [ m::mock(KeyDescriptor::class)];

        $foo = m::mock(LaravelQuery::class)->makePartial();
        $foo->shouldReceive('updateResource')->andReturn(null);
        $foo->shouldReceive('getControllerContainer')->andReturn($container);

        $expected = 'Bulk model update failed';
        $actual = null;

        try {
            $foo->updateBulkResource($source, null, $keys, $data);
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testBulkUpdateCountMismatch()
    {
        $source = m::mock(ResourceSet::class);
        $data = [ ['data']];
        $keys = [ ];
        $resultModel = m::mock(TestModel::class);

        $foo = m::mock(LaravelQuery::class)->makePartial();
        $foo->shouldReceive('updateResource')->andReturn($resultModel);

        $expected = 'Key descriptor array and data array must be same length';
        $actual = null;

        try {
            $foo->updateBulkResource($source, null, $keys, $data);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testBulkCustomUpdateFailure()
    {
        $callResult = response()->json(['status' => 'error', 'id' => null, 'errors' => 'FAIL']);

        $keyDesc = m::mock(KeyDescriptor::class);
        $keyDesc->shouldReceive('getNamedValues')->andReturn([]);

        $testController = m::mock(TestController::class);
        $testController->shouldReceive('storeTestModel')->andReturn($callResult)->once();
        App::instance(TestController::class, $testController);

        $map = [
            'method' => 'storeTestModel',
            'controller' => TestController::class,
            'parameters' => ['request' => ['name' => 'request', 'type' => TestRequest::class, 'isRequest' => true]]
        ];

        $data = [[ 'data']];
        $keys = [$keyDesc];

        $container = m::mock(MetadataControllerContainer::class);
        $container->shouldReceive('getMapping')->andReturn($map)->once();

        $db = DB::getFacadeRoot();
        $db->shouldReceive('rollBack')->andReturnNull()->once();
        $db->shouldReceive('beginTransaction')->andReturnNull()->once();
        $db->shouldReceive('commit')->andReturnNull()->never();

        $iType = new \ReflectionClass(TestModel::class);
        $type = m::mock(ResourceEntityType::class);
        $type->shouldReceive('getInstanceType')->andReturn($iType);
        $source = m::mock(ResourceSet::class);
        $source->shouldReceive('getResourceType')->andReturn($type);

        $foo = m::mock(LaravelQuery::class)->makePartial();
        $foo->shouldReceive('getControllerContainer')->andReturn($container);

        $expected = 'Target models not successfully updated';
        $actual = null;

        try {
            $foo->updateBulkResource($source, m::mock(TestModel::class), $keys, $data);
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testBulkCustomUpdateBadLookup()
    {
        $callResult = response()->json(['status' => 'success', 'id' => [], 'errors' => null]);

        $keyDesc = m::mock(KeyDescriptor::class);
        $keyDesc->shouldReceive('getNamedValues')->andReturn([]);

        $testController = m::mock(TestController::class);
        $testController->shouldReceive('storeTestModel')->andReturn($callResult)->once();
        App::instance(TestController::class, $testController);

        $map = [
            'method' => 'storeTestModel',
            'controller' => TestController::class,
            'parameters' => ['request' => ['name' => 'request', 'type' => TestRequest::class, 'isRequest' => true]]
        ];

        $data = [[ 'data']];
        $keys = [$keyDesc];

        $container = m::mock(MetadataControllerContainer::class);
        $container->shouldReceive('getMapping')->andReturn($map)->once();

        $db = DB::getFacadeRoot();
        $db->shouldReceive('rollBack')->andReturnNull()->once();
        $db->shouldReceive('beginTransaction')->andReturnNull()->once();
        $db->shouldReceive('commit')->andReturnNull()->never();

        $iType = new \ReflectionClass(TestModel::class);
        $type = m::mock(ResourceEntityType::class);
        $type->shouldReceive('getInstanceType')->andReturn($iType);
        $source = m::mock(ResourceSet::class);
        $source->shouldReceive('getResourceType')->andReturn($type);

        $foo = m::mock(LaravelQuery::class)->makePartial();
        $foo->shouldReceive('getControllerContainer')->andReturn($container);

        $expected = 'No query results for model [AlgoWeb\PODataLaravel\Models\TestModel].';
        $actual = null;

        try {
            $foo->updateBulkResource($source, m::mock(TestModel::class), $keys, $data);
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testBulkCustomUpdateGoodLookup()
    {
        $callResult = response()->json(['status' => 'success', 'id' => [2, 4, 6], 'errors' => null]);

        $keyDesc = m::mock(KeyDescriptor::class);
        $keyDesc->shouldReceive('getNamedValues')->andReturn([]);

        $testController = m::mock(TestController::class);
        $testController->shouldReceive('storeTestModel')->andReturn($callResult)->once();
        App::instance(TestController::class, $testController);

        $map = [
            'method' => 'storeTestModel',
            'controller' => TestController::class,
            'parameters' => ['request' => ['name' => 'request', 'type' => TestRequest::class, 'isRequest' => true]]
        ];

        $data = [['data'], ['data'], ['data']];
        $keys = [$keyDesc, $keyDesc, $keyDesc];

        $container = m::mock(MetadataControllerContainer::class);
        $container->shouldReceive('getMapping')->andReturn($map)->once();

        $db = DB::getFacadeRoot();
        $db->shouldReceive('rollBack')->andReturnNull()->never();
        $db->shouldReceive('beginTransaction')->andReturnNull()->once();
        $db->shouldReceive('commit')->andReturnNull()->once();

        $iType = new \ReflectionClass(TestModel::class);
        $type = m::mock(ResourceEntityType::class);
        $type->shouldReceive('getInstanceType')->andReturn($iType);
        $source = m::mock(ResourceSet::class);
        $source->shouldReceive('getResourceType')->andReturn($type);

        $foo = m::mock(LaravelQuery::class)->makePartial();
        $foo->shouldReceive('getControllerContainer')->andReturn($container);

        $result = $foo->updateBulkResource($source, m::mock(TestModel::class), $keys, $data);
        $this->assertTrue(is_array($result));
        $this->assertEquals(3, count($result));
    }

    public function setUp()
    {
        parent::setUp();
        $this->origFacade['DB'] = DB::getFacadeRoot();
    }

    public function tearDown()
    {
        DB::swap($this->origFacade['DB']);
        parent::tearDown();
    }
}
