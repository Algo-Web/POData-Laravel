<?php

namespace AlgoWeb\PODataLaravel\Serialisers;

use AlgoWeb\PODataLaravel\Models\TestModel;
use AlgoWeb\PODataLaravel\Providers\MetadataProvider;
use AlgoWeb\PODataLaravel\Query\LaravelQuery;
use Illuminate\Support\Facades\App;
use POData\ObjectModel\ObjectModelSerializer;
use POData\OperationContext\ServiceHost;
use POData\OperationContext\Web\Illuminate\IlluminateOperationContext as OperationContextAdapter;
use Mockery as m;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\Type\Binary;
use POData\Providers\Metadata\Type\DateTime;
use POData\Providers\Metadata\Type\Int32;
use POData\Providers\Metadata\Type\IType;
use POData\Providers\Metadata\Type\StringType;
use POData\Providers\Query\QueryResult;
use POData\Providers\Query\QueryType;

class SerialiserWritePrimitiveTest extends SerialiserTestBase
{
    /**
     *
     */
    public function testCompareWriteNullResourceProperty()
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

        $collection = new QueryResult();
        $collection->results = null;

        $resProp = null;

        $expected = null;
        $expectedExceptionClass = null;
        $actual = null;
        $actualExceptionClass = null;

        try {
            $object->writeTopLevelPrimitive($collection, $resProp);
        } catch (\Exception $e) {
            $expectedExceptionClass = get_class($e);
            $expected = $e->getMessage();
        }
        try {
            $ironic->writeTopLevelPrimitive($collection, $resProp);
        } catch (\Exception $e) {
            $actualExceptionClass = get_class($e);
            $actual = $e->getMessage();
        }

        $this->assertEquals($expectedExceptionClass, $actualExceptionClass);
        $this->assertEquals($expected, $actual);
    }

    public function testCompareWriteNullPrimitiveValue()
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

        $collection = new QueryResult();
        $collection->results = null;

        $iType = m::mock(IType::class);
        $iType->shouldReceive('getFullTypeName')->andReturn('String');

        $resProp = m::mock(ResourceProperty::class);
        $resProp->shouldReceive('getName')->andReturn('DesertWithNoName');
        $resProp->shouldReceive('getInstanceType')->andReturn($iType);

        $objectResult = $object->writeTopLevelPrimitive($collection, $resProp);
        $ironicResult = $ironic->writeTopLevelPrimitive($collection, $resProp);

        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
    }

    public function testCompareWriteNonNullPrimitiveValue()
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

        $collection = new QueryResult();
        $collection->results = 'MakeItPhunkee';

        $iType = new StringType();

        $rType = m::mock(ResourceType::class);
        $rType->shouldReceive('getFullTypeName')->andReturn('String');
        $rType->shouldReceive('getInstanceType')->andReturn($iType);

        $iType = m::mock(IType::class);
        $iType->shouldReceive('getFullTypeName')->andReturn('String');

        $resProp = m::mock(ResourceProperty::class);
        $resProp->shouldReceive('getName')->andReturn('DesertWithNoName');
        $resProp->shouldReceive('getResourceType')->andReturn($rType);
        $resProp->shouldReceive('getInstanceType')->andReturn($iType);

        $objectResult = $object->writeTopLevelPrimitive($collection, $resProp);
        $ironicResult = $ironic->writeTopLevelPrimitive($collection, $resProp);

        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
    }

    public function testCompareWriteDateTimePrimitiveValue()
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

        $collection = new QueryResult();
        $collection->results = new \DateTime();

        $iType = new DateTime();

        $rType = m::mock(ResourceType::class);
        $rType->shouldReceive('getFullTypeName')->andReturn('String');
        $rType->shouldReceive('getInstanceType')->andReturn($iType);

        $iType = m::mock(IType::class);
        $iType->shouldReceive('getFullTypeName')->andReturn('String');

        $resProp = m::mock(ResourceProperty::class);
        $resProp->shouldReceive('getName')->andReturn('DesertWithNoName');
        $resProp->shouldReceive('getResourceType')->andReturn($rType);
        $resProp->shouldReceive('getInstanceType')->andReturn($iType);

        $objectResult = $object->writeTopLevelPrimitive($collection, $resProp);
        $ironicResult = $ironic->writeTopLevelPrimitive($collection, $resProp);

        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
    }

    public function testCompareWriteBinaryPrimitiveValue()
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

        $collection = new QueryResult();
        $collection->results = 'StartTheDance';

        $iType = new Binary();

        $rType = m::mock(ResourceType::class);
        $rType->shouldReceive('getFullTypeName')->andReturn('String');
        $rType->shouldReceive('getInstanceType')->andReturn($iType);

        $iType = m::mock(IType::class);
        $iType->shouldReceive('getFullTypeName')->andReturn('String');

        $resProp = m::mock(ResourceProperty::class);
        $resProp->shouldReceive('getName')->andReturn('DesertWithNoName');
        $resProp->shouldReceive('getResourceType')->andReturn($rType);
        $resProp->shouldReceive('getInstanceType')->andReturn($iType);

        $objectResult = $object->writeTopLevelPrimitive($collection, $resProp);
        $ironicResult = $ironic->writeTopLevelPrimitive($collection, $resProp);

        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
    }

    public function testCompareWriteNonStringPrimitiveValue()
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

        $collection = new QueryResult();
        $collection->results = 311;

        $iType = new Int32();

        $rType = m::mock(ResourceType::class);
        $rType->shouldReceive('getFullTypeName')->andReturn('String');
        $rType->shouldReceive('getInstanceType')->andReturn($iType);

        $iType = m::mock(IType::class);
        $iType->shouldReceive('getFullTypeName')->andReturn('String');

        $resProp = m::mock(ResourceProperty::class);
        $resProp->shouldReceive('getName')->andReturn('DesertWithNoName');
        $resProp->shouldReceive('getResourceType')->andReturn($rType);
        $resProp->shouldReceive('getInstanceType')->andReturn($iType);

        $objectResult = $object->writeTopLevelPrimitive($collection, $resProp);
        $ironicResult = $ironic->writeTopLevelPrimitive($collection, $resProp);

        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
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
        return array($host, $meta, $query);
    }
}
