<?php

namespace AlgoWeb\PODataLaravel\Query;

use AlgoWeb\PODataLaravel\Models\LaravelReadQueryDummy;
use AlgoWeb\PODataLaravel\Models\TestCase as TestCase;
use AlgoWeb\PODataLaravel\Models\TestModel;
use AlgoWeb\PODataLaravel\Models\TestMonomorphicSource;
use AlgoWeb\PODataLaravel\Models\TestMonomorphicTarget;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Query\Builder;
use Mockery as m;
use POData\Common\InvalidOperationException;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Metadata\Type\Int32;
use POData\Providers\Metadata\Type\StringType;
use POData\Providers\Query\QueryType;
use POData\UriProcessor\QueryProcessor\OrderByParser\InternalOrderByInfo;
use POData\UriProcessor\QueryProcessor\OrderByParser\OrderByPathSegment;
use POData\UriProcessor\QueryProcessor\OrderByParser\OrderBySubPathSegment;
use POData\UriProcessor\QueryProcessor\SkipTokenParser\SkipTokenInfo;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\KeyDescriptor;

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

    public function testBadEagerLoad()
    {
        $expected = 'Eager-load elements must be non-empty strings';
        $actual = null;

        $query = m::mock(QueryType::class);
        $resource = m::mock(ResourceSet::class);
        $skipToken = null;
        $eagerLoad = ['start/the/dance', new \DateTime()];

        $foo = new LaravelReadQuery();

        try {
            $foo->getResourceSet($query, $resource, null, null, null, null, $skipToken, $eagerLoad);
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testSkipTokenWithSegmentValueCountMismatch()
    {
        $expected = 'Expected 1, got 0';
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
            $foo->getResourceSet($query, $resource, null, null, null, null, $skipToken, null, $source);
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

        $result = $foo->getResourceSet($query, $resource, null, null, null, null, $skipToken, null, $source);
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

        $result = $foo->getResourceSet($query, $resource, null, null, null, null, $skipToken, null, $source);
        $this->assertTrue(is_array($result->results));
        $this->assertEquals(0, count($result->results));
        $this->assertEquals(0, $result->count);
        $this->assertFalse($result->hasMore);
    }

    public function testGetNullResource()
    {
        $rSet = m::mock(ResourceSet::class);
        $source = m::mock(TestModel::class)->makePartial();
        $source->shouldReceive('get')->andReturn(collect([]));

        $foo = m::mock(LaravelReadQuery::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getAuth->canAuth')->andReturn(true)->once();

        $expected = null;
        $actual = $foo->getResource($rSet, null, [], null, $source);
        $this->assertEquals($expected, $actual);
    }

    public function testGetResourceWithBadEagerLoad()
    {
        $rSet = m::mock(ResourceSet::class);
        $source = m::mock(TestModel::class)->makePartial();
        $source->shouldReceive('get')->andReturn(collect([]));
        $source->shouldReceive('getEagerLoad')->andReturn(null)->once();

        $foo = m::mock(LaravelReadQuery::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getAuth->canAuth')->andReturn(true)->once();

        $expected = '';
        $actual = null;

        try {
            $actual = $foo->getResource($rSet, null, [], null, $source);
        } catch (InvalidOperationException $e) {
            $actual = $e->getMessage();
        }

        $this->assertEquals($expected, $actual);
    }

    public function testGetResourceFromRelation()
    {
        $rSet = m::mock(ResourceSet::class);

        $model = m::mock(TestMonomorphicTarget::class)->makePartial();

        $rel = m::mock(HasOne::class);
        $rel->shouldReceive('getRelated')->andReturn($model)->atLeast(1);
        $rel->shouldReceive('get')->andReturn(collect([]));
        $rel->shouldReceive('getEagerLoad')
            ->andThrow(new \Exception('Relation objects do not have getEagerLoad'))->never();

        $foo = m::mock(LaravelReadQuery::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getAuth->canAuth')->andReturn(true)->once();

        $expected = null;
        $actual = $foo->getResource($rSet, null, [], null, $rel);
        $this->assertEquals($expected, $actual);
    }

    public function testGetRelatedResourceReferenceWhenItIsntThere()
    {
        $rSet = m::mock(ResourceSet::class);
        $targProp = m::mock(ResourceProperty::class);
        $targProp->shouldReceive('getName')->andReturn('oneSource')->once();

        $foo = m::mock(LaravelReadQuery::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getAuth->canAuth')->andReturn(true)->once();

        $rel = m::mock(HasOne::class)->makePartial();
        $rel->shouldReceive('first')->andReturn(null)->once();

        $entity = m::mock(TestMonomorphicSource::class)->makePartial();
        $entity->shouldReceive('oneSource')->andReturn($rel)->once();

        $result = $foo->getRelatedResourceReference($rSet, $entity, $rSet, $targProp);
        $this->assertNull($result);
    }

    public function testGetRelatedResourceReferenceBadResult()
    {
        $rSet = m::mock(ResourceSet::class);
        $targProp = m::mock(ResourceProperty::class);
        $targProp->shouldReceive('getName')->andReturn('oneSource')->once();

        $foo = m::mock(LaravelReadQuery::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getAuth->canAuth')->andReturn(true)->once();

        $rel = m::mock(HasOne::class)->makePartial();
        $rel->shouldReceive('first')->andReturn(new \stdClass())->once();

        $entity = m::mock(TestMonomorphicSource::class)->makePartial();
        $entity->shouldReceive('oneSource')->andReturn($rel)->once();

        $expected = 'Model not retrieved from Eloquent relation';
        $actual = null;

        try {
            $foo->getRelatedResourceReference($rSet, $entity, $rSet, $targProp);
        } catch (InvalidOperationException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetResourceFromRelatedResourceSetBadResult()
    {
        $rSet = m::mock(ResourceSet::class);
        $targProp = m::mock(ResourceProperty::class);
        $targProp->shouldReceive('getName')->andReturn('oneSource')->once();

        $foo = m::mock(LaravelReadQuery::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getAuth->canAuth')->andReturn(true)->never();
        $foo->shouldReceive('getResource')->andReturn('chaboonagoonga')->once();

        $rel = m::mock(HasOne::class)->makePartial();

        $entity = m::mock(TestMonomorphicSource::class)->makePartial();
        $entity->shouldReceive('oneSource')->andReturn($rel)->once();

        $key = m::mock(KeyDescriptor::class)->makePartial();
        $key->shouldReceive('getValidatedNamedValues')->andReturn([]);

        $expected = 'GetResourceFromRelatedResourceSet must return an entity or null';
        $actual = null;

        try {
            $foo->getResourceFromRelatedResourceSet($rSet, $entity, $rSet, $targProp, $key);
        } catch (InvalidOperationException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testNonTrivialOrderByOnModel()
    {
        $rawModel = $this->generateTestModelWithMetadata();
        $rawMeta = $rawModel->metadata();

        $rSet = m::mock(ResourceSet::class);

        $builder = m::mock(Builder::class)->makePartial();
        $builder->shouldReceive('orderBy')->andReturn($builder)->times(1);
        $builder->shouldReceive('count')->andReturn(0)->atLeast(1);
        $builder->shouldReceive('skip')->andReturn($builder)->atLeast(1);
        $builder->shouldReceive('take')->andReturn($builder)->atLeast(1);
        $builder->shouldReceive('with')->andReturn($builder)->atLeast(1);
        $builder->shouldReceive('get')->andReturn(collect([]))->atLeast(1);

        $model = m::mock(TestModel::class)->makePartial();
        $model->shouldReceive('metadata')->andReturn($rawMeta);
        $model->shouldReceive('orderBy')->withArgs(['testmodel.name', 'desc'])->andReturn($builder)->times(1);
        $model->shouldReceive('getTable')->andReturn('testmodel');

        $firstSeg = m::mock(OrderBySubPathSegment::class);
        $firstSeg->shouldReceive('getName')->andReturn('name');

        $subSeg = m::mock(OrderBySubPathSegment::class);
        $subSeg->shouldReceive('getName')->andReturn('PrimaryKey');

        $orderSeg = m::mock(OrderByPathSegment::class);
        $orderSeg->shouldReceive('getSubPathSegments')->andReturn([$firstSeg, $subSeg]);
        $orderSeg->shouldReceive('isAscending')->andReturn(false)->atLeast(1);

        $orderBy = m::mock(InternalOrderByInfo::class)->makePartial();
        $orderBy->shouldReceive('getOrderByInfo->getOrderByPathSegments')->andReturn([$orderSeg]);

        $foo = m::mock(LaravelReadQuery::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getAuth->canAuth')->andReturn(true)->once();

        $expected = null;
        $type = QueryType::ENTITIES_WITH_COUNT();
        $actual = $foo->getResourceSet($type, $rSet, null, $orderBy, null, null, null, null, $model);
        $this->assertFalse($actual->hasMore);
        $this->assertEquals(0, $actual->count);
        $this->assertEquals(0, count($actual->results));
    }

    public function testApplyFilteringWithNullClosureOnBigSet()
    {
        $top = 0;
        $skip = 0;
        $sourceEntityInstance = m::mock(TestModel::class)->makePartial();
        $sourceEntityInstance->shouldReceive('count')->andReturn(25000)->once();
        $nullFilter = false;
        $rawLoad = [];
        $isValid = null;

        $foo = m::mock(LaravelReadQueryDummy::class)->makePartial();

        $expected = 'Filter closure not set';
        $actual = null;

        try {
            $foo->applyFiltering($top, $skip, $sourceEntityInstance, $nullFilter, $rawLoad, $isValid);
        } catch (InvalidOperationException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
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
