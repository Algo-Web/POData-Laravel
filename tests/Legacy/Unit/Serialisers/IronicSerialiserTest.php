<?php

namespace Tests\Legacy\AlgoWeb\PODataLaravel\Unit\Serialisers;

use AlgoWeb\PODataLaravel\Models\MetadataGubbinsHolder;
use AlgoWeb\PODataLaravel\Providers\MetadataProvider;
use AlgoWeb\PODataLaravel\Query\LaravelQuery;
use AlgoWeb\PODataLaravel\Serialisers\IronicSerialiser;
use AlgoWeb\PODataLaravel\Serialisers\ModelSerialiser;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Mockery as m;
use POData\Common\InvalidOperationException;
use POData\Common\ODataConstants;
use POData\Common\Url;
use POData\IService;
use POData\ObjectModel\ODataCategory;
use POData\ObjectModel\ODataEntry;
use POData\ObjectModel\ODataFeed;
use POData\ObjectModel\ODataLink;
use POData\ObjectModel\ODataMediaLink;
use POData\ObjectModel\ODataProperty;
use POData\ObjectModel\ODataPropertyContent;
use POData\ObjectModel\ODataTitle;
use POData\OperationContext\IOperationContext;
use POData\OperationContext\ServiceHost;
use POData\OperationContext\Web\Illuminate\IlluminateOperationContext;
use POData\Providers\Metadata\ResourceEntityType;
use POData\Providers\Metadata\ResourceSetWrapper;
use POData\Providers\Metadata\SimpleMetadataProvider;
use POData\Providers\ProvidersWrapper;
use POData\Providers\Query\QueryResult;
use POData\Providers\Stream\StreamProviderWrapper;
use POData\SimpleDataService;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\ExpandedProjectionNode;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\RootProjectionNode;
use POData\UriProcessor\QueryProcessor\OrderByParser\InternalOrderByInfo;
use POData\UriProcessor\RequestDescription;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMonomorphicManySource;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMonomorphicManyTarget;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMonomorphicSource;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMonomorphicTarget;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMorphOneSource;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMorphOneSourceAlternate;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMorphTarget;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Serialisers\IronicSerialiserDummy;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Serialisers\TestDataService;

class IronicSerialiserTest extends SerialiserTestBase
{
    public function testSetGetRequestRoundTrip()
    {
        $mockService = m::mock(IService::class);
        $mockService->shouldReceive('getHost->getAbsoluteServiceUri->getUrlAsString')
            ->andReturn('http://localhost/odata.svc/Models');
        $oldRequest = m::mock(RequestDescription::class)->makePartial();

        $request = m::mock(RequestDescription::class)->makePartial();
        $request->shouldReceive('getName')->andReturn('request')->once();

        $foo = new IronicSerialiser($mockService, $oldRequest);
        $foo->setRequest($request);
        $result = $foo->getRequest();
        $this->assertEquals('request', $result->getName());
    }

    public function testGetCurrentExpandedProjectionNodeNothingToReturn()
    {
        $mockService = m::mock(IService::class);
        $mockService->shouldReceive('getHost->getAbsoluteServiceUri->getUrlAsString')
            ->andReturn('http://localhost/odata.svc/Models');
        $request = m::mock(RequestDescription::class)->makePartial();
        $request->shouldReceive('getRootProjectionNode')->andReturn(null)->once();

        $foo = new IronicSerialiserDummy($mockService, $request);
        $this->assertNull($foo->getCurrentExpandedProjectionNode());
    }

    public function testGetProjectionNodesNothingToReturn()
    {
        $mockService = m::mock(IService::class);
        $mockService->shouldReceive('getHost->getAbsoluteServiceUri->getUrlAsString')
            ->andReturn('http://localhost/odata.svc/Models');
        $request = m::mock(RequestDescription::class)->makePartial();
        $request->shouldReceive('getRootProjectionNode')->andReturn(null)->once();

        $foo = new IronicSerialiserDummy($mockService, $request);
        $this->assertNull($foo->getProjectionNodes());
    }

    public function testGetProjectionNodesEverythingToReturn()
    {
        $mockService = m::mock(IService::class);
        $mockService->shouldReceive('getHost->getAbsoluteServiceUri->getUrlAsString')
            ->andReturn('http://localhost/odata.svc/Models');
        $node = m::mock(RootProjectionNode::class);
        $node->shouldReceive('canSelectAllProperties')->withAnyArgs()->andReturn(true)->once();
        $request = m::mock(RequestDescription::class)->makePartial();
        $request->shouldReceive('getRootProjectionNode')->andReturn($node)->once();

        $foo = new IronicSerialiserDummy($mockService, $request);
        $this->assertNull($foo->getProjectionNodes());
    }

    public function testGetProjectionNodesSonethingToReturn()
    {
        $mockService = m::mock(IService::class);
        $mockService->shouldReceive('getHost->getAbsoluteServiceUri->getUrlAsString')
            ->andReturn('http://localhost/odata.svc/Models');
        $kidNode = m::mock(ExpandedProjectionNode::class);
        $node = m::mock(RootProjectionNode::class);
        $node->shouldReceive('canSelectAllProperties')->withAnyArgs()->andReturn(false)->once();
        $node->shouldReceive('getChildNodes')->andReturn([$kidNode])->once();
        $request = m::mock(RequestDescription::class)->makePartial();
        $request->shouldReceive('getRootProjectionNode')->andReturn($node)->once();

        $foo = new IronicSerialiserDummy($mockService, $request);
        $result = $foo->getProjectionNodes();
        $this->assertTrue(is_array($result));
        $this->assertEquals(1, count($result));
        $this->assertEquals($kidNode, $result[0]);
    }

    public function testShouldExpandSegmentWithNoExpandedNode()
    {
        $mockService = m::mock(IService::class);
        $mockService->shouldReceive('getHost->getAbsoluteServiceUri->getUrlAsString')
            ->andReturn('http://localhost/odata.svc/Models');
        $request = m::mock(RequestDescription::class)->makePartial();
        $request->shouldReceive('getRootProjectionNode')->andReturn(null)->once();

        $foo = new IronicSerialiserDummy($mockService, $request);
        $this->assertFalse($foo->shouldExpandSegment('segment'));
    }

    public function testGetCurrentExpandedProjectionNodeNeedsExpansionAsRoot()
    {
        $mockService = m::mock(IService::class);
        $mockService->shouldReceive('getHost->getAbsoluteServiceUri->getUrlAsString')
            ->andReturn('http://localhost/odata.svc/Models');
        $kidNode = m::mock(ExpandedProjectionNode::class);
        $node = m::mock(RootProjectionNode::class);
        $node->shouldReceive('findNode')->withArgs(['Models'])->andReturn($kidNode)->never();
        $node->shouldReceive('isExpansionSpecified')->andReturn(true)->once();
        $request = m::mock(RequestDescription::class)->makePartial();
        $request->shouldReceive('getRootProjectionNode')->andReturn($node)->times(2);

        $segName = 'Models';
        $segWrapper = m::mock(ResourceSetWrapper::class);

        $foo = new IronicSerialiserDummy($mockService, $request);
        $foo->getStack()->pushSegment($segName, $segWrapper);

        $result = $foo->getCurrentExpandedProjectionNode();
        $this->assertTrue($result instanceof ExpandedProjectionNode);
    }

    public function testGetCurrentExpandedProjectionNodeNeedsExpansionTwoLevels()
    {
        $mockService = m::mock(IService::class);
        $mockService->shouldReceive('getHost->getAbsoluteServiceUri->getUrlAsString')
            ->andReturn('http://localhost/odata.svc/Models');
        $kidNode = m::mock(ExpandedProjectionNode::class);
        $node = m::mock(RootProjectionNode::class);
        $node->shouldReceive('isExpansionSpecified')->andReturn(true);
        $node->shouldReceive('getPropertyName')->andReturn('Scatman John');
        $request = m::mock(RequestDescription::class)->makePartial();
        $request->shouldReceive('getRootProjectionNode')->andReturn($node)->times(1);

        $foo = new IronicSerialiserDummy($mockService, $request);

        $result = $foo->getCurrentExpandedProjectionNode();
        $this->assertTrue($result instanceof ExpandedProjectionNode);
        $this->assertEquals('Scatman John', $result->getPropertyName());
    }

    public function testGetCurrentExpandedProjectionNodeNeedsExpansionThreeLevelsEndUpNull()
    {
        $mockService = m::mock(IService::class);
        $mockService->shouldReceive('getHost->getAbsoluteServiceUri->getUrlAsString')
            ->andReturn('http://localhost/odata.svc/Models');
        $node = m::mock(RootProjectionNode::class);
        $node->shouldReceive('findNode')->withArgs(['edge'])->andReturn(null)->once();
        $request = m::mock(RequestDescription::class)->makePartial();
        $request->shouldReceive('getRootProjectionNode')->andReturn($node)->times(1);

        $stack = [ ['type' => 'Models', 'prop' => 'Models'],
            ['type' => 'Models', 'prop' => 'edge'],
            ['type' => 'Models', 'prop' => 'edge']
        ];

        $foo = new IronicSerialiserDummy($mockService, $request);
        $foo->setLightStack($stack);

        $expected = 'is_null($expandedProjectionNode)';
        $actual = null;

        try {
            $result = $foo->getCurrentExpandedProjectionNode();
        } catch (InvalidOperationException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetCurrentExpandedProjectionNodeNeedsExpansionThreeLevelsEndUpNotANode()
    {
        $mockService = m::mock(IService::class);
        $mockService->shouldReceive('getHost->getAbsoluteServiceUri->getUrlAsString')
            ->andReturn('http://localhost/odata.svc/Models');
        $node = m::mock(RootProjectionNode::class);
        $node->shouldReceive('findNode')->withArgs(['edge'])->andReturn('eins')->times(1);
        $request = m::mock(RequestDescription::class)->makePartial();
        $request->shouldReceive('getRootProjectionNode')->andReturn($node)->times(1);

        $stack = [ ['type' => 'Models', 'prop' => 'Models'],
            ['type' => 'Models', 'prop' => 'edge'],
            ['type' => 'Models', 'prop' => 'edge']
        ];

        $foo = new IronicSerialiserDummy($mockService, $request);
        $foo->setLightStack($stack);

        $expected = '$expandedProjectionNode not instanceof ExpandedProjectionNode';
        $actual = null;

        try {
            $result = $foo->getCurrentExpandedProjectionNode();
        } catch (InvalidOperationException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testNeedNextPageLineNotOnRoot()
    {
        $segWrapper = m::mock(ResourceSetWrapper::class);
        $segWrapper->shouldReceive('getResourceSetPageSize')->andReturn(10);

        $mockService = m::mock(IService::class);
        $mockService->shouldReceive('getHost->getAbsoluteServiceUri->getUrlAsString')
            ->andReturn('http://localhost/odata.svc/Models');
        $request = m::mock(RequestDescription::class)->makePartial();
        $request->shouldReceive('getTargetResourceSetWrapper')->andReturn($segWrapper);

        $foo = new IronicSerialiserDummy($mockService, $request);

        $this->assertFalse($foo->needNextPageLink(8));
        $this->assertFalse($foo->needNextPageLink(9));
        $this->assertTrue($foo->needNextPageLink(10));
        $this->assertFalse($foo->needNextPageLink(11));
        $this->assertFalse($foo->needNextPageLink(12));
    }

    public function testNeedNextPageLineOnRootHasTopCount()
    {
        $segName = 'Models';
        $segWrapper = m::mock(ResourceSetWrapper::class);
        $segWrapper->shouldReceive('getResourceSetPageSize')->andReturn(10);
        $node = m::mock(RootProjectionNode::class);
        $node->shouldReceive('isExpansionSpecified')->andReturn(true)->times(1);

        $mockService = m::mock(IService::class);
        $mockService->shouldReceive('getHost->getAbsoluteServiceUri->getUrlAsString')
            ->andReturn('http://localhost/odata.svc/Models');
        $request = m::mock(RequestDescription::class)->makePartial();
        $request->shouldReceive('getTargetResourceSetWrapper')->andReturn($segWrapper);
        $request->shouldReceive('getTopOptionCount')->andReturn(9, 10, 11)->times(3);
        $request->shouldReceive('getRootProjectionNode')->andReturn($node)->times(1);

        $foo = new IronicSerialiserDummy($mockService, $request);
        $foo->getStack()->pushSegment($segName, $segWrapper);

        $this->assertFalse($foo->needNextPageLink(10));
        $this->assertFalse($foo->needNextPageLink(10));
        $this->assertTrue($foo->needNextPageLink(10));
    }

    public function testNeedNextPageLineOnRootHasNullTopCount()
    {
        $segName = 'Models';
        $segWrapper = m::mock(ResourceSetWrapper::class);
        $segWrapper->shouldReceive('getResourceSetPageSize')->andReturn(10);
        $node = m::mock(RootProjectionNode::class);
        $node->shouldReceive('isExpansionSpecified')->andReturn(true)->times(1);

        $mockService = m::mock(IService::class);
        $mockService->shouldReceive('getHost->getAbsoluteServiceUri->getUrlAsString')
            ->andReturn('http://localhost/odata.svc/Models');
        $request = m::mock(RequestDescription::class)->makePartial();
        $request->shouldReceive('getTargetResourceSetWrapper')->andReturn($segWrapper);
        $request->shouldReceive('getTopOptionCount')->andReturn(null)->times(3);
        $request->shouldReceive('getRootProjectionNode')->andReturn($node)->times(1);

        $foo = new IronicSerialiserDummy($mockService, $request);
        $foo->getStack()->pushSegment($segName, $segWrapper);

        $this->assertFalse($foo->needNextPageLink(9));
        $this->assertTrue($foo->needNextPageLink(10));
        $this->assertFalse($foo->needNextPageLink(11));
    }

    public function testSetService()
    {
        $oldUrl = 'http://localhost/odata.svc/Models';
        $newUrl = 'http://localhost/megamix.svc/Models';

        $mockService = m::mock(IService::class);
        $mockService->shouldReceive('getHost->getAbsoluteServiceUri->getUrlAsString')
            ->andReturn($oldUrl);

        $newService = m::mock(IService::class);
        $newService->shouldReceive('getHost->getAbsoluteServiceUri->getUrlAsString')
            ->andReturn($newUrl);

        $foo = new IronicSerialiserDummy($mockService, null);
        $this->assertEquals($oldUrl, $foo->getService()->getHost()->getAbsoluteServiceUri()->getUrlAsString());
        $foo->setService($newService);
        $this->assertEquals($newUrl, $foo->getService()->getHost()->getAbsoluteServiceUri()->getUrlAsString());
    }

    public function testGenerateNextLinkUrlNeedsPlainSkippage()
    {
        $object = new \stdClass();

        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getQueryStringItem')
            ->withArgs([ODataConstants::HTTPQUERY_STRING_FILTER])->andReturn(null)->once();
        $host->shouldReceive('getQueryStringItem')
            ->withArgs([ODataConstants::HTTPQUERY_STRING_EXPAND])->andReturn(null)->once();
        $host->shouldReceive('getQueryStringItem')
            ->withArgs([ODataConstants::HTTPQUERY_STRING_ORDERBY])->andReturn('CustomerTitle')->once();
        $host->shouldReceive('getQueryStringItem')
            ->withArgs([ODataConstants::HTTPQUERY_STRING_INLINECOUNT])->andReturn('all')->once();
        $host->shouldReceive('getQueryStringItem')
            ->withArgs([ODataConstants::HTTPQUERY_STRING_SELECT])->andReturn(null)->once();

        $service = m::mock(SimpleDataService::class);
        $service->shouldReceive('getHost')->andReturn($host);

        $request = m::mock(RequestDescription::class);
        $request->shouldReceive('getTopOptionCount')->andReturn(400)->once();
        $request->shouldReceive('getTopCount')->andReturn(200)->once();

        $internalInfo = m::mock(InternalOrderByInfo::class)->makePartial();
        $internalInfo->shouldReceive('buildSkipTokenValue')->andReturn('\'200\'');
        $internalInfo->shouldReceive('getOrderByPathSegments')->andReturn(['a'])->once();

        $node = m::mock(ExpandedProjectionNode::class)->makePartial();
        $node->shouldReceive('getInternalOrderByInfo')->andReturn($internalInfo)->once();

        $foo = m::mock(IronicSerialiserDummy::class)->shouldAllowMockingProtectedMethods()->makePartial();
        $foo->shouldReceive('getCurrentExpandedProjectionNode')->andReturn($node)->once();
        $foo->shouldReceive('getService')->andReturn($service);
        $foo->shouldReceive('getRequest')->andReturn($request);

        $expected = '?$orderby=CustomerTitle&$inlinecount=all&$top=200&$skip=200';
        $actual = $foo->getNextLinkUri($object, ' ');
        $this->assertEquals($expected, $actual);
    }

    public function testGenerateNextLinkUrlNeedsSkipToken()
    {
        $object = new \stdClass();

        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getQueryStringItem')
            ->withArgs([ODataConstants::HTTPQUERY_STRING_FILTER])->andReturn(null)->once();
        $host->shouldReceive('getQueryStringItem')
            ->withArgs([ODataConstants::HTTPQUERY_STRING_EXPAND])->andReturn(null)->once();
        $host->shouldReceive('getQueryStringItem')
            ->withArgs([ODataConstants::HTTPQUERY_STRING_ORDERBY])->andReturn('CustomerTitle')->once();
        $host->shouldReceive('getQueryStringItem')
            ->withArgs([ODataConstants::HTTPQUERY_STRING_INLINECOUNT])->andReturn('all')->once();
        $host->shouldReceive('getQueryStringItem')
            ->withArgs([ODataConstants::HTTPQUERY_STRING_SELECT])->andReturn(null)->once();

        $service = m::mock(SimpleDataService::class);
        $service->shouldReceive('getHost')->andReturn($host);

        $request = m::mock(RequestDescription::class);
        $request->shouldReceive('getTopOptionCount')->andReturn(null)->once();

        $internalInfo = m::mock(InternalOrderByInfo::class)->makePartial();
        $internalInfo->shouldReceive('buildSkipTokenValue')->andReturn('\'University+of+Loamshire\'');
        $internalInfo->shouldReceive('getOrderByPathSegments')->andReturn(['a', 'b'])->once();

        $node = m::mock(ExpandedProjectionNode::class)->makePartial();
        $node->shouldReceive('getInternalOrderByInfo')->andReturn($internalInfo)->once();

        $foo = m::mock(IronicSerialiserDummy::class)->shouldAllowMockingProtectedMethods()->makePartial();
        $foo->shouldReceive('getCurrentExpandedProjectionNode')->andReturn($node)->once();
        $foo->shouldReceive('getService')->andReturn($service);
        $foo->shouldReceive('getRequest')->andReturn($request);

        $expected = '?$orderby=CustomerTitle&$inlinecount=all&$skiptoken=\'University+of+Loamshire\'';
        $actual = $foo->getNextLinkUri($object, ' ');
        $this->assertEquals($expected, $actual);
    }

    public function testSerialisePolymorphicKnownType()
    {
        $known = Carbon::create(2017, 1, 1, 0, 0, 0, 'UTC');
        Carbon::setTestNow($known);

        $meta = [];
        $meta['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $meta['alternate_id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $meta['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $meta['photo'] = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];

        $this->setUpSchemaFacade();

        $classen = [ TestMorphOneSource::class, TestMorphOneSourceAlternate::class, TestMorphTarget::class];

        foreach ($classen as $className) {
            $testModel = new $className($meta);
            App::instance($className, $testModel);
        }

        Cache::shouldReceive('get')->withArgs(['metadata'])->andReturn(null);
        Cache::shouldReceive('get')->withArgs(['objectmap'])->andReturn(null);
        Cache::shouldReceive('forget')->withArgs(['metadataControllers'])->andReturn(null);
        Cache::shouldReceive('forget')->withArgs(['metadata'])->andReturn(null);
        Cache::shouldReceive('forget')->withArgs(['objectmap'])->andReturn(null);

        $holder = new MetadataGubbinsHolder();
        $metaProv = m::mock(MetadataProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $metaProv->shouldReceive('getRelationHolder')->andReturn($holder);
        $metaProv->shouldReceive('getCandidateModels')->andReturn($classen);
        $metaProv->boot();

        $propContent = new ODataPropertyContent();
        $propContent->properties = [
            'name' => new ODataProperty(),
            'alternate_id' => new ODataProperty(),
            'id' => new ODataProperty()
        ];
        $propContent->properties['name']->name = 'name';
        $propContent->properties['alternate_id']->name = 'alternate_id';
        $propContent->properties['id']->name = 'id';
        $propContent->properties['name']->typeName = 'Edm.String';
        $propContent->properties['alternate_id']->typeName = 'Edm.Int32';
        $propContent->properties['id']->typeName = 'Edm.Int32';
        $propContent->properties['name']->value = 'Hammer, M.C.';
        $propContent->properties['id']->value = '42';

        $odataLink = new ODataLink();
        $odataLink->name = 'http://schemas.microsoft.com/ado/2007/08/dataservices/related/morph_TestMorphOneSource';
        $odataLink->title = 'morph_TestMorphOneSource';
        $odataLink->type = 'application/atom+xml;type=entry';
        $odataLink->url = 'TestMorphTargets(id=42)/morph_TestMorphOneSource';

        $odataLink1 = new ODataLink();
        $odataLink1->name = 'http://schemas.microsoft.com/ado/2007/08/dataservices/related/morph_TestMorphOneSourceAlternate';
        $odataLink1->title = 'morph_TestMorphOneSourceAlternate';
        $odataLink1->type = 'application/atom+xml;type=entry';
        $odataLink1->url = 'TestMorphTargets(id=42)/morph_TestMorphOneSourceAlternate';

        $mediaLink1 = new ODataMediaLink(
            'photo',
            'stream',
            'stream',
            '*/*',
            'eTag'
        );
        $mediaLink = new ODataMediaLink(
            'Data.TestMorphTarget',
            '/$value',
            'TestMorphTargets(id=42)/$value',
            '*/*',
            'eTag',
            'edit-media'
        );

        $expected = new ODataEntry();
        $expected->id = 'http://localhost/odata.svc/TestMorphTargets(id=42)';
        $expected->title = new ODataTitle('TestMorphTarget');
        $expected->editLink = new ODataLink();
        $expected->editLink->url = 'TestMorphTargets(id=42)';
        $expected->editLink->name = 'edit';
        $expected->editLink->title = 'TestMorphTarget';
        $expected->type = new ODataCategory('Data.TestMorphTarget');
        $expected->propertyContent = $propContent;
        $expected->links[] = $odataLink;
        $expected->links[] = $odataLink1;
        $expected->mediaLink = $mediaLink;
        $expected->mediaLinks[] = $mediaLink1;
        $expected->isMediaLinkEntry = true;
        $expected->resourceSetName = 'TestMorphTargets';
        $expected->updated = '2017-01-01T00:00:00+00:00';
        $expected->baseURI = 'http://localhost/odata.svc/';

        $model = new TestMorphTarget($meta);
        $model->id = 42;
        $model->name = 'Hammer, M.C.';
        $this->assertTrue($model->isKnownPolymorphSide());
        $this->assertTrue($model->isUnknownPolymorphSide());

        $payload = new QueryResult();
        $payload->results = $model;

        $simple = App::make('metadata');

        $service = new Url('http://localhost/odata.svc');
        $request = new Url('http://localhost/odata.svc/TestMorphTargets(42)');

        $targType = $simple->resolveResourceType('TestMorphTarget');

        $request = m::mock(RequestDescription::class)->makePartial();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/TestMorphTargets(42)');
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc/TestMorphTargets(42)');
        $request->shouldReceive('getTargetResourceType')->andReturn($targType);

        $provWrap = m::mock(ProvidersWrapper::class);
        $provWrap->shouldReceive('resolveResourceType')->andReturn($targType);

        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getAbsoluteServiceUri')->andReturn($service);
        $host->shouldReceive('getProvidersWrapper')->andReturn($provWrap);

        $stream = m::mock(StreamProviderWrapper::class)->makePartial();
        $stream->shouldReceive('getStreamETag2')->andReturn('eTag');
        $stream->shouldReceive('getReadStreamUri2')->andReturn('stream');
        $stream->shouldReceive('getStreamContentType2')->andReturn('*/*');

        $opContext = m::mock(IOperationContext::class);

        $dataService = m::mock(SimpleDataService::class)->makePartial();
        $dataService->setHost($host);
        $dataService->shouldReceive('getProvidersWrapper')->andReturn($provWrap);
        $dataService->shouldReceive('getOperationContext')->andReturn($opContext);
        $dataService->shouldReceive('getStreamProviderWrapper')->andReturn($stream);
        //$dataService = new SimpleDataService($db, App::make('metadata'), $host);

        $ironic = new IronicSerialiser($dataService, $request);

        $actual = $ironic->writeTopLevelElement($payload);
        $this->assertEquals($expected, $actual);
    }

    
    public function testSerialiseKnownSideWithNoResourceMatch()
    {
        $known = Carbon::create(2017, 1, 1, 0, 0, 0, 'UTC');
        Carbon::setTestNow($known);

        $meta = [];
        $meta['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $meta['alternate_id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $meta['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $meta['photo'] = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];

        $model = new TestMorphTarget($meta);

        $concType = m::mock(ResourceEntityType::class)->makePartial();
        $concType->shouldReceive('isAbstract')->andReturn(false)->atLeast(1);
        $concType->shouldReceive('getInstanceType->getName')->andReturn('EatSleepMoshRepeat');

        $simple = m::mock(SimpleMetadataProvider::class)->makePartial();
        $simple->shouldReceive('getDerivedTypes')->andReturn([$concType]);
        App::instance('metadata', $simple);

        $payload = new QueryResult();
        $payload->results = $model;

        $targType = m::mock(ResourceEntityType::class)->makePartial();
        $targType->shouldReceive('isAbstract')->andReturn(true)->atLeast(1);
        $targType->shouldReceive('getName')->andReturn('polyMorphicPlaceholder')->atLeast(1);

        $request = m::mock(RequestDescription::class)->makePartial();
        $request->shouldReceive('getTargetResourceType')->andReturn($targType);

        $foo = m::mock(IronicSerialiser::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getRequest')->andReturn($request);
        $foo->shouldReceive('getService->getProvidersWrapper->resolveResourceType')->andReturn($targType);

        $expected = 'Concrete resource type not selected for payload '.
                     \Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMorphTarget::class;
        $actual = null;

        try {
            $foo->writeTopLevelElement($payload);
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testWriteNullTopLevelElement()
    {
        $query = new QueryResult();

        $foo = m::mock(IronicSerialiser::class)->makePartial();
        $this->assertNull($foo->writeTopLevelElement($query));
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

        $op = new IlluminateOperationContext($request);
        $host = new ServiceHost($op, $request);
        $host->setServiceUri('/odata.svc/');

        Cache::shouldReceive('get')->withArgs(['metadata'])->andReturn(null);
        Cache::shouldReceive('get')->withArgs(['objectmap'])->andReturn(null);
        Cache::shouldReceive('forget')->withArgs(['metadataControllers'])->andReturn(null);
        Cache::shouldReceive('forget')->withArgs(['metadata'])->andReturn(null);
        Cache::shouldReceive('forget')->withArgs(['objectmap'])->andReturn(null);

        $holder = new MetadataGubbinsHolder();
        $classen = [TestMonomorphicManySource::class, TestMonomorphicManyTarget::class];
        $metaProv = m::mock(MetadataProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $metaProv->shouldReceive('getRelationHolder')->andReturn($holder);
        $metaProv->shouldReceive('getCandidateModels')->andReturn($classen);
        $metaProv->reset();
        $metaProv->boot();

        $meta = App::make('metadata');

        $query = m::mock(LaravelQuery::class);

        $model = m::mock(TestMonomorphicManySource::class)->makePartial();
        $model->shouldReceive('metadata')->andReturn($metadata);
        $model->id = 1;
        $model->name = 'Name';
        $model->shouldReceive('getAttribute')->withArgs(['manySource'])->andReturn(collect([]));

        $result = new QueryResult();
        $result->results = $model;

        // default data service
        $service = new TestDataService($query, $meta, $host);
        $processor = $service->handleRequest();

        $ironic = m::mock(IronicSerialiser::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $ironic->shouldReceive('shouldExpandSegment')->withArgs(['manySource'])->andReturn(true)->atLeast(1);
        $ironic->shouldReceive('getService')->andReturn($service);
        $ironic->shouldReceive('getRequest')->andReturn($processor->getRequest());
        $ironic->shouldReceive('getModelSerialiser')->andReturn($serialiser);
        $ironic->shouldReceive('writeTopLevelElements')->andReturn(null)->never();
        $ironic->shouldReceive('getUpdated')->andReturn($known);

        $ironicResult = $ironic->writeTopLevelElement($result);
        $this->assertTrue($ironicResult->links[0]->expandedResult instanceof ODataFeed);
        $this->assertEquals(0, count($ironicResult->links[0]->expandedResult->entries));
    }

    public function testCrankshaftFeedExpansion()
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

        $op = new IlluminateOperationContext($request);
        $host = new ServiceHost($op, $request);
        $host->setServiceUri('/odata.svc/');

        Cache::shouldReceive('get')->withArgs(['metadata'])->andReturn(null);
        Cache::shouldReceive('get')->withArgs(['objectmap'])->andReturn(null);
        Cache::shouldReceive('forget')->withArgs(['metadataControllers'])->andReturn(null);
        Cache::shouldReceive('forget')->withArgs(['metadata'])->andReturn(null);
        Cache::shouldReceive('forget')->withArgs(['objectmap'])->andReturn(null);

        $holder = new MetadataGubbinsHolder();
        $classen = [TestMonomorphicSource::class, TestMonomorphicTarget::class];
        $metaProv = m::mock(MetadataProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $metaProv->shouldReceive('getRelationHolder')->andReturn($holder);
        $metaProv->shouldReceive('getCandidateModels')->andReturn($classen);
        $metaProv->reset();
        $metaProv->boot();

        $targ = new TestMonomorphicTarget($metadata);
        $targ->id = 1;
        $targ->name = 'Name';

        $hasMany = m::mock(HasMany::class)->makePartial();
        $hasMany->shouldReceive('getResults')->andReturn([$targ]);

        $emptyMany = m::mock(HasMany::class)->makePartial();
        $emptyMany->shouldReceive('getResults')->andReturn(collect([]));

        $src1 = m::mock(TestMonomorphicSource::class)->makePartial();
        $src1->shouldReceive('metadata')->andReturn($metadata);
        $src1->shouldReceive('manySource')->andReturn($hasMany);
        $src1->id = 1;

        $src2 = m::mock(TestMonomorphicSource::class)->makePartial();
        $src2->shouldReceive('metadata')->andReturn($metadata);
        $src2->shouldReceive('manySource')->andReturn($emptyMany);
        $src2->id = 2;

        $src3 = m::mock(TestMonomorphicSource::class)->makePartial();
        $src3->shouldReceive('metadata')->andReturn($metadata);
        $src3->shouldReceive('manySource')->andReturn($hasMany);
        $src3->id = 3;

        $results = [new QueryResult(), new QueryResult(), new QueryResult()];
        $results[0]->results = $src1;
        $results[1]->results = $src2;
        $results[2]->results = $src3;

        $collection = new QueryResult();
        $collection->results = $results;

        $meta = App::make('metadata');

        $query = m::mock(LaravelQuery::class);

        $service = new TestDataService($query, $meta, $host);
        $processor = $service->handleRequest();

        $ironic = new IronicSerialiserDummy($service, $processor->getRequest());
        $ironic->setPropertyExpansion('manySource', true);
        $ironic->setPropertyExpansion('oneSource', false);
        $ironic->setPropertyExpansion('manyTarget', false);
        $ironic->setPropertyExpansion('oneTarget', false);

        $ironicResult = $ironic->writeTopLevelElements($collection);
        $this->assertEquals(3, count($ironicResult->entries));
        $this->assertNull($ironicResult->entries[0]->links[0]->expandedResult);
        $this->assertNull($ironicResult->entries[1]->links[0]->expandedResult);
        $this->assertNull($ironicResult->entries[2]->links[0]->expandedResult);
        $this->assertTrue($ironicResult->entries[0]->links[1]->expandedResult instanceof ODataFeed);
        $this->assertTrue($ironicResult->entries[0]->links[1]->expandedResult instanceof ODataFeed);
        $this->assertEquals(1, count($ironicResult->entries[0]->links[1]->expandedResult->entries));
        $this->assertTrue($ironicResult->entries[2]->links[1]->expandedResult instanceof ODataFeed);
        $this->assertEquals(1, count($ironicResult->entries[2]->links[1]->expandedResult->entries));
    }

    public function testGetConcreteTypeFromAbstractTypeWhereAbstractHasNoDerivedTypes()
    {
        $abstractType = m::mock(ResourceEntityType::class)->makePartial();
        $abstractType->shouldReceive('isAbstract')->andReturn(true)->atLeast(1);

        $payloadClass = 'payloadClass';

        $metadata = m::mock(SimpleMetadataProvider::class)->MakePartial();
        $metadata->shouldReceive('getDerivedTypes')->withArgs([$abstractType])->andReturn([])->once();
        App::instance('metadata', $metadata);

        $ironic = m::mock(IronicSerialiserDummy::class)->makePartial();

        $expected = 'Supplied abstract type must have at least one derived type';
        $actual = null;

        try {
            $ironic->getConcreteTypeFromAbstractType($abstractType, $payloadClass);
        } catch (InvalidOperationException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetConcreteTypeFromAbstractTypeWhereAbstractHasOnlyAbstractDerivedTypes()
    {
        $abstractType = m::mock(ResourceEntityType::class)->makePartial();
        $abstractType->shouldReceive('isAbstract')->andReturn(true)->atLeast(1);

        $payloadClass = 'payloadClass';

        $metadata = m::mock(SimpleMetadataProvider::class)->makePartial();
        $metadata->shouldReceive('getDerivedTypes')->withArgs([$abstractType])->andReturn([$abstractType])->once();
        App::instance('metadata', $metadata);

        $ironic = m::mock(IronicSerialiserDummy::class)->makePartial();

        $expected = 'Concrete resource type not selected for payload payloadClass';
        $actual = null;

        try {
            $ironic->getConcreteTypeFromAbstractType($abstractType, $payloadClass);
        } catch (InvalidOperationException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetConcreteTypeFromAbstractTypeWhereAbstractHasOnlyConcreteDerivedTypesNoPayloadClass()
    {
        $abstractType = m::mock(ResourceEntityType::class)->makePartial();
        $abstractType->shouldReceive('isAbstract')->andReturn(true)->atLeast(1);

        $concreteType = m::mock(ResourceEntityType::class)->makePartial();
        $concreteType->shouldReceive('isAbstract')->andReturn(false)->atLeast(1);
        $concreteType->shouldReceive('getInstanceType->getName')->andReturn('concreteClass');

        $payloadClass = 'payloadClass';

        $metadata = m::mock(SimpleMetadataProvider::class)->makePartial();
        $metadata->shouldReceive('getDerivedTypes')->withArgs([$abstractType])->andReturn([$concreteType])->once();
        App::instance('metadata', $metadata);

        $ironic = m::mock(IronicSerialiserDummy::class)->makePartial();

        $expected = 'Concrete resource type not selected for payload payloadClass';
        $actual = null;

        try {
            $ironic->getConcreteTypeFromAbstractType($abstractType, $payloadClass);
        } catch (InvalidOperationException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetConcreteTypeFromAbstractTypeWhereAbstractHasOnlyConcreteDerivedTypesWithPayloadClass()
    {
        $abstractType = m::mock(ResourceEntityType::class)->makePartial();
        $abstractType->shouldReceive('isAbstract')->andReturn(true)->atLeast(1);

        $concreteType = m::mock(ResourceEntityType::class)->makePartial();
        $concreteType->shouldReceive('isAbstract')->andReturn(false)->atLeast(1);
        $concreteType->shouldReceive('getInstanceType->getName')->andReturn('payloadClass');

        $payloadClass = 'payloadClass';

        $metadata = m::mock(SimpleMetadataProvider::class)->makePartial();
        $metadata->shouldReceive('getDerivedTypes')->withArgs([$abstractType])->andReturn([$concreteType])->once();
        App::instance('metadata', $metadata);

        $ironic = m::mock(IronicSerialiserDummy::class)->makePartial();

        $result = $ironic->getConcreteTypeFromAbstractType($abstractType, $payloadClass);
        $this->assertEquals($result, $concreteType);
    }

    public function testGetRequestWhenNotYetSet()
    {
        $ironic = m::mock(IronicSerialiser::class)->makePartial();

        $expected = 'Request not yet set';
        $actual = null;

        try {
            $ironic->getRequest();
        } catch (InvalidOperationException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetNextLinkUriWithBadOrderByInfo()
    {
        $lastObject = new \stdClass();
        $node = m::mock(ExpandedProjectionNode::class)->makePartial();
        $node->shouldReceive('getInternalOrderByInfo')->andReturn(null);

        $ironic = m::mock(IronicSerialiserDummy::class)->makePartial();
        $ironic->shouldReceive('getCurrentExpandedProjectionNode')->andReturn($node)->once();

        $expected = "Null";
        $actual = null;

        try {
            $ironic->getNextLinkUri($lastObject);
        } catch (InvalidOperationException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }
}
