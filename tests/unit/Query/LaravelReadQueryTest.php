<?php

namespace AlgoWeb\PODataLaravel\Query;

use AlgoWeb\PODataLaravel\Auth\NullAuthProvider;
use AlgoWeb\PODataLaravel\Models\TestCase as TestCase;
use AlgoWeb\PODataLaravel\Models\TestModel;
use Illuminate\Support\Facades\App;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Query\QueryResult;
use POData\Providers\Query\QueryType;

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
