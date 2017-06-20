<?php

namespace AlgoWeb\PODataLaravel\Serialisers;

use AlgoWeb\PODataLaravel\Models\TestModel;
use AlgoWeb\PODataLaravel\Models\TestMonomorphicSource;
use AlgoWeb\PODataLaravel\Models\TestMonomorphicTarget;
use AlgoWeb\PODataLaravel\Providers\MetadataProvider;
use AlgoWeb\PODataLaravel\Query\LaravelQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Mockery as m;
use AlgoWeb\PODataLaravel\Models\TestCase as TestCase;
use POData\ObjectModel\ObjectModelSerializer;
use POData\ObjectModel\ODataPropertyContent;
use POData\OperationContext\ServiceHost;
use POData\OperationContext\Web\Illuminate\IlluminateOperationContext as OperationContextAdapter;
use POData\Providers\Metadata\SimpleMetadataProvider;
use POData\SimpleDataService as DataService;
use POData\UriProcessor\RequestDescription;
use Symfony\Component\HttpFoundation\HeaderBag;

class SerialiserWriteElementsTest extends SerialiserTestBase
{
    public function testCompareWriteMultipleModelsNoPageOverrun()
    {
        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/TestModels');
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc/TestModels');

        $meta = [];
        $meta['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $meta['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $meta['photo'] = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];

        $testModel = new TestModel($meta, null);
        App::instance(TestModel::class, $testModel);

        $op = new OperationContextAdapter($request);
        $host = new ServiceHost($op, $request);
        $host->setServiceUri("/odata.svc/");

        $classen = [TestModel::class];
        $metaProv = m::mock(MetadataProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $metaProv->shouldReceive('getCandidateModels')->andReturn($classen);
        $metaProv->boot();

        $meta = App::make('metadata');

        $query = m::mock(LaravelQuery::class);

        // default data service
        $service = new TestDataService($query, $meta, $host);
        $processor = $service->handleRequest();
        $object = new ObjectModelSerializer($service, $processor->getRequest());
        $ironic = new IronicSerialiser($service, $processor->getRequest());

        $blankProp = new ODataPropertyContent();

        $models = [new TestModel(), new TestModel()];
        $models[0]->id = 1;
        $models[1]->id = 2;
        $objectResult = $object->writeTopLevelElements($models);
        $ironicResult = $ironic->writeTopLevelElements($models);
        foreach ($objectResult->entries as $entry) {
            $entry->propertyContent = $blankProp;
        }
        foreach ($ironicResult->entries as $entry) {
            $entry->propertyContent = $blankProp;
        }
        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
    }

    public function testCompareWriteMultipleModelsHasPageOverrun()
    {
        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/TestModels');
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc/TestModels');

        $meta = [];
        $meta['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $meta['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $meta['photo'] = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];

        $testModel = new TestModel($meta, null);
        App::instance(TestModel::class, $testModel);

        $op = new OperationContextAdapter($request);
        $host = new ServiceHost($op, $request);
        $host->setServiceUri("/odata.svc/");

        $classen = [TestModel::class];
        $metaProv = m::mock(MetadataProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $metaProv->shouldReceive('getCandidateModels')->andReturn($classen);
        $metaProv->boot();

        $meta = App::make('metadata');

        $query = m::mock(LaravelQuery::class);

        $service = new TestDataService($query, $meta, $host);
        $service->maxPageSize = 1;

        $processor = $service->handleRequest();
        $object = new ObjectModelSerializer($service, $processor->getRequest());
        $ironic = new IronicSerialiser($service, $processor->getRequest());

        $blankProp = new ODataPropertyContent();

        $models = [new TestModel(), new TestModel()];
        $models[0]->id = 1;
        $models[1]->id = 2;
        $objectResult = $object->writeTopLevelElements($models);
        $ironicResult = $ironic->writeTopLevelElements($models);
        foreach ($objectResult->entries as $entry) {
            $entry->propertyContent = $blankProp;
        }
        foreach ($ironicResult->entries as $entry) {
            $entry->propertyContent = $blankProp;
        }
        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
    }
}
