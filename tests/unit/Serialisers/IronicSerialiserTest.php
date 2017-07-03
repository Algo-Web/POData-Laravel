<?php

namespace AlgoWeb\PODataLaravel\Serialisers;

use Mockery as m;
use POData\Common\ODataConstants;
use POData\IService;
use POData\OperationContext\ServiceHost;
use POData\Providers\Metadata\ResourceSetWrapper;
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
}
