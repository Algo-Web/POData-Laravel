<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 29/02/20
 * Time: 1:31 AM.
 */
namespace AlgoWeb\PODataLaravel\Orchestra\Tests\Unit\Serialisers;

use AlgoWeb\PODataLaravel\Orchestra\Tests\Serialisers\DummyIronicSerialiser;
use AlgoWeb\PODataLaravel\Orchestra\Tests\TestCase;
use Mockery as m;
use POData\Common\InvalidOperationException;
use POData\SimpleDataService;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\ExpandedProjectionNode;
use POData\UriProcessor\QueryProcessor\OrderByParser\InternalOrderByInfo;
use POData\UriProcessor\QueryProcessor\OrderByParser\OrderByInfo;

class SerialiseNextPageLinksTraitTest extends TestCase
{
    /**
     * @throws \Exception
     */
    public function testGetNextLinkUriWithBadOrderByInfo()
    {
        $node = m::mock(ExpandedProjectionNode::class);
        $node->shouldReceive('getInternalOrderByInfo')->andReturn(null);

        $foo = m::mock(DummyIronicSerialiser::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getCurrentExpandedProjectionNode')->andReturn($node);

        $object = new \stdClass();

        $this->expectException(InvalidOperationException::class);
        $this->expectExceptionMessage('Null');

        $foo->getNextLinkUri($object);
    }

    /**
     * @throws \Exception
     */
    public function testGetNextLinkUriWithBadSkipToken()
    {
        $order = m::mock(InternalOrderByInfo::class)->makePartial();
        $order->shouldReceive('getOrderByPathSegments')->andReturn([]);

        $node = m::mock(ExpandedProjectionNode::class);
        $node->shouldReceive('getInternalOrderByInfo')->andReturn($order);

        $foo = m::mock(DummyIronicSerialiser::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getCurrentExpandedProjectionNode')->andReturn($node);
        $foo->shouldReceive('getNextPageLinkQueryParametersForRootResourceSet')->andReturn('');

        $object = new \stdClass();

        $this->expectException(InvalidOperationException::class);
        $this->expectExceptionMessage('!is_null($skipToken)');

        $foo->getNextLinkUri($object);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetEmptyNextPageLinkParms()
    {
        $foo = m::mock(DummyIronicSerialiser::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getService->getHost->getQueryStringItem')->andReturnNull()->times(5);
        $foo->shouldReceive('getRequest->getTopOptionCount')->andReturn(0)->once();
        $foo->shouldReceive('getRequest->getTopCount')->andReturn(0)->once();

        $expected = null;

        $actual = $foo->getNextPageLinkQueryParametersForRootResourceSet();

        $this->assertEquals($expected, $actual);
    }
}
