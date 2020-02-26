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
use AlgoWeb\PODataLaravel\Orchestra\Tests\TestCase;
use AlgoWeb\PODataLaravel\Query\LaravelReadQuery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Mockery as m;
use POData\Common\InvalidOperationException;
use POData\Common\ODataException;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Metadata\SimpleMetadataProvider;
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
}
