<?php

namespace AlgoWeb\PODataLaravel\Query;

use AlgoWeb\PODataLaravel\Controllers\MetadataControllerContainer;
use AlgoWeb\PODataLaravel\Controllers\TestController;
use AlgoWeb\PODataLaravel\Interfaces\AuthInterface;
use AlgoWeb\PODataLaravel\Models\LaravelBulkQueryDummy;
use AlgoWeb\PODataLaravel\Models\LaravelQueryDummy;
use AlgoWeb\PODataLaravel\Models\TestBulkCreateRequest;
use AlgoWeb\PODataLaravel\Models\TestBulkUpdateRequest;
use AlgoWeb\PODataLaravel\Models\TestCase;
use AlgoWeb\PODataLaravel\Models\TestModel;
use AlgoWeb\PODataLaravel\Providers\MetadataProvider;
use AlgoWeb\PODataLaravel\Requests\TestRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Mockery as m;
use POData\Common\InvalidOperationException;
use POData\Common\ODataException;
use POData\Providers\Metadata\ResourceEntityType;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Metadata\Type\Int32;
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
        $container = m::mock(MetadataControllerContainer::class)->makePartial();
        $container->shouldReceive('getMapping')->andReturn(null)->once();
        App::instance('metadataControllers', $container);

        $db = DB::getFacadeRoot();
        DB::shouldReceive('rollBack')->andReturnNull()->never();
        DB::shouldReceive('beginTransaction')->andReturnNull()->once();
        DB::shouldReceive('commit')->andReturnNull()->once();

        $iType = new \ReflectionClass(TestModel::class);
        $type = m::mock(ResourceEntityType::class);
        $type->shouldReceive('getInstanceType')->andReturn($iType);
        $source = m::mock(ResourceSet::class);
        $source->shouldReceive('getResourceType')->andReturn($type);
        $data = [ ['data']];

        $auth = m::mock(AuthInterface::class);
        $auth->shouldReceive('canAuth')->andReturn(true);

        $bulk = m::mock(LaravelBulkQueryDummy::class)->makePartial();
        $bulk->shouldReceive('getControllerContainer')->andReturn($container);
        $bulk->shouldReceive('getQuery->createResourceForResourceSet')->andReturn($resultModel);

        $foo = m::mock(LaravelQueryDummy::class)->makePartial();
        $foo->shouldReceive('getBulk')->andReturn($bulk);
        $foo->shouldReceive('getControllerContainer')->andReturn($container);
        $bulk->shouldReceive('createResourceforResourceSet')->andReturn($resultModel);
        $bulk->setQuery($foo);
        $foo->setAuth($auth);

        $actual = $foo->createBulkResourceforResourceSet($source, $data);
        $this->assertEquals(1, count($actual));
        $this->assertTrue($actual[0] instanceof TestModel, get_class($actual[0]));
    }

    public function testBulkCreateFailure()
    {
        $container = m::mock(MetadataControllerContainer::class);
        $container->shouldReceive('getMapping')->andReturn(null)->once();
        $db = DB::getFacadeRoot();
        DB::shouldReceive('rollBack')->andReturnNull()->once();
        DB::shouldReceive('beginTransaction')->andReturnNull()->once();
        DB::shouldReceive('commit')->andReturnNull()->never();

        $iType = new \ReflectionClass(TestModel::class);
        $type = m::mock(ResourceEntityType::class);
        $type->shouldReceive('getInstanceType')->andReturn($iType);
        $source = m::mock(ResourceSet::class);
        $source->shouldReceive('getResourceType')->andReturn($type);
        $data = [ ['data']];

        $bulk = m::mock(LaravelBulkQuery::class)->makePartial();
        $bulk->shouldReceive('getControllerContainer')->andReturn($container);
        $bulk->shouldReceive('getQuery->createResourceForResourceSet')->andReturn(null);

        $foo = m::mock(LaravelQuery::class)->makePartial();
        $foo->shouldReceive('getBulk')->andReturn($bulk);
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
        DB::shouldReceive('rollBack')->andReturnNull()->once();
        DB::shouldReceive('beginTransaction')->andReturnNull()->once();
        DB::shouldReceive('commit')->andReturnNull()->never();

        $iType = new \ReflectionClass(TestModel::class);
        $type = m::mock(ResourceEntityType::class);
        $type->shouldReceive('getInstanceType')->andReturn($iType);
        $source = m::mock(ResourceSet::class);
        $source->shouldReceive('getResourceType')->andReturn($type);

        $bulk = m::mock(LaravelBulkQuery::class)->makePartial();
        $bulk->shouldReceive('getControllerContainer')->andReturn($container);

        $foo = m::mock(LaravelQuery::class)->makePartial();
        $foo->shouldReceive('getControllerContainer')->andReturn($container);
        $foo->shouldReceive('getBulk')->andReturn($bulk);

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
        DB::shouldReceive('rollBack')->andReturnNull()->once();
        DB::shouldReceive('beginTransaction')->andReturnNull()->once();
        DB::shouldReceive('commit')->andReturnNull()->never();

        $iType = new \ReflectionClass(TestModel::class);
        $type = m::mock(ResourceEntityType::class);
        $type->shouldReceive('getInstanceType')->andReturn($iType);
        $source = m::mock(ResourceSet::class);
        $source->shouldReceive('getResourceType')->andReturn($type);

        $bulk = m::mock(LaravelBulkQuery::class)->makePartial();
        $bulk->shouldReceive('getControllerContainer')->andReturn($container);

        $foo = m::mock(LaravelQuery::class)->makePartial();
        $foo->shouldReceive('getControllerContainer')->andReturn($container);
        $foo->shouldReceive('getBulk')->andReturn($bulk);

        $expected = 'No query results for model [AlgoWeb\PODataLaravel\Models\TestModel].';
        $actual = null;

        try {
            $foo->createBulkResourceforResourceSet($source, $data);
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testBulkCustomCreateMethod()
    {
        $paramList = [
            'method' => 'storeBulkTestModel',
            'controller' => TestController::class,
            'parameters' => ['request' =>
                ['name' => 'request', 'type' => TestBulkCreateRequest::class, 'isRequest' => true]]
        ];

        $actualValid = m::mock(\Illuminate\Validation\Validator::class);
        $actualValid->shouldReceive('passes')->andReturn(true)->once();

        $valFactory = m::mock(\Illuminate\Validation\Factory::class);
        $valFactory->shouldReceive('make')->andReturn($actualValid);

        Validator::swap($valFactory);

        $controller = new TestController();

        $query = new LaravelQueryDummy();

        $date = new \DateTime('2017-01-01');

        $rawData = [];
        $rawData[] = ['name' => 'name', 'date' => $date, 'weight' => 0, 'code' => '42', 'success' => true];
        $rawData[] = ['name' => 'name', 'date' => $date, 'weight' => 0, 'code' => '42', 'success' => true];

        $bulk = m::mock(LaravelBulkQueryDummy::class)->makePartial();

        $request = $bulk->prepareBulkRequestInput($paramList['parameters'], $rawData);
        $request = $request[0];
        $this->assertTrue($request instanceof TestBulkCreateRequest, get_class($request));

        $result = $controller->storeBulkTestModel($request);
        $data = $result->getData();
        $this->assertEquals([1, 2], $data->id);
    }

    public function testBulkCustomUpdateMethod()
    {
        $paramList = [
            'method' => 'updateBulkTestModel',
            'controller' => TestController::class,
            'parameters' => ['request' =>
                ['name' => 'request', 'type' => TestBulkUpdateRequest::class, 'isRequest' => true]]
        ];

        $actualValid = m::mock(\Illuminate\Validation\Validator::class);
        $actualValid->shouldReceive('passes')->andReturn(true)->once();

        $valFactory = m::mock(\Illuminate\Validation\Factory::class);
        $valFactory->shouldReceive('make')->andReturn($actualValid);

        Validator::swap($valFactory);

        $controller = new TestController();

        $query = new LaravelQueryDummy();

        $date = new \DateTime('2017-01-01');

        $descOne = m::mock(KeyDescriptor::class);
        $descOne->shouldReceive('getNamedValues')->andReturn(['id' => [1, new Int32()]]);
        $descTwo = m::mock(KeyDescriptor::class);
        $descTwo->shouldReceive('getNamedValues')->andReturn(['id' => [4, new Int32()]]);

        $rawData = [];
        $rawData[] = ['name' => 'name', 'date' => $date, 'weight' => 0, 'code' => '42', 'success' => true];
        $rawData[] = ['name' => 'name', 'date' => $date, 'weight' => 0, 'code' => '42', 'success' => true];

        $bulk = m::mock(LaravelBulkQueryDummy::class)->makePartial();
        $request = $bulk->prepareBulkRequestInput($paramList['parameters'], $rawData, [$descOne, $descTwo]);
        $request = $request[0];
        $this->assertTrue($request instanceof TestBulkUpdateRequest, get_class($request));

        $result = $controller->updateBulkTestModel($request);
        $data = $result->getData();
        $this->assertEquals([1, 4], $data->id);
    }

    public function testBulkUpdate()
    {
        $container = m::mock(MetadataControllerContainer::class);
        $container->shouldReceive('getMapping')->andReturn(null)->once();

        $db = DB::getFacadeRoot();
        DB::shouldReceive('rollBack')->andReturnNull()->never();
        DB::shouldReceive('beginTransaction')->andReturnNull()->once();
        DB::shouldReceive('commit')->andReturnNull()->once();

        $iType = new \ReflectionClass(TestModel::class);
        $type = m::mock(ResourceEntityType::class);
        $type->shouldReceive('getInstanceType')->andReturn($iType);
        $source = m::mock(ResourceSet::class);
        $source->shouldReceive('getResourceType')->andReturn($type);
        $data = [ ['data']];
        $keys = [ m::mock(KeyDescriptor::class)];
        $resultModel = m::mock(TestModel::class);

        $bulk = m::mock(LaravelBulkQuery::class)->makePartial();
        $bulk->shouldReceive('getControllerContainer')->andReturn($container);
        $bulk->shouldReceive('getQuery->updateResource')->andReturn($resultModel);

        $foo = m::mock(LaravelQuery::class)->makePartial();
        $foo->shouldReceive('getBulk')->andReturn($bulk);
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
        DB::shouldReceive('rollBack')->andReturnNull()->once();
        DB::shouldReceive('beginTransaction')->andReturnNull()->once();
        DB::shouldReceive('commit')->andReturnNull()->never();

        $iType = new \ReflectionClass(TestModel::class);
        $type = m::mock(ResourceEntityType::class);
        $type->shouldReceive('getInstanceType')->andReturn($iType);
        $source = m::mock(ResourceSet::class);
        $source->shouldReceive('getResourceType')->andReturn($type);
        $data = [ ['data']];
        $keys = [ m::mock(KeyDescriptor::class)];

        $bulk = m::mock(LaravelBulkQuery::class)->makePartial();
        $bulk->shouldReceive('getControllerContainer')->andReturn($container);
        $bulk->shouldReceive('getQuery->updateResource')->andReturn(null);

        $foo = m::mock(LaravelQuery::class)->makePartial();
        $foo->shouldReceive('getBulk')->andReturn($bulk);
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

        $bulk = m::mock(LaravelBulkQuery::class)->makePartial();
        $bulk->shouldReceive('getQuery->updateResource')->andReturn($resultModel);

        $foo = m::mock(LaravelQuery::class)->makePartial();
        $foo->shouldReceive('getBulk')->andReturn($bulk);

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
        DB::shouldReceive('rollBack')->andReturnNull()->once();
        DB::shouldReceive('beginTransaction')->andReturnNull()->once();
        DB::shouldReceive('commit')->andReturnNull()->never();

        $iType = new \ReflectionClass(TestModel::class);
        $type = m::mock(ResourceEntityType::class);
        $type->shouldReceive('getInstanceType')->andReturn($iType);
        $source = m::mock(ResourceSet::class);
        $source->shouldReceive('getResourceType')->andReturn($type);

        $bulk = m::mock(LaravelBulkQuery::class)->makePartial();
        $bulk->shouldReceive('getControllerContainer')->andReturn($container);

        $foo = m::mock(LaravelQuery::class)->makePartial();
        $foo->shouldReceive('getBulk')->andReturn($bulk);
        $foo->shouldReceive('getControllerContainer')->andReturn($container);

        $expected = 'Target models not successfully updated';
        $actual = null;

        try {
            $foo->updateBulkResource($source, m::mock(TestModel::class), $keys, $data);
        } catch (ODataException $e) {
            $actual = $e->getMessage();
            $this->assertEquals(422, $e->getStatusCode());
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
        DB::shouldReceive('rollBack')->andReturnNull()->once();
        DB::shouldReceive('beginTransaction')->andReturnNull()->once();
        DB::shouldReceive('commit')->andReturnNull()->never();

        $iType = new \ReflectionClass(TestModel::class);
        $type = m::mock(ResourceEntityType::class);
        $type->shouldReceive('getInstanceType')->andReturn($iType);
        $source = m::mock(ResourceSet::class);
        $source->shouldReceive('getResourceType')->andReturn($type);

        $bulk = m::mock(LaravelBulkQuery::class)->makePartial();
        $bulk->shouldReceive('getControllerContainer')->andReturn($container);

        $foo = m::mock(LaravelQuery::class)->makePartial();
        $foo->shouldReceive('getBulk')->andReturn($bulk);
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

    /**
     *
     */
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
        DB::shouldReceive('rollBack')->andReturnNull()->never();
        DB::shouldReceive('beginTransaction')->andReturnNull()->once();
        DB::shouldReceive('commit')->andReturnNull()->once();

        $iType = new \ReflectionClass(TestModel::class);
        $type = m::mock(ResourceEntityType::class);
        $type->shouldReceive('getInstanceType')->andReturn($iType);
        $source = m::mock(ResourceSet::class);
        $source->shouldReceive('getResourceType')->andReturn($type);

        $bulk = m::mock(LaravelBulkQueryDummy::class)->makePartial();
        $bulk->setControllerContainer($container);

        $foo = m::mock(LaravelQueryDummy::class)->makePartial();
        $foo->shouldReceive('getControllerContainer')->andReturn($container);
        $foo->setBulk($bulk);

        $result = $foo->updateBulkResource($source, m::mock(TestModel::class), $keys, $data);
        $this->assertTrue(is_array($result));
        $this->assertEquals(3, count($result));
    }

    public function testProcessOutputMalformedResponse()
    {
        $response = m::mock(JsonResponse::class);
        $response->shouldReceive('getData')->andReturn('bork bork bork!');

        $bulk = m::mock(LaravelBulkQueryDummy::class)->makePartial();

        $expected = 'Controller response does not have an array.';
        $actual = null;

        try {
            $bulk->createUpdateDeleteProcessOutput($response);
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testProcessOutputMalformedArrayResponse()
    {
        $response = m::mock(JsonResponse::class);
        $response->shouldReceive('getData')->andReturn([]);

        $bulk = m::mock(LaravelBulkQueryDummy::class)->makePartial();
        $this->assertNull($bulk->getQuery());

        $expected = 'Controller response array missing at least one of id, status and/or errors fields.';
        $actual = null;

        try {
            $bulk->createUpdateDeleteProcessOutput($response);
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetEmptyControllerContainer()
    {
        $foo = m::mock(LaravelBulkQuery::class)->makePartial();

        $expected = 'Controller container must not be null';
        $actual = null;

        try {
            $foo->getControllerContainer();
        } catch (InvalidOperationException $e) {
            $actual = $e->getMessage();
        }

        $this->assertEquals($expected, $actual);
    }

    public function testGetOptionalVerbMappingBadInstanceType()
    {
        $set = m::mock(ResourceSet::class);
        $set->shouldReceive('getResourceType->getInstanceType')->andReturnNull();

        $foo = m::mock(LaravelBulkQueryDummy::class)->makePartial();

        $expected = "Null";
        $actual = null;

        try {
            $foo->getOptionalVerbMapping($set, 'POST');
        } catch (InvalidOperationException $e) {
            $actual = $e->getMessage();
        }

        $this->assertEquals($expected, $actual);
    }

    public function setUp() : void
    {
        parent::setUp();
        $this->origFacade['DB'] = DB::getFacadeRoot();
    }

    public function tearDown() : void
    {
        DB::swap($this->origFacade['DB']);
        parent::tearDown();
    }
}
