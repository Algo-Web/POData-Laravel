<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 29/02/20
 * Time: 12:19 PM
 */

namespace AlgoWeb\PODataLaravel\Orchestra\Tests\Unit\Serialisers;

use AlgoWeb\PODataLaravel\Orchestra\Tests\Serialisers\DummyIronicSerialiser;
use AlgoWeb\PODataLaravel\Orchestra\Tests\TestCase;
use Mockery as m;
use POData\Common\InvalidOperationException;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourcePropertyKind;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\ResourceTypeKind;
use POData\Providers\Query\QueryResult;
use POData\SimpleDataService;
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
        $actual = $foo->writeTopLevelElement($result);
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

        $result = new QueryResult();
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

        $result = new QueryResult();
        $result->results = [];

        $rType = m::mock(ResourceType::class);
        $rType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::PRIMITIVE());
        $badProp = new ResourceProperty('resource', 'mine', ResourcePropertyKind::PRIMITIVE, $rType);

        $this->expectException(InvalidOperationException::class);
        $this->expectExceptionMessage('$propKind != ResourcePropertyKind::RESOURCESET_REFERENCE && $propKind != ResourcePropertyKind::RESOURCE_REFERENCE');

        $foo->buildLinksFromRels($result, [$badProp], 'foo');
    }

}
