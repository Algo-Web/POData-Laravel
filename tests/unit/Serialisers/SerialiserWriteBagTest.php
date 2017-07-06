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

class SerialiserWriteBagTest extends SerialiserTestBase
{
    public function testWriteNullBagObject()
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
        $collection->results = null;

        $objectResult = $object->writeTopLevelBagObject($collection, $propName, $rType);
        $ironicResult = $ironic->writeTopLevelBagObject($collection, $propName, $rType);

        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
    }

    public function testWriteEmptyBagObject()
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
        $rType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::PRIMITIVE);

        $collection = new QueryResult();
        $collection->results = [];

        $objectResult = $object->writeTopLevelBagObject($collection, $propName, $rType);
        $ironicResult = $ironic->writeTopLevelBagObject($collection, $propName, $rType);

        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
    }

    public function testWriteBagObjectOfPrimitiveTypes()
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

        $iType = new StringType();

        $propName = 'makeItPhunkee';
        $rType = m::mock(ResourceType::class);
        $rType->shouldReceive('getFullName')->andReturn('stopHammerTime');
        $rType->shouldReceive('getName')->andReturn('tooLegitToQuit');
        $rType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::PRIMITIVE);
        $rType->shouldReceive('getInstanceType')->andReturn($iType);

        $collection = new QueryResult();
        $collection->results = ['eins', 'zwei', 'polizei'];

        $objectResult = $object->writeTopLevelBagObject($collection, $propName, $rType);
        $ironicResult = $ironic->writeTopLevelBagObject($collection, $propName, $rType);

        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
    }

    public function testWriteBagObjectOfPrimitiveTypesIncludingNulls()
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

        $iType = new StringType();

        $propName = 'makeItPhunkee';
        $rType = m::mock(ResourceType::class);
        $rType->shouldReceive('getFullName')->andReturn('stopHammerTime');
        $rType->shouldReceive('getName')->andReturn('tooLegitToQuit');
        $rType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::PRIMITIVE);
        $rType->shouldReceive('getInstanceType')->andReturn($iType);

        $collection = new QueryResult();
        $collection->results = ['eins', null, 'zwei', null, 'polizei'];

        $objectResult = $object->writeTopLevelBagObject($collection, $propName, $rType);
        $ironicResult = $ironic->writeTopLevelBagObject($collection, $propName, $rType);

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
        return [$host, $meta, $query];
    }
}
