<?php

namespace AlgoWeb\PODataLaravel\Query;

use AlgoWeb\PODataLaravel\Models\TestCase as TestCase;
use AlgoWeb\PODataLaravel\Models\TestModel;
use Illuminate\Database\Query\Builder;
use Mockery as m;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Metadata\Type\Int32;
use POData\Providers\Metadata\Type\StringType;
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
        $expected = 'assert():';
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
        $this->assertStringStartsWith($expected, $actual);
    }

    public function testSkipTokenWithRootValueExcludedCountMismatch()
    {
        $source = m::mock(TestModel::class)->makePartial();
        $source->shouldReceive('orWhere')->withAnyArgs()->andReturnSelf()->once();
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

    public function testSkipTokenWithTwoEffectiveColumns()
    {
        $source = $this->generateTestModelWithMetadata();
        $source->getConnection()->shouldReceive('select')->andReturn([]);

        $query = QueryType::ENTITIES_WITH_COUNT();
        $resource = m::mock(ResourceSet::class);

        $subSegment = m::mock(OrderBySubPathSegment::class);
        $subSegment->shouldReceive('getName')->andReturn('id')->once();

        $pathSegment = m::mock(OrderByPathSegment::class);
        $pathSegment->shouldReceive('isAscending')->andReturn(true);
        $pathSegment->shouldReceive('getSubPathSegments')->andReturn([$subSegment])->once();

        $nameSub = m::mock(OrderBySubPathSegment::class);
        $nameSub->shouldReceive('getName')->andReturn('name')->once();

        $nameSegment = m::mock(OrderByPathSegment::class);
        $nameSegment->shouldReceive('isAscending')->andReturn(false);
        $nameSegment->shouldReceive('getSubPathSegments')->andReturn([$nameSub])->once();

        $orderKey = [['\'2\'', new Int32()], [ '\'name\'', new StringType()]];

        $skipToken = m::mock(SkipTokenInfo::class)->makePartial();
        $skipToken->shouldReceive('getOrderByKeysInToken')->andReturn($orderKey);
        $skipToken->shouldReceive('getOrderByInfo->getOrderByPathSegments')
            ->andReturn([$pathSegment, $nameSegment]);

        $foo = new LaravelReadQuery();

        $result = $foo->getResourceSet($query, $resource, null, null, null, null, $skipToken, $source);
        $this->assertTrue(is_array($result->results));
        $this->assertEquals(0, count($result->results));
        $this->assertEquals(0, $result->count);
        $this->assertFalse($result->hasMore);
    }

    /**
     * @return TestModel
     */
    private function generateTestModelWithMetadata()
    {
        $meta = [];
        $meta['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $meta['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $meta['added_at'] = ['type' => 'datetime', 'nullable' => true, 'fillable' => true, 'default' => null];
        $meta['weight'] = ['type' => 'integer', 'nullable' => true, 'fillable' => true, 'default' => null];
        $meta['code'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];

        $instance = new TestModel($meta, null);
        return $instance;
    }
}
