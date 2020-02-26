<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 26/02/20
 * Time: 1:42 AM
 */

namespace AlgoWeb\PODataLaravel\Orchestra\Tests\Unit\Query;

use AlgoWeb\PODataLaravel\Enums\ActionVerb;
use AlgoWeb\PODataLaravel\Orchestra\Tests\Models\OrchestraHasManyTestModel;
use AlgoWeb\PODataLaravel\Orchestra\Tests\Models\OrchestraPolymorphToManySourceModel;
use AlgoWeb\PODataLaravel\Orchestra\Tests\Models\OrchestraPolymorphToManyTestModel;
use AlgoWeb\PODataLaravel\Orchestra\Tests\Query\DummyReadQuery;
use AlgoWeb\PODataLaravel\Orchestra\Tests\TestCase;
use AlgoWeb\PODataLaravel\Query\LaravelReadQuery;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Mockery as m;
use POData\Common\InvalidOperationException;
use POData\Common\ODataException;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Metadata\SimpleMetadataProvider;
use POData\Providers\Query\QueryResult;
use POData\Providers\Query\QueryType;

class LaravelReadQueryTest extends TestCase
{
    /**
     * @throws \ReflectionException
     */
    public function testEagerLoadCollisionResolved()
    {
        $model = new OrchestraHasManyTestModel();
        $model->setEagerLoad(['parent']);

        $combo = ['parent', 'children'];

        $foo = m::mock(LaravelReadQuery::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('applyFiltering')->withArgs([$model, true, $combo, PHP_INT_MAX, 0, null])
            ->andThrow(InvalidOperationException::class);

        $input = function (ActionVerb $type, string $class, OrchestraHasManyTestModel $model) {
            return ActionVerb::READ() == $type && OrchestraHasManyTestModel::class == $class;
        };

        $foo->shouldReceive('getAuth->canAuth')
            ->withArgs($input)
            ->andReturn(true)->once();

        $type = QueryType::ENTITIES();
        $rSet = m::mock(ResourceSet::class);

        $this->expectException(InvalidOperationException::class);

        $foo->getResourceSet($type, $rSet, null, null, null, null, null, $combo, $model);
    }

    /**
     * @throws InvalidOperationException
     * @throws \POData\Common\ODataException
     * @throws \ReflectionException
     */
    public function testGetRelatedResourceReferenceOverMorphToMany()
    {
        $parent = new OrchestraPolymorphToManySourceModel();
        $this->assertTrue($parent->save());

        $child = new OrchestraPolymorphToManyTestModel();
        $this->assertTrue($child->save());

        $parent->sourceChildren()->attach($child);

        /** @var OrchestraPolymorphToManyTestModel $nuChild */
        $nuChild = $parent->sourceChildren()->firstOrFail();
        $this->assertEquals($child->getKey(), $nuChild->getKey());

        $nuParent = $nuChild->sourceParents()->firstOrFail();
        $this->assertEquals($parent->getKey(), $nuParent->getKey());

        /** @var SimpleMetadataProvider $meta */
        $meta = App::make('metadata');
        /** @var ResourceSet $parentSource */
        $parentSource = $meta->resolveResourceSet('OrchestraPolymorphToManySourceModels');
        $parentType = $parentSource->getResourceType();
        /** @var ResourceSet $targSource */
        $targSource = $meta->resolveResourceSet('OrchestraPolymorphToManyTestModels');

        $property = $parentType->resolveProperty('sourceChildren');

        /** @var LaravelReadQuery $foo */
        $foo = App::make(LaravelReadQuery::class);

        $result = $foo->getRelatedResourceReference($parentSource, $parent, $targSource, $property);
        $this->assertTrue($result instanceof OrchestraPolymorphToManyTestModel);
        $this->assertEquals($child->getKey(), $result->getKey());
    }

    /**
     * @throws InvalidOperationException
     * @throws \POData\Common\ODataException
     * @throws \ReflectionException
     */
    public function testGetRelatedResourceReferenceOverMorphedByMany()
    {
        $parent = new OrchestraPolymorphToManySourceModel();
        $this->assertTrue($parent->save());

        $child = new OrchestraPolymorphToManyTestModel();
        $this->assertTrue($child->save());

        $parent->sourceChildren()->attach($child);

        /** @var SimpleMetadataProvider $meta */
        $meta = App::make('metadata');
        /** @var ResourceSet $parentSource */
        $parentSource = $meta->resolveResourceSet('OrchestraPolymorphToManyTestModels');
        $parentType = $parentSource->getResourceType();

        /** @var ResourceSet $targSource */
        $targSource = $meta->resolveResourceSet('OrchestraPolymorphToManySourceModels');

        $property = $parentType->resolveProperty('sourceParents_OrchestraPolymorphToManySourceModels');

        /** @var LaravelReadQuery $foo */
        $foo = App::make(LaravelReadQuery::class);

        $result = $foo->getRelatedResourceReference($parentSource, $child, $targSource, $property);
        $this->assertTrue($result instanceof OrchestraPolymorphToManySourceModel);
        $this->assertEquals($parent->getKey(), $result->getKey());
    }

    public function testGetRelatedResourceSetBadAuthKabooms()
    {
        $model = new OrchestraHasManyTestModel();
        $model->setEagerLoad(['parent']);

        $combo = [];

        $foo = m::mock(LaravelReadQuery::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('applyFiltering')->withArgs([$model, true, $combo, PHP_INT_MAX, 0, null])
            ->andThrow(InvalidOperationException::class);

        $input = function (ActionVerb $type, ?string $class, ?OrchestraHasManyTestModel $model) {
            return ActionVerb::READ() == $type;
        };

        $foo->shouldReceive('getAuth->canAuth')
            ->withArgs($input)
            ->andReturn(false)->once();

        $type = QueryType::ENTITIES();
        $rSet = m::mock(ResourceSet::class);
        $tSet = m::mock(ResourceSet::class);
        $targProp = m::mock(ResourceProperty::class);

        $this->expectException(ODataException::class);

        $foo->getRelatedResourceSet(QueryType::COUNT(), $rSet, $model, $tSet, $targProp);
    }

    public function testGetNullResource()
    {
        $src = new OrchestraHasManyTestModel(['name' => 'notfoobar']);
        $this->assertTrue($src->save());

        $model = new OrchestraHasManyTestModel();
        $model->setEagerLoad(['parent']);

        /** @var LaravelReadQuery $foo */
        $foo = App::make(LaravelReadQuery::class);

        $result = $foo->getResource(null, null, ['name' => 'foobar'], null, $model);

        $this->assertNull($result);
    }

    public function testGetNullModelLoadOnGetResourceKabooms()
    {
        $model = new OrchestraHasManyTestModel();
        $model->setEagerLoad(['parent']);

        $nullModel = m::mock(OrchestraHasManyTestModel::class)->makePartial();
        $nullModel->shouldReceive('getEagerLoad')->andReturnNull()->once();

        $foo = m::mock(LaravelReadQuery::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getAuth->canAuth')->andReturn(true)->once();
        $foo->shouldReceive('checkSourceInstance')->andReturn($nullModel)->once();

        $this->expectException(InvalidOperationException::class);

        $foo->getResource(null, null, [], null, $model);
    }

    public function testPackageResultsFlagHasMore()
    {
        $qType = QueryType::COUNT();
        $resultCount = 1;
        $resultSet = ['a'];
        $skip = 0;
        $bulkCount = 11;
        $result = new QueryResult();
        $this->assertNull($result->hasMore);

        $foo = new DummyReadQuery();
        $foo->packageResourceSetResults($qType, $skip, $result, $resultSet, $resultCount, $bulkCount);

        $this->assertTrue($result->hasMore);
        $this->assertEquals(1, $result->count);
        $this->assertNull($result->results);
    }

    public function testPackageResultsFlagHasNoMore()
    {
        $qType = QueryType::COUNT();
        $resultCount = 1;
        $resultSet = ['a'];
        $skip = 0;
        $bulkCount = 1;
        $result = new QueryResult();
        $this->assertNull($result->hasMore);

        $foo = new DummyReadQuery();
        $foo->packageResourceSetResults($qType, $skip, $result, $resultSet, $resultCount, $bulkCount);

        $this->assertFalse($result->hasMore);
        $this->assertEquals(1, $result->count);
        $this->assertNull($result->results);
    }

    public function testNullFilteringHasZeroSkipDefault()
    {
        $nullModel = m::mock(OrchestraHasManyTestModel::class);
        $nullModel->shouldReceive('count')->andReturn(0);
        $nullModel->shouldReceive('skip')->withArgs([0])->andReturnSelf()->once();
        $nullModel->shouldReceive('take')->andReturnSelf()->once();
        $nullModel->shouldReceive('with')->andReturnSelf()->once();
        $nullModel->shouldReceive('get')->andReturn(new Collection())->once();

        $foo = new DummyReadQuery();

        list($bulkSetCount, $resultSet, $resultCount, $skip) = $foo->applyBasicFiltering(
            $nullModel,
            true
        );

        $this->assertEquals(0, $bulkSetCount);
        $this->assertEquals(0, $skip);
        $this->assertEquals(0, $resultCount);
    }

    public function testLargeSetFilterBorderlineLow()
    {
        $collect = m::mock(Collection::class);
        $collect->shouldReceive('slice')->withArgs([0])->andReturnSelf();
        $collect->shouldReceive('filter')->andReturnSelf()->once();
        $collect->shouldReceive('count')->andReturn(0);

        $nullModel = m::mock(OrchestraHasManyTestModel::class);
        $nullModel->shouldReceive('count')->andReturn(20000);
        $nullModel->shouldReceive('with')->andReturnSelf()->once();
        $nullModel->shouldReceive('get')->andReturn($collect)->once();

        $foo = new DummyReadQuery();

        list($bulkSetCount, $resultSet, $resultCount, $skip) = $foo->applyBasicFiltering(
            $nullModel,
            false
        );

        $this->assertEquals(20000, $bulkSetCount);
        $this->assertEquals(0, $skip);
        $this->assertEquals(0, $resultCount);
    }

    public function testLargeSetFilterBorderlineHigh()
    {
        $collect = m::mock(Collection::class);
        $collect->shouldReceive('slice')->withArgs([0])->andReturnSelf();
        $collect->shouldReceive('filter')->andReturnSelf();
        $collect->shouldReceive('count')->andReturn(0);

        $res = new OrchestraHasManyTestModel();
        $res = new Collection([$res]);

        $builder = m::mock(Builder::class)->shouldAllowMockingProtectedMethods();
        $builder->shouldReceive('chunk')->withArgs([5000, m::any()])->passthru();
        $builder->shouldReceive('enforceOrderBy')->andReturnNull();
        $builder->shouldReceive('forPage->get')->andReturn($res)->once();

        $nullModel = m::mock(OrchestraHasManyTestModel::class);
        $nullModel->shouldReceive('count')->andReturn(20001);
        $nullModel->shouldReceive('with')->andReturnSelf();
        $nullModel->shouldReceive('get')->andReturn($collect);
        $nullModel->shouldReceive('chunk')->withArgs([5000, m::any()])->passthru();
        $nullModel->shouldReceive('newQuery')->andReturn($builder);

        $foo = new DummyReadQuery();

        $filter = function () {
            return true;
        };

        list($bulkSetCount, $resultSet, $resultCount, $skip) = $foo->applyFilterFiltering(
            $nullModel,
            false,
            $filter
        );

        $this->assertEquals(20001, $bulkSetCount);
        $this->assertEquals(0, $skip);
        $this->assertEquals(1, $resultCount);
    }
}
