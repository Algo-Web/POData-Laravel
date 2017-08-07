<?php

namespace AlgoWeb\PODataLaravel\Serialisers;

use AlgoWeb\PODataLaravel\Models\TestMorphOneSource;
use AlgoWeb\PODataLaravel\Models\TestMorphOneSourceAlternate;
use AlgoWeb\PODataLaravel\Models\TestMorphTarget;
use AlgoWeb\PODataLaravel\Providers\MetadataProvider;
use Illuminate\Support\Facades\App;
use Mockery as m;
use POData\Common\ODataConstants;
use POData\Common\Url;
use POData\IService;
use POData\ObjectModel\ODataEntry;
use POData\ObjectModel\ODataLink;
use POData\ObjectModel\ODataMediaLink;
use POData\ObjectModel\ODataProperty;
use POData\ObjectModel\ODataPropertyContent;
use POData\OperationContext\IOperationContext;
use POData\OperationContext\ServiceHost;
use POData\Providers\Metadata\ResourceSetWrapper;
use POData\Providers\Metadata\SimpleMetadataProvider;
use POData\Providers\ProvidersWrapper;
use POData\Providers\Query\IQueryProvider;
use POData\Providers\Query\QueryResult;
use POData\Providers\Stream\StreamProviderWrapper;
use POData\SimpleDataService;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\ExpandedProjectionNode;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\RootProjectionNode;
use POData\UriProcessor\QueryProcessor\OrderByParser\InternalOrderByInfo;
use POData\UriProcessor\RequestDescription;

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
        $node->shouldReceive('findNode')->withArgs(['Models'])->andReturn($kidNode)->once();
        $node->shouldReceive('isExpansionSpecified')->andReturn(true)->times(2);
        $request = m::mock(RequestDescription::class)->makePartial();
        $request->shouldReceive('getRootProjectionNode')->andReturn($node)->times(3);

        $segName = 'Models';
        $segWrapper = m::mock(ResourceSetWrapper::class);

        $foo = new IronicSerialiserDummy($mockService, $request);
        $foo->getStack()->pushSegment($segName, $segWrapper);
        $foo->getStack()->pushSegment($segName, $segWrapper);

        $result = $foo->getCurrentExpandedProjectionNode();
        $this->assertTrue($result instanceof ExpandedProjectionNode);
    }

    public function testGetCurrentExpandedProjectionNodeNeedsExpansionThreeLevelsEndUpNull()
    {
        $mockService = m::mock(IService::class);
        $mockService->shouldReceive('getHost->getAbsoluteServiceUri->getUrlAsString')
            ->andReturn('http://localhost/odata.svc/Models');
        $kidNode = m::mock(ExpandedProjectionNode::class);
        $kidNode->shouldReceive('findNode')->andReturn(null)->once();
        $node = m::mock(RootProjectionNode::class);
        $node->shouldReceive('findNode')->withArgs(['Models'])->andReturn($kidNode)->once();
        $node->shouldReceive('isExpansionSpecified')->andReturn(true)->times(3);
        $request = m::mock(RequestDescription::class)->makePartial();
        $request->shouldReceive('getRootProjectionNode')->andReturn($node)->times(4);

        $segName = 'Models';
        $segWrapper = m::mock(ResourceSetWrapper::class);

        $foo = new IronicSerialiserDummy($mockService, $request);
        $foo->getStack()->pushSegment($segName, $segWrapper);
        $foo->getStack()->pushSegment($segName, $segWrapper);
        $foo->getStack()->pushSegment($segName, $segWrapper);

        $expected = 'assert(): is_null($expandedProjectionNode) failed';
        $actual = null;

        try {
            $result = $foo->getCurrentExpandedProjectionNode();
        } catch (\ErrorException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetCurrentExpandedProjectionNodeNeedsExpansionThreeLevelsEndUpNotANode()
    {
        $mockService = m::mock(IService::class);
        $mockService->shouldReceive('getHost->getAbsoluteServiceUri->getUrlAsString')
            ->andReturn('http://localhost/odata.svc/Models');
        $kidNode = m::mock(ExpandedProjectionNode::class);
        $kidNode->shouldReceive('findNode')->andReturn('eins')->once();
        $node = m::mock(RootProjectionNode::class);
        $node->shouldReceive('findNode')->withArgs(['Models'])->andReturn($kidNode)->once();
        $node->shouldReceive('isExpansionSpecified')->andReturn(true)->times(3);
        $request = m::mock(RequestDescription::class)->makePartial();
        $request->shouldReceive('getRootProjectionNode')->andReturn($node)->times(4);

        $segName = 'Models';
        $segWrapper = m::mock(ResourceSetWrapper::class);

        $foo = new IronicSerialiserDummy($mockService, $request);
        $foo->getStack()->pushSegment($segName, $segWrapper);
        $foo->getStack()->pushSegment($segName, $segWrapper);
        $foo->getStack()->pushSegment($segName, $segWrapper);

        $expected = 'assert(): $expandedProjectionNode not instanceof ExpandedProjectionNode failed';
        $actual = null;

        try {
            $result = $foo->getCurrentExpandedProjectionNode();
        } catch (\ErrorException $e) {
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
        $internalInfo->shouldReceive('buildSkipTokenValue')->andReturn('200');
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

    public function testSerialisePolymorphicUnknownType()
    {
        $meta = [];
        $meta['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $meta['alternate_id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $meta['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $meta['photo'] = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];

        $this->setUpSchemaFacade();

        $simple = new SimpleMetadataProvider('Data', 'Data');
        App::instance('metadata', $simple);

        $classen = [ TestMorphOneSource::class, TestMorphOneSourceAlternate::class, TestMorphTarget::class];

        foreach ($classen as $className) {
            $testModel = new $className($meta);
            App::instance($className, $testModel);
        }

        $metaProv = m::mock(MetadataProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $metaProv->shouldReceive('getCandidateModels')->andReturn($classen);
        $metaProv->boot();

        $propContent = new ODataPropertyContent();
        $propContent->properties = [new ODataProperty(), new ODataProperty(), new ODataProperty()];
        $propContent->properties[0]->name = 'alternate_id';
        $propContent->properties[1]->name = 'name';
        $propContent->properties[2]->name = 'id';
        $propContent->properties[0]->typeName = 'Edm.Int32';
        $propContent->properties[1]->typeName = 'Edm.String';
        $propContent->properties[2]->typeName = 'Edm.Int32';
        $propContent->properties[0]->value= '42';
        $propContent->properties[1]->value = 'Hammer, M.C.';

        $odataLink = new ODataLink();
        $odataLink->name = 'http://schemas.microsoft.com/ado/2007/08/dataservices/related/morphTarget';
        $odataLink->title = 'morphTarget';
        $odataLink->type = 'application/atom+xml;type=entry';
        $odataLink->url = 'TestMorphOneSourceAlternates(PrimaryKey=\'42\')/morphTarget';

        $mediaLink1 = new ODataMediaLink(
            'photo',
            'stream',
            'stream',
            '*/*',
            'eTag'
        );
        $mediaLink = new ODataMediaLink(
            'TestMorphOneSourceAlternate',
            '/$value',
            'TestMorphOneSourceAlternates(PrimaryKey=\'42\')/$value',
            '*/*',
            'eTag'
        );

        $expected = new ODataEntry();
        $expected->id = 'http://localhost/odata.svc/TestMorphOneSourceAlternates(PrimaryKey=\'42\')';
        $expected->title = 'TestMorphOneSourceAlternate';
        $expected->editLink = 'TestMorphOneSourceAlternates(PrimaryKey=\'42\')';
        $expected->type = 'TestMorphOneSourceAlternate';
        $expected->propertyContent = $propContent;
        $expected->links[] = $odataLink;
        $expected->mediaLink = $mediaLink;
        $expected->mediaLinks[] = $mediaLink1;
        $expected->isMediaLinkEntry = true;
        $expected->resourceSetName = 'TestMorphOneSourceAlternates';

        $model = new TestMorphOneSourceAlternate($meta);
        $model->alternate_id = 42;
        $model->name = 'Hammer, M.C.';
        $model->PrimaryKey = 42;

        $payload = new QueryResult();
        $payload->results = $model;

        $service = new Url('http://localhost/odata.svc');
        $request = new Url('http://localhost/odata.svc/TestMorphOneSourceAlternates(42)');

        $targType = $simple->resolveResourceType('polyMorphicPlaceholder');

        $request = m::mock(RequestDescription::class)->makePartial();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/TestMorphOneSourceAlternates(42)');
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc/TestMorphOneSourceAlternates(42)');
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
}
