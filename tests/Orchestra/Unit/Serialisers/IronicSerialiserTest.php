<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 29/02/20
 * Time: 12:19 PM.
 */
namespace AlgoWeb\PODataLaravel\Orchestra\Tests\Unit\Serialisers;

use AlgoWeb\PODataLaravel\Models\MetadataGubbinsHolder;
use AlgoWeb\PODataLaravel\Orchestra\Tests\Models\OrchestraBelongsToTestModel;
use AlgoWeb\PODataLaravel\Orchestra\Tests\Models\OrchestraHasManyTestModel;
use AlgoWeb\PODataLaravel\Orchestra\Tests\Serialisers\DummyIronicSerialiser;
use AlgoWeb\PODataLaravel\Orchestra\Tests\TestCase;
use AlgoWeb\PODataLaravel\Providers\MetadataProvider;
use AlgoWeb\PODataLaravel\Serialisers\IronicSerialiser;
use Illuminate\Support\Facades\App;
use Mockery as m;
use POData\Common\InvalidOperationException;
use POData\Common\Url;
use POData\IService;
use POData\ObjectModel\ODataLink;
use POData\OperationContext\IOperationContext;
use POData\OperationContext\ServiceHost;
use POData\Providers\Metadata\ResourceEntityType;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourcePropertyKind;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\ResourceTypeKind;
use POData\Providers\Metadata\SimpleMetadataProvider;
use POData\Providers\ProvidersWrapper;
use POData\Providers\Query\QueryResult;
use POData\Providers\Stream\StreamProviderWrapper;
use POData\SimpleDataService;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\ExpandedProjectionNode;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\RootProjectionNode;
use POData\UriProcessor\RequestDescription;

class IronicSerialiserTest extends TestCase
{
    /**
     * @throws \POData\Common\InvalidOperationException
     * @throws \POData\Common\ODataException
     * @throws \ReflectionException
     */
    public function testWriteTopLevelElementWithNullPayload()
    {
        $service = m::mock(SimpleDataService::class);
        $service->shouldReceive('getHost->getAbsoluteServiceUri->getUrlAsString')->andReturn('http://localhost');

        $request = m::mock(RequestDescription::class);
        $request->shouldReceive('getTargetResourceType->getName')->andReturn('property');

        $foo = new DummyIronicSerialiser($service, $request);

        $foo->loadStackIfEmpty();

        $this->assertEquals(1, count($foo->getLightStack()));

        $result = new QueryResult();
        $this->assertNull($result->results);

        $expected = null;
        $actual   = $foo->writeTopLevelElement($result);
        $this->assertEquals($expected, $actual);

        $this->assertEquals(0, count($foo->getLightStack()));
    }

    /**
     * @throws InvalidOperationException
     * @throws \POData\Common\ODataException
     * @throws \ReflectionException
     */
    public function testWriteTopLevelElementsSeedsLightStackDespiteKaBoom()
    {
        $service = m::mock(SimpleDataService::class);
        $service->shouldReceive('getHost->getAbsoluteServiceUri->getUrlAsString')->andReturn('http://localhost');

        $request = m::mock(RequestDescription::class);
        $request->shouldReceive('getTargetResourceType->getName')->andReturn('property')->once();
        $request->shouldReceive('getContainerName')->andThrow(InvalidOperationException::class);

        $foo = new DummyIronicSerialiser($service, $request);

        $this->expectException(InvalidOperationException::class);

        $result          = new QueryResult();
        $result->results = [];

        $foo->writeTopLevelElements($result);
    }

    public function testBuildLinksFromRelsBadResourceProperty()
    {
        $service = m::mock(SimpleDataService::class);
        $service->shouldReceive('getHost->getAbsoluteServiceUri->getUrlAsString')->andReturn('http://localhost');

        $request = m::mock(RequestDescription::class);
        $request->shouldReceive('getTargetResourceType->getName')->andReturn('property');

        $foo = new DummyIronicSerialiser($service, $request);

        $result          = new QueryResult();
        $result->results = [];

        $rType = m::mock(ResourceType::class);
        $rType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::PRIMITIVE());
        $badProp = new ResourceProperty('resource', 'mine', ResourcePropertyKind::PRIMITIVE(), $rType);

        $this->expectException(InvalidOperationException::class);
        $this->expectExceptionMessage('$propKind != ResourcePropertyKind::RESOURCESET_REFERENCE && $propKind != ResourcePropertyKind::RESOURCE_REFERENCE');

        $foo->buildLinksFromRels($result, [$badProp], 'foo');
    }

    public function testBuildLinksFromRelsWithExpansion()
    {
        $parent = new OrchestraHasManyTestModel();
        $this->assertTrue($parent->save());

        $kid = new OrchestraBelongsToTestModel();
        $kid->parent()->associate($parent);
        $this->assertTrue($kid->save());

        /** @var SimpleMetadataProvider $meta */
        $meta = App::make('metadata');

        $rSet = $meta->resolveResourceSet('OrchestraBelongsToTestModels');
        $this->assertTrue($rSet instanceof ResourceSet);

        /** @var ResourceEntityType $rType */
        $rType = $rSet->getResourceType();
        $this->assertTrue($rType instanceof ResourceEntityType);

        $rProp = $rType->getAllProperties()['parent'];
        $this->assertTrue($rProp instanceof ResourceProperty);

        $context = m::mock(IOperationContext::class);

        $stream = m::mock(StreamProviderWrapper::class);

        $service = m::mock(IService::class);
        $service->shouldReceive('getHost->getAbsoluteServiceUri->getUrlAsString')->andReturn('http://localhost');
        $service->shouldReceive('getProvidersWrapper')->andReturn($meta);
        $service->shouldReceive('getOperationContext')->andReturn($context);
        $service->shouldReceive('getStreamProviderWrapper')->andReturn($stream);

        $expNode = m::mock(ExpandedProjectionNode::class);

        $rootNode = m::mock(RootProjectionNode::class);
        $rootNode->shouldReceive('findNode')->andReturn($expNode, null);

        $request = m::mock(RequestDescription::class);
        $request->shouldReceive('getTargetResourceType->getName')->andReturn('property');
        $request->shouldReceive('getRootProjectionNode')->andReturn($rootNode);

        $foo = new DummyIronicSerialiser($service, $request);

        $kid->parent = [$parent];

        $result          = new QueryResult();
        $result->results = $kid;

        $actual = $foo->buildLinksFromRels($result, [$rProp], 'foo');

        $this->assertEquals(1, count($actual));
        /** @var ODataLink $link */
        $link = $actual[0];
        $this->assertTrue($link->isExpanded());
        $this->assertFalse($link->isCollection());
    }

    /**
     * @throws InvalidOperationException
     * @throws \Exception
     */
    public function testWriteMediaDataBadStreamWrapper()
    {
        $request = m::mock(RequestDescription::class)->makePartial();
        $url = m::mock(Url::class)->makePartial();
        $url->shouldReceive('getUrlAsString')->andReturn('http://localhost');
        $host = m::mock(ServiceHost::class)->makePartial();
        $host->shouldReceive('getAbsoluteServiceUri')->andReturn($url);
        $service = m::mock(IService::class)->makePartial();
        $service->shouldReceive('getOperationContext')->andReturnNull();
        $service->shouldReceive('getStreamProviderWrapper')->andReturnNull();
        $service->shouldReceive('getHost')->andReturn($host);

        $rType = m::mock(ResourceType::class)->makePartial();

        $foo = new DummyIronicSerialiser($service, $request);

        $this->expectException(InvalidOperationException::class);
        $this->expectExceptionMessage('Retrieved stream provider must not be null');

        $foo->writeMediaData(null, '', '', $rType);
    }

    public function testWriteUrlElementsEmptyWithHasMoreSet()
    {
        $foo = m::mock(IronicSerialiser::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('buildNextPageLink')->andReturnNull()->never();

        $result = new QueryResult();
        $result->results =
        $result->hasMore = true;
    }

}
