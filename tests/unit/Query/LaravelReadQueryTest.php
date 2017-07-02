<?php

namespace AlgoWeb\PODataLaravel\Query;

use AlgoWeb\PODataLaravel\Auth\NullAuthProvider;
use AlgoWeb\PODataLaravel\Models\TestCase as TestCase;
use AlgoWeb\PODataLaravel\Models\TestModel;
use AlgoWeb\PODataLaravel\Models\TestMorphManySource;
use AlgoWeb\PODataLaravel\Models\TestMorphTarget;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\App;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Query\QueryResult;
use POData\Providers\Query\QueryType;
use Mockery as m;

class LaravelReadQueryTest extends TestCase
{
    public function testGetResourceSetWithSkipTokenAndNoOrderBy()
    {
        $combo = [];
        for ($i = 4; $i < 7; $i++) {
            $foo = $this->generateTestModelWithMetadata();
            $foo->id = $i;
            $combo[] = $foo;
        }

        $instanceType = new \StdClass();
        $instanceType->name = 'AlgoWeb\\PODataLaravel\\Models\\TestModel';

        $mockResource = \Mockery::mock(ResourceSet::class);
        $mockResource->shouldReceive('getResourceType->getInstanceType')->andReturn($instanceType);

        $queryType = QueryType::ENTITIES_WITH_COUNT();

        $rawBuilder = $this->getBuilder();

        $rawResult = \Mockery::mock(\Illuminate\Database\Eloquent\Builder::class)->makePartial();
        $rawResult->setQuery($rawBuilder);
        $rawResult->shouldReceive('get')->andReturn(collect($combo));
        $rawResult->shouldReceive('take')->andReturnSelf()->once();
        $rawResult->shouldReceive('with')->andReturnSelf()->once();
        $this->assertTrue(null != ($rawResult->getQuery()->getProcessor()));

        $countback = collect(['a', 'b', 'c']);

        $sourceEntity = \Mockery::mock(TestModel::class);
        $sourceEntity->shouldReceive('where')->withArgs(['id', '<', '4'])->andReturn($countback)->once();
        $sourceEntity->shouldReceive('getKeyName')->andReturn('id');
        $sourceEntity->shouldReceive('getEagerLoad')->andReturn([]);
        $sourceEntity->shouldReceive('newQuery')->andReturnSelf()->never();
        $sourceEntity->shouldReceive('count')->andReturn(6)->once();
        $sourceEntity->shouldReceive('skip')->withArgs([3])->andReturn($rawResult)->once();
        App::instance($instanceType->name, $sourceEntity);

        $reader = new LaravelReadQuery();
        $foo = \Mockery::mock(LaravelQuery::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getReader')->andReturn($reader);

        $skipToken = '4';

        $result = $foo->getResourceSet($queryType, $mockResource, null, null, null, null, $skipToken);
        $this->assertTrue($result instanceof QueryResult);
        $this->assertEquals(3, count($result->results));
        $expectedId = [4, 5, 6];
        for ($i = 0; $i < 3; $i++) {
            $this->assertEquals($expectedId[$i], $result->results[$i]->id);
        }
    }

    public function testGetResourceSetWithSkipTokenTopValueAndNoOrderBy()
    {
        $combo = [];
        for ($i = 4; $i < 5; $i++) {
            $foo = $this->generateTestModelWithMetadata();
            $foo->id = $i;
            $combo[] = $foo;
        }

        $instanceType = new \StdClass();
        $instanceType->name = 'AlgoWeb\\PODataLaravel\\Models\\TestModel';

        $mockResource = \Mockery::mock(ResourceSet::class);
        $mockResource->shouldReceive('getResourceType->getInstanceType')->andReturn($instanceType);

        $queryType = QueryType::ENTITIES_WITH_COUNT();

        $rawBuilder = $this->getBuilder();

        $rawResult = \Mockery::mock(\Illuminate\Database\Eloquent\Builder::class)->makePartial();
        $rawResult->setQuery($rawBuilder);
        $rawResult->shouldReceive('get')->andReturn(collect($combo));
        $rawResult->shouldReceive('take')->withArgs([1])->andReturnSelf()->once();
        $rawResult->shouldReceive('with')->andReturnSelf()->once();
        $this->assertTrue(null != ($rawResult->getQuery()->getProcessor()));

        $countback = collect(['a', 'b', 'c']);

        $sourceEntity = \Mockery::mock(TestModel::class);
        $sourceEntity->shouldReceive('where')->withArgs(['id', '<', '4'])->andReturn($countback)->once();
        $sourceEntity->shouldReceive('getKeyName')->andReturn('id');
        $sourceEntity->shouldReceive('getEagerLoad')->andReturn([]);
        $sourceEntity->shouldReceive('newQuery')->andReturnSelf()->never();
        $sourceEntity->shouldReceive('count')->andReturn(6)->once();
        $sourceEntity->shouldReceive('skip')->withArgs([3])->andReturn($rawResult)->once();
        App::instance($instanceType->name, $sourceEntity);

        $reader = new LaravelReadQuery();
        $foo = \Mockery::mock(LaravelQuery::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getReader')->andReturn($reader);

        $skipToken = '4';

        $result = $foo->getResourceSet($queryType, $mockResource, null, null, 1, null, $skipToken);
        $this->assertTrue($result instanceof QueryResult);
        $this->assertEquals(1, count($result->results));
        $expectedId = [4];
        for ($i = 0; $i < 1; $i++) {
            $this->assertEquals($expectedId[$i], $result->results[$i]->id);
        }
    }

    public function testGetResourceSetFromRelationWithSkipToken()
    {
        $combo = [];
        for ($i = 4; $i < 7; $i++) {
            $foo = $this->generateTestMorphTargetWithMetadata();
            $foo->id = $i;
            $combo[] = $foo;
        }

        $instanceType = new \StdClass();
        $instanceType->name = 'AlgoWeb\\PODataLaravel\\Models\\TestMorphManySource';

        $resourceType = m::mock(ResourceType::class);
        $resourceType->shouldReceive('getInstanceType')->andReturn($instanceType);
        $resourceType->shouldReceive('getName')->andReturn('name');

        $mockResource = \Mockery::mock(ResourceSet::class);
        $mockResource->shouldReceive('getResourceType')->andReturn($resourceType);

        $queryType = QueryType::ENTITIES_WITH_COUNT();

        $related = new TestMorphManySource();

        $rawResult = \Mockery::mock(\Illuminate\Database\Eloquent\Builder::class)
            ->makePartial();
        $rawResult->shouldReceive('with')->andReturnSelf()->once();
        $rawResult->shouldReceive('get')->andReturn(collect($combo))->once();

        $countback = collect(['a', 'b', 'c']);

        $source = m::mock(HasMany::class)->makePartial();
        $source->shouldReceive('where')->withArgs(['id', '<', '4'])->andReturn($countback)->once();
        $source->shouldReceive('getRelated')->andReturn($related);
        $source->shouldReceive('skip')->andReturn($source)->once();
        $source->shouldReceive('take')->andReturn($rawResult)->once();
        $source->shouldReceive('count')->andReturn(6)->once();

        $auth = new NullAuthProvider();
        $foo = m::mock(LaravelReadQuery::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getAuth')->andReturn($auth);

        $skipToken = '4';

        $result = $foo->getResourceSet($queryType, $mockResource, null, null, null, null, $skipToken, $source);
        $this->assertEquals(6, $result->count);
        $this->assertEquals(3, count($result->results));
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

    /**
     * @return TestModel
     */
    private function generateTestMorphTargetWithMetadata()
    {
        $meta = [];
        $meta['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $meta['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $meta['added_at'] = ['type' => 'datetime', 'nullable' => true, 'fillable' => true, 'default' => null];
        $meta['weight'] = ['type' => 'integer', 'nullable' => true, 'fillable' => true, 'default' => null];
        $meta['code'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];

        $instance = new TestMorphTarget($meta, null);
        return $instance;
    }
}
