<?php

namespace AlgoWeb\PODataLaravel\Serialisers;

use AlgoWeb\PODataLaravel\Models\TestModel;
use AlgoWeb\PODataLaravel\Models\TestMonomorphicTarget;
use AlgoWeb\PODataLaravel\Providers\MetadataProvider;
use AlgoWeb\PODataLaravel\Query\LaravelQuery;
use Illuminate\Support\Facades\App;
use POData\ObjectModel\ObjectModelSerializer;
use POData\OperationContext\ServiceHost;
use POData\OperationContext\Web\Illuminate\IlluminateOperationContext as OperationContextAdapter;
use Mockery as m;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourcePropertyKind;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\ResourceTypeKind;
use POData\Providers\Metadata\Type\Int32;
use POData\Providers\Metadata\Type\StringType;
use POData\Providers\Query\QueryResult;
use POData\Providers\Query\QueryType;

class SerialiserWriteComplexTest extends SerialiserTestBase
{
    public function testWriteNullComplexObject()
    {
        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/TestModels');
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc/TestModels');

        list($host, $meta, $query) = $this->setUpDataServiceDeps($request);

        // default data service
        $service = new TestDataService($query, $meta, $host);
        $processor = $service->handleRequest();
        $processor->getRequest()->queryType = QueryType::ENTITIES_WITH_COUNT();
        $processor->getRequest()->setCountValue(1);
        $object = new ObjectModelSerializer($service, $processor->getRequest());
        $ironic = new IronicSerialiser($service, $processor->getRequest());

        $propName = 'makeItPhunkee';
        $rType = m::mock(ResourceType::class);
        $rType->shouldReceive('getFullName')->andReturn('stopHammerTime');
        $rType->shouldReceive('getName')->andReturn('tooLegitToQuit');

        $collection = new QueryResult();
        $collection->results = null;

        $objectResult = $object->writeTopLevelComplexObject($collection, $propName, $rType);
        $ironicResult = $ironic->writeTopLevelComplexObject($collection, $propName, $rType);

        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
    }

    public function testWriteBadComplexObject()
    {
        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/TestModels');
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc/TestModels');

        list($host, $meta, $query) = $this->setUpDataServiceDeps($request);

        // default data service
        $service = new TestDataService($query, $meta, $host);
        $processor = $service->handleRequest();
        $processor->getRequest()->queryType = QueryType::ENTITIES_WITH_COUNT();
        $processor->getRequest()->setCountValue(1);
        $object = new ObjectModelSerializer($service, $processor->getRequest());
        $ironic = new IronicSerialiser($service, $processor->getRequest());

        $propName = 'makeItPhunkee';
        $rType = m::mock(ResourceType::class);
        $rType->shouldReceive('getFullName')->andReturn('stopHammerTime');
        $rType->shouldReceive('getName')->andReturn('tooLegitToQuit');
        $rType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::COMPLEX);

        $collection = new QueryResult();
        $collection->results = [ 'foo' ];

        $expected = null;
        $expectedExceptionClass = null;
        $actual = null;
        $actualExceptionClass = null;

        try {
            $object->writeTopLevelComplexObject($collection, $propName, $rType);
        } catch (\Exception $e) {
            $expectedExceptionClass = get_class($e);
            $expected = $e->getMessage();
        }
        try {
            $ironic->writeTopLevelComplexObject($collection, $propName, $rType);
        } catch (\Exception $e) {
            $actualExceptionClass = get_class($e);
            $actual = $e->getMessage();
        }

        $this->assertEquals($expectedExceptionClass, $actualExceptionClass);
        $this->assertEquals($expected, $actual);
    }

    public function testWriteEloquentModelComplexObject()
    {
        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/TestModels');
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc/TestModels');

        list($host, $meta, $query) = $this->setUpDataServiceDeps($request);

        // default data service
        $service = new TestDataService($query, $meta, $host);
        $processor = $service->handleRequest();
        $processor->getRequest()->queryType = QueryType::ENTITIES_WITH_COUNT();
        $processor->getRequest()->setCountValue(1);
        $object = new ObjectModelSerializer($service, $processor->getRequest());
        $ironic = new IronicSerialiser($service, $processor->getRequest());

        $subType1 = m::mock(ResourceType::class);
        $subType1->shouldReceive('getInstanceType')->andReturn(new Int32());
        $subType2 = m::mock(ResourceType::class);
        $subType2->shouldReceive('getInstanceType')->andReturn(new StringType());

        $rProp1 = m::mock(ResourceProperty::class);
        $rProp1->shouldReceive('getKind')->andReturn(ResourcePropertyKind::PRIMITIVE);
        $rProp1->shouldReceive('getName')->andReturn('id');
        $rProp1->shouldReceive('getInstanceType')->andReturn(new Int32());
        $rProp1->shouldReceive('getResourceType')->andReturn($subType1);
        $rProp2 = m::mock(ResourceProperty::class);
        $rProp2->shouldReceive('getKind')->andReturn(ResourcePropertyKind::PRIMITIVE);
        $rProp2->shouldReceive('getName')->andReturn('foo');
        $rProp2->shouldReceive('getInstanceType')->andReturn(new StringType());
        $rProp2->shouldReceive('getResourceType')->andReturn($subType2);

        $propName = 'makeItPhunkee';
        $rType = m::mock(ResourceType::class);
        $rType->shouldReceive('getFullName')->andReturn('stopHammerTime');
        $rType->shouldReceive('getName')->andReturn('tooLegitToQuit');
        $rType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::COMPLEX);
        $rType->shouldReceive('getAllProperties')->andReturn([$rProp1, $rProp2]);

        $model = new TestMonomorphicTarget();
        $model->id = 11;
        $model->foo = 'bar';

        $collection = new QueryResult();
        $collection->results = $model;

        $objectResult = $object->writeTopLevelComplexObject($collection, $propName, $rType);
        $ironicResult = $ironic->writeTopLevelComplexObject($collection, $propName, $rType);

        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
    }

    public function testWriteNonEloquentModelComplexObject()
    {
        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/TestModels');
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc/TestModels');

        list($host, $meta, $query) = $this->setUpDataServiceDeps($request);

        // default data service
        $service = new TestDataService($query, $meta, $host);
        $processor = $service->handleRequest();
        $processor->getRequest()->queryType = QueryType::ENTITIES_WITH_COUNT();
        $processor->getRequest()->setCountValue(1);
        $object = new ObjectModelSerializer($service, $processor->getRequest());
        $ironic = new IronicSerialiser($service, $processor->getRequest());

        $subType1 = m::mock(ResourceType::class);
        $subType1->shouldReceive('getInstanceType')->andReturn(new Int32());
        $subType2 = m::mock(ResourceType::class);
        $subType2->shouldReceive('getInstanceType')->andReturn(new StringType());

        $rProp1 = m::mock(ResourceProperty::class);
        $rProp1->shouldReceive('getKind')->andReturn(ResourcePropertyKind::PRIMITIVE);
        $rProp1->shouldReceive('getName')->andReturn('name');
        $rProp1->shouldReceive('getInstanceType')->andReturn(new StringType());
        $rProp1->shouldReceive('getResourceType')->andReturn($subType1);
        $rProp2 = m::mock(ResourceProperty::class);
        $rProp2->shouldReceive('getKind')->andReturn(ResourcePropertyKind::PRIMITIVE);
        $rProp2->shouldReceive('getName')->andReturn('type');
        $rProp2->shouldReceive('getInstanceType')->andReturn(new StringType());
        $rProp2->shouldReceive('getResourceType')->andReturn($subType2);

        $propName = 'makeItPhunkee';
        $rType = m::mock(ResourceType::class);
        $rType->shouldReceive('getFullName')->andReturn('stopHammerTime');
        $rType->shouldReceive('getName')->andReturn('tooLegitToQuit');
        $rType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::COMPLEX);
        $rType->shouldReceive('getAllProperties')->andReturn([$rProp1, $rProp2]);

        $model = new reusableEntityClass1();
        $model->name = 'name';
        $model->type = 'type';

        $collection = new QueryResult();
        $collection->results = $model;

        $objectResult = $object->writeTopLevelComplexObject($collection, $propName, $rType);
        $ironicResult = $ironic->writeTopLevelComplexObject($collection, $propName, $rType);

        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
    }

    public function testWriteEloquentComplexObjectWithNonEloquentComplexProperty()
    {
        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/TestModels');
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc/TestModels');

        list($host, $meta, $query) = $this->setUpDataServiceDeps($request);

        // default data service
        $service = new TestDataService($query, $meta, $host);
        $processor = $service->handleRequest();
        $processor->getRequest()->queryType = QueryType::ENTITIES_WITH_COUNT();
        $processor->getRequest()->setCountValue(1);
        $object = new ObjectModelSerializer($service, $processor->getRequest());
        $ironic = new IronicSerialiser($service, $processor->getRequest());

        $rTypeBase = m::mock(ResourceType::class);
        $rTypeBase->shouldReceive('getInstanceType')->andReturn(new StringType());
        $rTypeBase->shouldReceive('getFullName')->andReturn('putEmHigh');

        $subProp1 = m::mock(ResourceProperty::class);
        $subProp1->shouldReceive('getKind')->andReturn(ResourcePropertyKind::PRIMITIVE);
        $subProp1->shouldReceive('getName')->andReturn('name');
        $subProp1->shouldReceive('getInstanceType')->andReturn(new StringType());
        $subProp1->shouldReceive('getResourceType')->andReturn($rTypeBase);

        $subProp2 = m::mock(ResourceProperty::class);
        $subProp2->shouldReceive('getKind')->andReturn(ResourcePropertyKind::PRIMITIVE);
        $subProp2->shouldReceive('getName')->andReturn('type');
        $subProp2->shouldReceive('getInstanceType')->andReturn(new StringType());
        $subProp2->shouldReceive('getResourceType')->andReturn($rTypeBase);

        $subType2 = m::mock(ResourceType::class);
        $subType2->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::COMPLEX);
        $subType2->shouldReceive('getInstanceType')->andReturn(new StringType());
        $subType2->shouldReceive('getFullName')->andReturn('paintItBlack');
        $subType2->shouldReceive('getAllProperties')->andReturn([$subProp1, $subProp2]);

        $rProp1 = m::mock(ResourceProperty::class);
        $rProp1->shouldReceive('isKindOf')->withArgs([ResourcePropertyKind::BAG])->andReturn(false);
        $rProp1->shouldReceive('getKind')->andReturn(ResourcePropertyKind::PRIMITIVE);
        $rProp1->shouldReceive('getName')->andReturn('id');
        $rProp1->shouldReceive('getInstanceType')->andReturn(new StringType());
        $rProp1->shouldReceive('getResourceType')->andReturn($subType2);

        $rProp2 = m::mock(ResourceProperty::class);
        $rProp2->shouldReceive('isKindOf')->withArgs([ResourcePropertyKind::BAG])->andReturn(false);
        $rProp2->shouldReceive('getKind')->andReturn(ResourcePropertyKind::COMPLEX_TYPE);
        $rProp2->shouldReceive('getName')->andReturn('foo');
        $rProp2->shouldReceive('getInstanceType')->andReturn(new StringType());
        $rProp2->shouldReceive('getResourceType')->andReturn($subType2);

        $propName = 'makeItPhunkee';
        $rType = m::mock(ResourceType::class);
        $rType->shouldReceive('getFullName')->andReturn('stopHammerTime');
        $rType->shouldReceive('getName')->andReturn('tooLegitToQuit');
        $rType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::COMPLEX);
        $rType->shouldReceive('getAllProperties')->andReturn([$rProp1, $rProp2]);

        $zoidberg = new reusableEntityClass1();
        $zoidberg->name = 'name';
        $zoidberg->type = 'type';

        $model = new TestMonomorphicTarget();
        $model->id = 11;
        $model->foo = $zoidberg;

        $collection = new QueryResult();
        $collection->results = $model;

        $objectResult = $object->writeTopLevelComplexObject($collection, $propName, $rType);
        $ironicResult = $ironic->writeTopLevelComplexObject($collection, $propName, $rType);

        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
    }

    public function testWriteEloquentModelComplexObjectLoopDeLoop()
    {
        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/TestModels');
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc/TestModels');

        list($host, $meta, $query) = $this->setUpDataServiceDeps($request);

        // default data service
        $service = new TestDataService($query, $meta, $host);
        $processor = $service->handleRequest();
        $processor->getRequest()->queryType = QueryType::ENTITIES_WITH_COUNT();
        $processor->getRequest()->setCountValue(1);
        $object = new ObjectModelSerializer($service, $processor->getRequest());
        $ironic = new IronicSerialiser($service, $processor->getRequest());

        $subType1 = m::mock(ResourceType::class);
        $subType1->shouldReceive('getInstanceType')->andReturn(new Int32());
        $subType1->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::PRIMITIVE);

        $rProp1 = m::mock(ResourceProperty::class);
        $rProp1->shouldReceive('isKindOf')->withArgs([ResourcePropertyKind::BAG])->andReturn(false);
        $rProp1->shouldReceive('getKind')->andReturn(ResourcePropertyKind::PRIMITIVE);
        $rProp1->shouldReceive('getName')->andReturn('id');
        $rProp1->shouldReceive('getInstanceType')->andReturn(new Int32());
        $rProp1->shouldReceive('getResourceType')->andReturn($subType1);

        $rProp2 = m::mock(ResourceProperty::class);
        $rProp2->shouldReceive('isKindOf')->withArgs([ResourcePropertyKind::BAG])->andReturn(false);
        $rProp2->shouldReceive('getKind')->andReturn(ResourcePropertyKind::COMPLEX_TYPE);
        $rProp2->shouldReceive('getName')->andReturn('foo');
        $rProp2->shouldReceive('getInstanceType')->andReturn(new StringType());

        $rType = m::mock(ResourceType::class);
        $rType->shouldReceive('getFullName')->andReturn('stopHammerTime');
        $rType->shouldReceive('getName')->andReturn('tooLegitToQuit');
        $rType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::COMPLEX);
        $rType->shouldReceive('getAllProperties')->andReturn([$rProp1, $rProp2]);
        $rProp2->shouldReceive('getResourceType')->andReturn($rType);

        $zoidberg = new TestMonomorphicTarget();
        $zoidberg->id = 11;
        $zoidberg->foo = null;

        $subModel = new TestMonomorphicTarget();
        $subModel->id = 11;
        $subModel->foo = $zoidberg;

        $model = new TestMonomorphicTarget();
        $model->id = 11;
        $model->foo = $subModel;

        $zoidberg->foo = $model;

        $collection = new QueryResult();
        $collection->results = $model;

        $propName = 'makeItPhunkee';

        $expected = null;
        $expectedExceptionClass = null;
        $actual = null;
        $actualExceptionClass = null;

        try {
            $object->writeTopLevelComplexObject($collection, $propName, $rType);
        } catch (\Exception $e) {
            $expectedExceptionClass = get_class($e);
            $expected = $e->getMessage();
        }
        try {
            $ironic->writeTopLevelComplexObject($collection, $propName, $rType);
        } catch (\Exception $e) {
            $actualExceptionClass = get_class($e);
            $actual = $e->getMessage();
        }

        $this->assertEquals($expectedExceptionClass, $actualExceptionClass);
        $this->assertEquals($expected, $actual);
    }

    public function testMatchPrimitiveHighball()
    {
        $this->assertFalse(IronicSerialiser::isMatchPrimitive(29));
        $this->assertTrue(IronicSerialiser::isMatchPrimitive(28));
    }

    /**
     * @param $request
     * @return array
     */
    private function setUpDataServiceDeps($request)
    {
        $metadata = [];
        $metadata['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metadata['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metadata['photo'] = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];

        $testModel = new TestModel($metadata, null);
        App::instance(TestModel::class, $testModel);

        $op = new OperationContextAdapter($request);
        $host = new ServiceHost($op, $request);

        $classen = [TestModel::class];
        $metaProv = m::mock(MetadataProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $metaProv->shouldReceive('getCandidateModels')->andReturn($classen);
        $metaProv->boot();

        $meta = App::make('metadata');

        $query = m::mock(LaravelQuery::class);
        return [$host, $meta, $query];
    }
}
