<?php

namespace AlgoWeb\PODataLaravel\Query;

use AlgoWeb\PODataLaravel\Models\TestCase as TestCase;
use AlgoWeb\PODataLaravel\Models\TestModel;
use Mockery as m;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Metadata\Type\Int32;
use POData\Providers\Query\QueryType;
use POData\UriProcessor\QueryProcessor\OrderByParser\OrderByPathSegment;
use POData\UriProcessor\QueryProcessor\OrderByParser\OrderBySubPathSegment;
use POData\UriProcessor\QueryProcessor\SkipTokenParser\SkipTokenInfo;

class LaravelReadQueryTest extends TestCase
{
    public function testBadSkipToken()
    {
        $expected = 'Skip token must be either null or instance of SkipTokenInfo.';
        $actual = null;

        $query = m::mock(QueryType::class);
        $resource = m::mock(ResourceSet::class);
        $skipToken = new \DateTime();

        $foo = new LaravelReadQuery();

        try {
            $foo->getResourceSet($query, $resource, null, null, null, null, $skipToken);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testSkipTokenWithSegmentValueCountMismatch()
    {
        $expected = 'assert(): assert(count($values) == count($segments)) failed';
        $actual = null;

        $source = m::mock(TestModel::class)->makePartial();

        $query = m::mock(QueryType::class);
        $resource = m::mock(ResourceSet::class);

        $skipToken = m::mock(SkipTokenInfo::class)->makePartial();
        $skipToken->shouldReceive('getOrderByKeysInToken')->andReturn([]);
        $skipToken->shouldReceive('getOrderByInfo->getOrderByPathSegments')
            ->andReturn([m::mock(OrderByPathSegment::class)]);

        $foo = new LaravelReadQuery();

        try {
            $foo->getResourceSet($query, $resource, null, null, null, null, $skipToken, $source);
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testSkipTokenWithRootValueExcludedCountMismatch()
    {
        $source = m::mock(TestModel::class)->makePartial();
        $source->shouldReceive('where')->withArgs(['id', '!=', '2'])->andReturnSelf()->once();
        $source->shouldReceive('skip')->withArgs([0])->andReturnSelf()->once();
        $source->shouldReceive('with')->withAnyArgs()->andReturnSelf()->once();
        $source->shouldReceive('take')->withAnyArgs()->andReturnSelf()->once();
        $source->shouldReceive('get')->andReturn(collect([]));
        $source->shouldReceive('count')->andReturn(0)->once();

        $query = QueryType::ENTITIES_WITH_COUNT();
        $resource = m::mock(ResourceSet::class);

        $subSegment = m::mock(OrderBySubPathSegment::class);
        $subSegment->shouldReceive('getName')->andReturn('id')->once();

        $pathSegment = m::mock(OrderByPathSegment::class);
        $pathSegment->shouldReceive('isAscending')->andReturn(true);
        $pathSegment->shouldReceive('getSubPathSegments')->andReturn([$subSegment])->once();

        $orderKey = ['\'2\'', new Int32()];

        $skipToken = m::mock(SkipTokenInfo::class)->makePartial();
        $skipToken->shouldReceive('getOrderByKeysInToken')->andReturn([$orderKey]);
        $skipToken->shouldReceive('getOrderByInfo->getOrderByPathSegments')
            ->andReturn([$pathSegment]);

        $foo = new LaravelReadQuery();

        $result = $foo->getResourceSet($query, $resource, null, null, null, null, $skipToken, $source);
        $this->assertTrue(is_array($result->results));
        $this->assertEquals(0, count($result->results));
        $this->assertEquals(0, $result->count);
    }
}
