<?php

namespace AlgoWeb\PODataLaravel\Serialisers;

use AlgoWeb\PODataLaravel\Models\MetadataGubbinsHolder;
use AlgoWeb\PODataLaravel\Models\MetadataRelationHolder;
use AlgoWeb\PODataLaravel\Models\TestCase as TestCase;
use AlgoWeb\PODataLaravel\Models\TestModel;
use AlgoWeb\PODataLaravel\Models\TestMonomorphicManySource;
use AlgoWeb\PODataLaravel\Models\TestMonomorphicManyTarget;
use AlgoWeb\PODataLaravel\Models\TestMonomorphicParentOfMorphTarget;
use AlgoWeb\PODataLaravel\Models\TestMonomorphicSource;
use AlgoWeb\PODataLaravel\Models\TestMonomorphicTarget;
use AlgoWeb\PODataLaravel\Models\TestMorphManySource;
use AlgoWeb\PODataLaravel\Models\TestMorphTarget;
use AlgoWeb\PODataLaravel\Providers\MetadataProvider;
use AlgoWeb\PODataLaravel\Query\LaravelQuery;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Mockery as m;
use POData\ObjectModel\ObjectModelSerializer;
use POData\ObjectModel\ODataCategory;
use POData\ObjectModel\ODataEntry;
use POData\ObjectModel\ODataFeed;
use POData\ObjectModel\ODataLink;
use POData\ObjectModel\ODataProperty;
use POData\ObjectModel\ODataPropertyContent;
use POData\ObjectModel\ODataTitle;
use POData\OperationContext\ServiceHost;
use POData\OperationContext\Web\Illuminate\IlluminateOperationContext as OperationContextAdapter;
use POData\Providers\Metadata\SimpleMetadataProvider;
use POData\Providers\Query\QueryResult;
use POData\Providers\Query\QueryType;
use POData\SimpleDataService as DataService;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\ExpandedProjectionNode;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\RootProjectionNode;
use POData\UriProcessor\RequestDescription;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\ParameterBag;

class SerialiserWriteElementTest extends SerialiserTestBase
{
    public function testCompareWriteSingleModelWithPropertiesNulled()
    {
        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/TestModels');
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc/TestModels');

        $metadata = [];
        $metadata['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metadata['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metadata['photo'] = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];

        $testModel = new TestModel($metadata, null);
        $testModel->id = 1;
        App::instance(TestModel::class, $testModel);

        $op = new OperationContextAdapter($request);
        $host = new ServiceHost($op, $request);
        $host->setServiceUri('/odata.svc/');

        $holder = new MetadataGubbinsHolder();
        $classen = [TestModel::class];
        $metaProv = m::mock(MetadataProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $metaProv->shouldReceive('getRelationHolder')->andReturn($holder);
        $metaProv->shouldReceive('getCandidateModels')->andReturn($classen);
        $metaProv->reset();
        $metaProv->boot();

        $meta = App::make('metadata');

        $query = m::mock(LaravelQuery::class);

        // default data service
        $service = new TestDataService($query, $meta, $host);
        $processor = $service->handleRequest();
        $object = new ObjectModelSerializer($service, $processor->getRequest());
        $ironic = new IronicSerialiser($service, $processor->getRequest());

        $model = new TestModel($metadata, null);
        $model->id = 4;

        $result = new QueryResult();
        $result->results = $model;

        $objectResult = $object->writeTopLevelElement($result);
        $ironicResult = $ironic->writeTopLevelElement($result);
        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
        $numProperties = count($objectResult->propertyContent->properties);
        $keys = array_keys($objectResult->propertyContent->properties);
        for ($i = 0; $i < $numProperties; $i++) {
            $propName = $objectResult->propertyContent->properties[$keys[$i]]->name;
            $objectVal = $objectResult->propertyContent->properties[$keys[$i]]->value;
            $ironicVal = $ironicResult->propertyContent->properties[$keys[$i]]->value;
            $this->assertEquals(isset($objectVal), isset($ironicVal), "Values for $propName differently null");
            $this->assertEquals(is_string($objectVal), is_string($ironicVal), "Values for $propName not identical");
        }
    }

    public function testCompareWriteSingleModelWithKeyPropertiesNulled()
    {
        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/TestModels');
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc/TestModels');

        $metadata = [];
        $metadata['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metadata['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metadata['photo'] = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];

        $testModel = new TestModel($metadata, null);
        $testModel->id = null;
        App::instance(TestModel::class, $testModel);

        $op = new OperationContextAdapter($request);
        $host = new ServiceHost($op, $request);
        $host->setServiceUri('/odata.svc/');

        $holder = new MetadataGubbinsHolder();
        $classen = [TestModel::class];
        $metaProv = m::mock(MetadataProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $metaProv->shouldReceive('getRelationHolder')->andReturn($holder);
        $metaProv->shouldReceive('getCandidateModels')->andReturn($classen);
        $metaProv->reset();
        $metaProv->boot();

        $meta = App::make('metadata');

        $query = m::mock(LaravelQuery::class);

        // default data service
        $service = new TestDataService($query, $meta, $host);
        $processor = $service->handleRequest();
        $object = new ObjectModelSerializer($service, $processor->getRequest());
        $ironic = new IronicSerialiser($service, $processor->getRequest());

        $model = new TestModel($metadata, null);
        $model->id = null;

        $result = new QueryResult();
        $result->results = $model;

        $expected = null;
        $expectedExceptionClass = null;
        $actual = null;
        $actualExceptionClass = null;

        try {
            $object->writeTopLevelElement($result);
        } catch (\Exception $e) {
            $expectedExceptionClass = get_class($e);
            $expected = $e->getMessage();
        }
        try {
            $ironic->writeTopLevelElement($result);
        } catch (\Exception $e) {
            $actualExceptionClass = get_class($e);
            $actual = $e->getMessage();
        }

        $this->assertEquals($expectedExceptionClass, $actualExceptionClass);
        $this->assertEquals($expected, $actual);
    }

    public function testCompareWriteSingleModelWithPropertiesNulledAndSingleRelation()
    {
        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/TestMonomorphicSources');
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc/TestMonomorphicSources');

        $metadata = [];
        $metadata['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metadata['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metadata['photo'] = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];
        $source = new TestMonomorphicSource($metadata, null);
        $target = new TestMonomorphicTarget($metadata, null);

        App::instance(TestMonomorphicSource::class, $source);
        App::instance(TestMonomorphicTarget::class, $target);

        $op = new OperationContextAdapter($request);
        $host = new ServiceHost($op, $request);
        $host->setServiceUri('/odata.svc/');

        $holder = new MetadataGubbinsHolder();
        $classen = [TestMonomorphicSource::class, TestMonomorphicTarget::class];
        $metaProv = m::mock(MetadataProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $metaProv->shouldReceive('getRelationHolder')->andReturn($holder);
        $metaProv->shouldReceive('getCandidateModels')->andReturn($classen);
        $metaProv->reset();
        $metaProv->boot();

        $meta = App::make('metadata');

        $query = m::mock(LaravelQuery::class);

        // default data service
        $service = new TestDataService($query, $meta, $host);
        $processor = $service->handleRequest();
        $object = new ObjectModelSerializer($service, $processor->getRequest());
        $ironic = new IronicSerialiser($service, $processor->getRequest());

        $model = new TestMonomorphicSource($metadata, null);
        $model->id = 42;

        $result = new QueryResult();
        $result->results = $model;

        $objectResult = $object->writeTopLevelElement($result);
        $ironicResult = $ironic->writeTopLevelElement($result);
        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
        $numProperties = count($objectResult->propertyContent->properties);
        $keys = array_keys($objectResult->propertyContent->properties);
        for ($i = 0; $i < $numProperties; $i++) {
            $propName = $objectResult->propertyContent->properties[$keys[$i]]->name;
            $objectVal = $objectResult->propertyContent->properties[$keys[$i]]->value;
            $ironicVal = $ironicResult->propertyContent->properties[$keys[$i]]->value;
            $this->assertEquals(isset($objectVal), isset($ironicVal), "Values for $propName differently null");
            $this->assertEquals(is_string($objectVal), is_string($ironicVal), "Values for $propName not identical");
        }
    }

    public function testCompareSingleModelAtSingletonEndOfRelation()
    {
        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/TestMonomorphicTargets');
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc/TestMonomorphicTargets');

        $metadata = [];
        $metadata['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metadata['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metadata['photo'] = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];
        $source = new TestMonomorphicSource($metadata, null);
        $target = new TestMonomorphicTarget($metadata, null);

        App::instance(TestMonomorphicSource::class, $source);
        App::instance(TestMonomorphicTarget::class, $target);

        $op = new OperationContextAdapter($request);
        $host = new ServiceHost($op, $request);
        $host->setServiceUri('/odata.svc/');

        $holder = new MetadataGubbinsHolder();
        $classen = [TestMonomorphicSource::class, TestMonomorphicTarget::class];
        $metaProv = m::mock(MetadataProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $metaProv->shouldReceive('getRelationHolder')->andReturn($holder);
        $metaProv->shouldReceive('getCandidateModels')->andReturn($classen);
        $metaProv->reset();
        $metaProv->boot();

        $meta = App::make('metadata');

        $query = m::mock(LaravelQuery::class);

        // default data service
        $service = new TestDataService($query, $meta, $host);
        $processor = $service->handleRequest();
        $object = new ObjectModelSerializer($service, $processor->getRequest());
        $ironic = new IronicSerialiser($service, $processor->getRequest());

        $model = new TestMonomorphicTarget($metadata, null);
        $model->id = 42;

        $result = new QueryResult();
        $result->results = $model;

        $objectResult = $object->writeTopLevelElement($result);
        $ironicResult = $ironic->writeTopLevelElement($result);
        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
        $numProperties = count($objectResult->propertyContent->properties);
        $keys = array_keys($objectResult->propertyContent->properties);
        for ($i = 0; $i < $numProperties; $i++) {
            $propName = $objectResult->propertyContent->properties[$keys[$i]]->name;
            $objectVal = $objectResult->propertyContent->properties[$keys[$i]]->value;
            $ironicVal = $ironicResult->propertyContent->properties[$keys[$i]]->value;
            $this->assertEquals(isset($objectVal), isset($ironicVal), "Values for $propName differently null");
            $this->assertEquals(is_string($objectVal), is_string($ironicVal), "Values for $propName not identical");
        }
    }

    public function testCompareSingleModelWithTwoExpandedProperties()
    {
        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')
            ->andReturn('/odata.svc/TestMonomorphicSources(id=42)?$expand=oneSource,manySource');
        $request->shouldReceive('fullUrl')
            ->andReturn('http://localhost/odata.svc/TestMonomorphicSources(id=42)?$expand=oneSource,manySource');
        $request->request = new ParameterBag(['$expand' => 'oneSource,manySource']);

        $metadata = [];
        $metadata['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metadata['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metadata['photo'] = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];
        $source = new TestMonomorphicSource($metadata, null);
        $target = new TestMonomorphicTarget($metadata, null);

        App::instance(TestMonomorphicSource::class, $source);
        App::instance(TestMonomorphicTarget::class, $target);

        $op = new OperationContextAdapter($request);
        $host = new ServiceHost($op, $request);
        $host->setServiceUri('/odata.svc/');

        $holder = new MetadataGubbinsHolder();
        $classen = [TestMonomorphicSource::class, TestMonomorphicTarget::class];
        $metaProv = m::mock(MetadataProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $metaProv->shouldReceive('getRelationHolder')->andReturn($holder);
        $metaProv->shouldReceive('getCandidateModels')->andReturn($classen);
        $metaProv->reset();
        $metaProv->boot();

        $meta = App::make('metadata');

        $query = m::mock(LaravelQuery::class);

        $expandNode = m::mock(ExpandedProjectionNode::class);
        $expandNode->shouldReceive('canSelectAllProperties')->andReturn(true);
        $expandNode->shouldReceive('isExpansionSpecified')->andReturn(false);
        $expandNode->shouldReceive('findNode')->andReturn(null);

        $node = m::mock(RootProjectionNode::class);
        $node->shouldReceive('getPropertyName')->andReturn('oneSource');
        $node->shouldReceive('isExpansionSpecified')->andReturn(true, true, true, false);
        $node->shouldReceive('canSelectAllProperties')->andReturn(true);
        $node->shouldReceive('findNode')->andReturn($expandNode);

        $service = new TestDataService($query, $meta, $host);
        $processor = $service->handleRequest();
        $request = $processor->getRequest();
        $request->setRootProjectionNode($node);

        $object = new ObjectModelSerializer($service, $request);
        $ironic = new IronicSerialiser($service, $request);

        $belongsTo = m::mock(BelongsTo::class)->makePartial();
        $belongsTo->shouldReceive('getResults')->andReturn(null);
        $targ = m::mock(TestMonomorphicTarget::class)->makePartial();
        $targ->shouldReceive('metadata')->andReturn($metadata);
        $targ->shouldReceive('manyTarget')->andReturn($belongsTo);
        $targ->shouldReceive('oneTarget')->andReturn($belongsTo);
        $targ->id = 11;

        $hasOne = m::mock(HasMany::class)->makePartial();
        $hasOne->shouldReceive('getResults')->andReturn($targ);

        $hasMany = m::mock(HasMany::class)->makePartial();
        $hasMany->shouldReceive('getResults')->andReturn([$targ]);

        $model = m::mock(TestMonomorphicSource::class)->makePartial();
        $model->shouldReceive('hasOne')->andReturn($hasOne);
        $model->shouldReceive('manySource')->andReturn($hasMany);
        $model->shouldReceive('metadata')->andReturn($metadata);
        $model->id = 42;

        $result = new QueryResult();
        $result->results = $model;

        $objectResult = $object->writeTopLevelElement($result);

        // check that object result is properly set up - if not, no point comparing it to anything
        $this->assertTrue($objectResult->links[0]->isExpanded);
        $this->assertFalse($objectResult->links[0]->isCollection);
        $this->assertTrue($objectResult->links[1]->isExpanded);
        $this->assertTrue($objectResult->links[1]->isCollection);

        $ironicResult = $ironic->writeTopLevelElement($result);
        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult, '', 0, 20);

        $numProperties = count($objectResult->propertyContent->properties);
        $keys = array_keys($objectResult->propertyContent->properties);
        for ($i = 0; $i < $numProperties; $i++) {
            $propName = $objectResult->propertyContent->properties[$keys[$i]]->name;
            $objectVal = $objectResult->propertyContent->properties[$keys[$i]]->value;
            $ironicVal = $ironicResult->propertyContent->properties[$keys[$i]]->value;
            $this->assertEquals(isset($objectVal), isset($ironicVal), "Values for $propName differently null");
            $this->assertEquals(is_string($objectVal), is_string($ironicVal), "Values for $propName not identical");
        }
    }

    public function testCompareSingleModelWithOffWallMetadata()
    {
        $serialiser = new ModelSerialiser();
        $serialiser->reset();
        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/TestModels');
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc/TestModels');

        $metadata = [];
        $metadata['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metadata['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metadata['is_boolean'] = ['type' => 'boolean', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metadata['created_at'] = ['type' => 'datetime', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metadata['photo'] = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];

        $testModel = new TestModel($metadata, null);
        $testModel->id = 1;
        App::instance(TestModel::class, $testModel);

        $op = new OperationContextAdapter($request);
        $host = new ServiceHost($op, $request);
        $host->setServiceUri('/odata.svc/');

        $holder = new MetadataGubbinsHolder();
        $classen = [TestModel::class];
        $metaProv = m::mock(MetadataProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $metaProv->shouldReceive('getRelationHolder')->andReturn($holder);
        $metaProv->shouldReceive('getCandidateModels')->andReturn($classen);
        $metaProv->reset();
        $metaProv->boot();

        $meta = App::make('metadata');

        $query = m::mock(LaravelQuery::class);

        // default data service
        $service = new TestDataService($query, $meta, $host);
        $processor = $service->handleRequest();
        $object = new ObjectModelSerializer($service, $processor->getRequest());
        $ironic = new IronicSerialiser($service, $processor->getRequest());

        $model = new TestModel($metadata, null);
        $model->id = 4;
        $model->name = 'Name';
        $model->is_boolean = true;
        $model->created_at = new \DateTime();

        $result = new QueryResult();
        $result->results = $model;

        $objectResult = $object->writeTopLevelElement($result);
        $ironicResult = $ironic->writeTopLevelElement($result);
        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
        $numProperties = count($objectResult->propertyContent->properties);
        $keys = array_keys($objectResult->propertyContent->properties);
        for ($i = 0; $i < $numProperties; $i++) {
            $propName = $objectResult->propertyContent->properties[$keys[$i]]->name;
            $objectVal = $objectResult->propertyContent->properties[$keys[$i]]->value;
            $ironicVal = $ironicResult->propertyContent->properties[$keys[$i]]->value;
            $this->assertEquals(isset($objectVal), isset($ironicVal), "Values for $propName differently null");
            $this->assertEquals(is_string($objectVal), is_string($ironicVal), "Values for $propName not identical");
        }
    }

    public function testSerialiseSingleModelWithNullExpansion()
    {
        $known = Carbon::create(2017, 1, 1, 0, 0, 0, 'UTC');
        Carbon::setTestNow($known);

        $serialiser = new ModelSerialiser();
        $serialiser->reset();
        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/TestMonomorphicManySources');
        $request->shouldReceive('fullUrl')
            ->andReturn('http://localhost/odata.svc/TestMonomorphicManySources(1)?$expand=manySource');

        $metadata = [];
        $metadata['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metadata['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];

        $source = new TestMonomorphicManySource($metadata);
        $target = new TestMonomorphicManyTarget($metadata);

        App::instance(TestMonomorphicManySource::class, $source);
        App::instance(TestMonomorphicManyTarget::class, $target);

        $op = new OperationContextAdapter($request);
        $host = new ServiceHost($op, $request);
        $host->setServiceUri('/odata.svc/');

        $holder = new MetadataGubbinsHolder();
        $classen = [TestMonomorphicManySource::class, TestMonomorphicManyTarget::class];
        $metaProv = m::mock(MetadataProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $metaProv->shouldReceive('getRelationHolder')->andReturn($holder);
        $metaProv->shouldReceive('getCandidateModels')->andReturn($classen);
        $metaProv->reset();
        $metaProv->boot();

        $meta = App::make('metadata');

        $query = m::mock(LaravelQuery::class);

        $stack = [
            ['type' => 'TestMonomorphicManySource', 'prop' => 'manySource', 'count' => 1],
        ];

        // default data service
        $service = new TestDataService($query, $meta, $host);
        $processor = $service->handleRequest();
        $ironic = new IronicSerialiserDummy($service, $processor->getRequest());
        $ironic->setLightStack($stack);

        $model = new TestMonomorphicManySource($metadata, null);
        $model->id = 1;
        $model->name = 'Name';

        $result = new QueryResult();
        $result->results = $model;

        $propContent = new ODataPropertyContent();
        $propContent->properties = ['id' => new ODataProperty(), 'name' => new ODataProperty()];
        $propContent->properties['id']->name = 'id';
        $propContent->properties['name']->name = 'name';
        $propContent->properties['id']->typeName = 'Edm.Int32';
        $propContent->properties['name']->typeName = 'Edm.String';
        $propContent->properties['id']->value = '1';
        $propContent->properties['name']->value = 'Name';

        $link = new ODataLink();
        $link->name = 'http://schemas.microsoft.com/ado/2007/08/dataservices/related/manySource';
        $link->title = 'manySource';
        $link->type = 'application/atom+xml;type=feed';
        $link->url = 'TestMonomorphicManySources(id=1)/manySource';

        $expected = new ODataEntry();
        $expected->id = 'http://localhost/odata.svc/TestMonomorphicManySources(id=1)';
        $expected->title = new ODataTitle('TestMonomorphicManySource');
        $expected->editLink = 'TestMonomorphicManySources(id=1)';
        $expected->editLink = new ODataLink();
        $expected->editLink->url = 'TestMonomorphicManySources(id=1)';
        $expected->editLink->name = 'edit';
        $expected->editLink->title = 'TestMonomorphicManySource';
        $expected->type = new ODataCategory('TestMonomorphicManySource');
        $expected->isMediaLinkEntry = false;
        $expected->resourceSetName = 'TestMonomorphicManySources';
        $expected->links = [$link];
        $expected->propertyContent = $propContent;
        $expected->updated = '2017-01-01T00:00:00+00:00';
        $expected->baseURI = 'http://localhost/odata.svc/';

        $actual = $ironic->writeTopLevelElement($result);
        $this->assertEquals($expected, $actual);
    }

    public function testSerialiseSingleModelWithTwoSubordinatesExpansion()
    {
        $known = Carbon::create(2017, 1, 1, 0, 0, 0, 'UTC');
        Carbon::setTestNow($known);

        $serialiser = new ModelSerialiser();
        $serialiser->reset();
        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/TestMonomorphicSources');
        $request->shouldReceive('fullUrl')
            ->andReturn('http://localhost/odata.svc/TestMonomorphicSources(1)?$expand=manySource');

        $metadata = [];
        $metadata['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metadata['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];

        $source = new TestMonomorphicSource($metadata);
        $target = new TestMonomorphicTarget($metadata);

        App::instance(TestMonomorphicSource::class, $source);
        App::instance(TestMonomorphicTarget::class, $target);

        $op = new OperationContextAdapter($request);
        $host = new ServiceHost($op, $request);
        $host->setServiceUri('/odata.svc/');

        $holder = new MetadataGubbinsHolder();
        $classen = [TestMonomorphicSource::class, TestMonomorphicTarget::class];
        $metaProv = m::mock(MetadataProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $metaProv->shouldReceive('getRelationHolder')->andReturn($holder);
        $metaProv->shouldReceive('getCandidateModels')->andReturn($classen);
        $metaProv->reset();
        $metaProv->boot();

        $meta = App::make('metadata');

        $query = m::mock(LaravelQuery::class);

        $stack = [
            ['type' => 'TestMonomorphicSource', 'prop' => 'TestMonomorphicSource', 'count' => 1],
        ];

        $op = new OperationContextAdapter($request);
        $host = new ServiceHost($op, $request);
        $host->setServiceUri('/odata.svc/');

        $service = new TestDataService($query, $meta, $host);
        $processor = $service->handleRequest();
        $ironic = new IronicSerialiserDummy($service, $processor->getRequest());
        $ironic->setLightStack($stack);
        $ironic->setPropertyExpansion('manySource');
        $ironic->setPropertyExpansion('oneSource', false);
        $ironic->setPropertyExpansion('oneTarget', false);
        $ironic->setPropertyExpansion('manyTarget', false);

        $targ1 = new TestMonomorphicTarget($metadata);
        $targ1->id = 1;
        $targ1->name = 'Inspector';

        $targ2 = new TestMonomorphicTarget($metadata);
        $targ2->id = 2;
        $targ2->name = 'Gadget';

        $meta = App::make('metadata');

        $query = m::mock(LaravelQuery::class);

        $relation = m::mock(HasMany::class)->makePartial();
        $relation->shouldReceive('get')->andReturn(collect([$targ1, $targ2]));

        $model = m::mock(TestMonomorphicSource::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $model->shouldReceive('metadata')->andReturn($metadata);
        $model->id = 1;
        $model->name = 'Name';
        $model->shouldReceive('getAttribute')->withArgs(['manySource'])->andReturn(([$targ1, $targ2]));

        $result = new QueryResult();
        $result->results = $model;

        $propContent = new ODataPropertyContent();
        $propContent->properties = ['id' => new ODataProperty(), 'name' => new ODataProperty()];
        $propContent->properties['id']->name = 'id';
        $propContent->properties['name']->name = 'name';
        $propContent->properties['id']->typeName = 'Edm.Int32';
        $propContent->properties['name']->typeName = 'Edm.String';
        $propContent->properties['id']->value = '1';
        $propContent->properties['name']->value = 'Name';

        $feed1Content = new ODataPropertyContent();
        $feed1Content->properties = ['id' => new ODataProperty(), 'name' => new ODataProperty()];
        $feed1Content->properties['id']->name = 'id';
        $feed1Content->properties['name']->name = 'name';
        $feed1Content->properties['id']->typeName = 'Edm.Int32';
        $feed1Content->properties['name']->typeName = 'Edm.String';
        $feed1Content->properties['id']->value = '1';
        $feed1Content->properties['name']->value = 'Inspector';

        $feed2Content = new ODataPropertyContent();
        $feed2Content->properties = ['id' => new ODataProperty(), 'name' => new ODataProperty()];
        $feed2Content->properties['id']->name = 'id';
        $feed2Content->properties['name']->name = 'name';
        $feed2Content->properties['id']->typeName = 'Edm.Int32';
        $feed2Content->properties['name']->typeName = 'Edm.String';
        $feed2Content->properties['id']->value = '2';
        $feed2Content->properties['name']->value = 'Gadget';

        $feed1 = new ODataEntry();
        $feed1->id = 'http://localhost/odata.svc/TestMonomorphicTargets(id=1)';
        $feed1->title = new ODataTitle('TestMonomorphicTarget');
        $feed1->editLink = new ODataLink();
        $feed1->editLink->url = 'TestMonomorphicTargets(id=1)';
        $feed1->editLink->name = 'edit';
        $feed1->editLink->title = 'TestMonomorphicTarget';
        $feed1->type = new ODataCategory('TestMonomorphicTarget');
        $feed1->propertyContent = $feed1Content;
        $feed1->isMediaLinkEntry = false;
        $feed1->resourceSetName = 'TestMonomorphicTargets';
        $feed1->updated = '2017-01-01T00:00:00+00:00';

        $feed2 = new ODataEntry();
        $feed2->id = 'http://localhost/odata.svc/TestMonomorphicTargets(id=2)';
        $feed2->title = new ODataTitle('TestMonomorphicTarget');
        $feed2->editLink = new ODataLink();
        $feed2->editLink->url = 'TestMonomorphicTargets(id=2)';
        $feed2->editLink->name = 'edit';
        $feed2->editLink->title = 'TestMonomorphicTarget';
        $feed2->type = new ODataCategory('TestMonomorphicTarget');
        $feed2->propertyContent = $feed2Content;
        $feed2->isMediaLinkEntry = false;
        $feed2->resourceSetName = 'TestMonomorphicTargets';
        $feed2->updated = '2017-01-01T00:00:00+00:00';

        $feedLink = new ODataLink();
        $feedLink->name = 'self';
        $feedLink->title = 'manySource';
        $feedLink->url = 'TestMonomorphicSources(id=1)/manySource';

        $feed = new ODataFeed();
        $feed->id = 'http://localhost/odata.svc/TestMonomorphicSources(id=1)/manySource';
        $feed->title = new ODataTitle('manySource');
        $feed->selfLink = $feedLink;
        $feed->entries = [$feed1, $feed2];
        $feed->updated = '2017-01-01T00:00:00+00:00';

        $link1 = new ODataLink();
        $link1->name = 'http://schemas.microsoft.com/ado/2007/08/dataservices/related/oneSource';
        $link1->title = 'oneSource';
        $link1->type = 'application/atom+xml;type=entry';
        $link1->url = 'TestMonomorphicSources(id=1)/oneSource';

        $link2 = new ODataLink();
        $link2->name = 'http://schemas.microsoft.com/ado/2007/08/dataservices/related/manySource';
        $link2->title = 'manySource';
        $link2->type = 'application/atom+xml;type=feed';
        $link2->url = 'TestMonomorphicSources(id=1)/manySource';
        $link2->isCollection = true;
        $link2->isExpanded = true;
        $link2->expandedResult = $feed;

        $expected = new ODataEntry();
        $expected->id = 'http://localhost/odata.svc/TestMonomorphicSources(id=1)';
        $expected->title = new ODataTitle('TestMonomorphicSource');
        $expected->editLink = new ODataLink();
        $expected->editLink->url = 'TestMonomorphicSources(id=1)';
        $expected->editLink->name = 'edit';
        $expected->editLink->title = 'TestMonomorphicSource';
        $expected->type = new ODataCategory('TestMonomorphicSource');
        $expected->propertyContent = $propContent;
        $expected->links = [$link1, $link2];
        $expected->isMediaLinkEntry = false;
        $expected->resourceSetName = 'TestMonomorphicSources';
        $expected->updated = '2017-01-01T00:00:00+00:00';
        $expected->baseURI = 'http://localhost/odata.svc/';

        $actual = $ironic->writeTopLevelElement($result);
        // not too worried about the TestMonomorphicTarget links, so zeroing them out here
        $actual->links[1]->expandedResult->entries[0]->links = [];
        $actual->links[1]->expandedResult->entries[1]->links = [];
        $this->assertEquals($expected, $actual);
    }

    public function testExpandKnownSideModelOverOneToManyPolymorphic()
    {
        $known = Carbon::create(2017, 1, 1, 0, 0, 0, 'UTC');
        Carbon::setTestNow($known);

        $serialiser = new ModelSerialiser();
        $serialiser->reset();
        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/TestMorphManySources');
        $request->shouldReceive('fullUrl')
            ->andReturn('http://localhost/odata.svc/TestMorphManySources(1)?$expand=morphTarget');

        $metadata = [];
        $metadata['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metadata['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];

        $source = new TestMorphManySource($metadata);
        $target = new TestMorphTarget($metadata);

        App::instance(TestMorphManySource::class, $source);
        App::instance(TestMorphTarget::class, $target);

        $op = new OperationContextAdapter($request);
        $host = new ServiceHost($op, $request);
        $host->setServiceUri('/odata.svc/');

        $holder = new MetadataGubbinsHolder();
        $classen = [TestMorphManySource::class, TestMorphTarget::class];
        shuffle($classen);
        $metaProv = m::mock(MetadataProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $metaProv->shouldReceive('getRelationHolder')->andReturn($holder);
        $metaProv->shouldReceive('getCandidateModels')->andReturn($classen);
        $metaProv->reset();
        $metaProv->boot();

        $meta = App::make('metadata');

        $query = m::mock(LaravelQuery::class);

        $stack = [
            ['type' => 'TestMorphManySource', 'prop' => 'TestMorphManySource', 'count' => 1],
        ];

        $op = new OperationContextAdapter($request);
        $host = new ServiceHost($op, $request);
        $host->setServiceUri('/odata.svc/');

        $service = new TestDataService($query, $meta, $host);
        $processor = $service->handleRequest();
        $ironic = new IronicSerialiserDummy($service, $processor->getRequest());
        $ironic->setLightStack($stack);
        $ironic->setPropertyExpansion('morphTarget');
        $ironic->setPropertyExpansion('morph', false);

        $targ1 = new TestMorphTarget($metadata);
        $targ1->id = 1;
        $targ1->name = 'Inspector';

        $targ2 = new TestMorphTarget($metadata);
        $targ2->id = 2;
        $targ2->name = 'Gadget';

        $meta = App::make('metadata');

        $query = m::mock(LaravelQuery::class);

        $relation = m::mock(MorphMany::class)->makePartial();
        $relation->shouldReceive('get')->andReturn(collect([$targ1, $targ2]));

        $model = m::mock(TestMorphManySource::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $model->shouldReceive('metadata')->andReturn($metadata);
        $model->id = 1;
        $model->name = 'Name';
        $model->shouldReceive('getAttribute')->withArgs(['morphTarget'])->andReturn(([$targ1, $targ2]));

        $result = new QueryResult();
        $result->results = $model;

        $actual = $ironic->writeTopLevelElement($result);
        $expectedLinksCount = 2;
        $actualLinksCount = count($actual->links[0]->expandedResult->entries);
        $this->assertEquals($expectedLinksCount, $actualLinksCount);
    }

    public function testExpandKnownSideModelOverOneToManyMonomorphic()
    {
        $known = Carbon::create(2017, 1, 1, 0, 0, 0, 'UTC');
        Carbon::setTestNow($known);

        $serialiser = new ModelSerialiser();
        $serialiser->reset();
        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/TestMonomorphicParentOfMorphTargets');
        $request->shouldReceive('fullUrl')
            ->andReturn('http://localhost/odata.svc/TestMonomorphicParentOfMorphTargets(1)?$expand=morphTargets');

        $metadata = [];
        $metadata['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metadata['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];

        $source = new TestMonomorphicParentOfMorphTarget($metadata);
        $target = new TestMorphTarget($metadata);

        App::instance(TestMonomorphicParentOfMorphTarget::class, $source);
        App::instance(TestMorphTarget::class, $target);

        $op = new OperationContextAdapter($request);
        $host = new ServiceHost($op, $request);
        $host->setServiceUri('/odata.svc/');

        $holder = new MetadataGubbinsHolder();
        $classen = [TestMonomorphicParentOfMorphTarget::class, TestMorphTarget::class];
        shuffle($classen);
        $metaProv = m::mock(MetadataProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $metaProv->shouldReceive('getRelationHolder')->andReturn($holder);
        $metaProv->shouldReceive('getCandidateModels')->andReturn($classen);
        $metaProv->reset();
        $metaProv->boot();

        $meta = App::make('metadata');

        $query = m::mock(LaravelQuery::class);

        $stack = [
            ['type' => 'TestMonomorphicParentOfMorphTarget',
                'prop' => 'TestMonomorphicParentOfMorphTarget',
                'count' => 1],
        ];

        $op = new OperationContextAdapter($request);
        $host = new ServiceHost($op, $request);
        $host->setServiceUri('/odata.svc/');

        $service = new TestDataService($query, $meta, $host);
        $processor = $service->handleRequest();
        $ironic = new IronicSerialiserDummy($service, $processor->getRequest());
        $ironic->setLightStack($stack);
        $ironic->setPropertyExpansion('morphTargets');
        $ironic->setPropertyExpansion('monomorphicParent', false);

        $targ1 = new TestMorphTarget($metadata);
        $targ1->id = 1;
        $targ1->name = 'Inspector';

        $targ2 = new TestMorphTarget($metadata);
        $targ2->id = 2;
        $targ2->name = 'Gadget';

        $meta = App::make('metadata');

        $query = m::mock(LaravelQuery::class);

        $relation = m::mock(HasMany::class)->makePartial();
        $relation->shouldReceive('get')->andReturn(collect([$targ1, $targ2]));

        $model = m::mock(TestMonomorphicParentOfMorphTarget::class)->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $model->shouldReceive('metadata')->andReturn($metadata);
        $model->id = 1;
        $model->name = 'Name';
        $model->shouldReceive('getAttribute')->withArgs(['morphTargets'])->andReturn(collect([$targ1, $targ2]));

        $result = new QueryResult();
        $result->results = $model;

        $actual = $ironic->writeTopLevelElement($result);
        $expectedLinksCount = 2;
        $actualLinksCount = count($actual->links[0]->expandedResult->entries);
        $this->assertEquals($expectedLinksCount, $actualLinksCount);
    }
}
