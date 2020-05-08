<?php

declare(strict_types=1);

namespace Tests\Legacy\AlgoWeb\PODataLaravel\Unit\Serialisers;

use AlgoWeb\PODataLaravel\Models\MetadataGubbinsHolder;
use AlgoWeb\PODataLaravel\Models\MetadataRelationshipContainer;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Map;
use AlgoWeb\PODataLaravel\Providers\MetadataProvider;
use AlgoWeb\PODataLaravel\Query\LaravelQuery;
use AlgoWeb\PODataLaravel\Serialisers\IronicSerialiser;
use Illuminate\Support\Facades\App;
use Mockery as m;
use POData\ObjectModel\ObjectModelSerializer;
use POData\OperationContext\ServiceHost;
use AlgoWeb\PODataLaravel\OperationContext\Web\Illuminate\IlluminateOperationContext as OperationContextAdapter;
use POData\Providers\Query\QueryResult;
use POData\Providers\Query\QueryType;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestModel;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Serialisers\TestDataService;

class SerialiserWriteUrlTest extends SerialiserTestBase
{
    public function testWriteUrlForBasicModel()
    {
        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/TestModels');
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc/TestModels');

        $meta          = [];
        $meta['id']    = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $meta['name']  = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $meta['photo'] = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];

        $testModel     = new TestModel($meta, null);
        $testModel->id = 1;
        App::instance(TestModel::class, $testModel);

        $op   = new OperationContextAdapter($request);
        $host = new ServiceHost($op, $request);
        $host->setServiceUri('/odata.svc/');

        $classen  = [TestModel::class];
        $metaProv = $this->setupMockMetadataProvider($classen);
        $metaProv->boot();

        $meta = App::make('metadata');

        $query = m::mock(LaravelQuery::class);

        // default data service
        $service   = new TestDataService($query, $meta, $host);
        $processor = $service->handleRequest();
        $object    = new ObjectModelSerializer($service, $processor->getRequest());
        $ironic    = new IronicSerialiser($service, $processor->getRequest());

        $model     = new TestModel();
        $model->id = 4;

        $result          = new QueryResult();
        $result->results = $model;

        $objectResult = $object->writeUrlElement($result);
        $ironicResult = $ironic->writeUrlElement($result);
        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
    }

    public function testWriteUrlForBasicModelAsCollectionWithCount()
    {
        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/TestModels');
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc/TestModels');

        $meta          = [];
        $meta['id']    = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $meta['name']  = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $meta['photo'] = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];

        $testModel     = new TestModel($meta, null);
        $testModel->id = 1;
        App::instance(TestModel::class, $testModel);

        $op   = new OperationContextAdapter($request);
        $host = new ServiceHost($op, $request);
        $host->setServiceUri('/odata.svc/');

        $classen  = [TestModel::class];
        $metaProv = $this->setupMockMetadataProvider($classen);
        $metaProv->boot();

        $meta = App::make('metadata');

        $query = m::mock(LaravelQuery::class);

        // default data service
        $service                            = new TestDataService($query, $meta, $host);
        $processor                          = $service->handleRequest();
        $processor->getRequest()->queryType = QueryType::ENTITIES_WITH_COUNT();
        $processor->getRequest()->setCountValue(2);
        $object = new ObjectModelSerializer($service, $processor->getRequest());
        $ironic = new IronicSerialiser($service, $processor->getRequest());

        $model     = new TestModel();
        $model->id = 4;

        $result          = new QueryResult();
        $result->results = $model;

        $collection          = new QueryResult();
        $collection->results = [$result, $model];

        $objectResult = $object->writeUrlElements($collection);
        $ironicResult = $ironic->writeUrlElements($collection);
        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
    }

    public function testWriteUrlForBasicModelAsCollectionWithCountAndPageOverrun()
    {
        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/TestModels');
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc/TestModels');

        $meta          = [];
        $meta['id']    = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $meta['name']  = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $meta['photo'] = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];

        $testModel     = new TestModel($meta, null);
        $testModel->id = 1;
        App::instance(TestModel::class, $testModel);

        $op   = new OperationContextAdapter($request);
        $host = new ServiceHost($op, $request);
        $host->setServiceUri('/odata.svc/');

        $classen  = [TestModel::class];
        $metaProv = $this->setupMockMetadataProvider($classen);
        $metaProv->boot();

        $meta = App::make('metadata');

        $query = m::mock(LaravelQuery::class);

        // default data service
        $service                            = new TestDataService($query, $meta, $host);
        $service->maxPageSize               = 1;
        $processor                          = $service->handleRequest();
        $processor->getRequest()->queryType = QueryType::ENTITIES_WITH_COUNT();
        $processor->getRequest()->setCountValue(1);
        $object = new ObjectModelSerializer($service, $processor->getRequest());
        $ironic = new IronicSerialiser($service, $processor->getRequest());

        $model     = new TestModel();
        $model->id = 4;

        $result          = new QueryResult();
        $result->results = $model;

        $collection          = new QueryResult();
        $collection->results = [$result];
        $collection->hasMore = true;

        $objectResult = $object->writeUrlElements($collection);
        $ironicResult = $ironic->writeUrlElements($collection);
        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
    }

    /**
     * @param $classen
     * @return m\Mock
     */
    private function setupMockMetadataProvider($classen)
    {
        $map      = new Map();
        $holder   = new MetadataRelationshipContainer();
        $metaProv = m::mock(MetadataProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $metaProv->shouldReceive('getCandidateModels')->andReturn($classen);
        $metaProv->shouldReceive('getRelationHolder')->andReturn($holder);
        self::resetMetadataProvider($metaProv);
        App::instance('objectmap', $map);
        return $metaProv;
    }
}
