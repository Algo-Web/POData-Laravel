<?php

namespace AlgoWeb\PODataLaravel\Providers;

use AlgoWeb\PODataLaravel\Models\TestCase;
use AlgoWeb\PODataLaravel\Models\TestMonomorphicManySource;
use AlgoWeb\PODataLaravel\Models\TestMonomorphicManyTarget;
use AlgoWeb\PODataLaravel\Models\TestMonomorphicOneAndManySource;
use AlgoWeb\PODataLaravel\Models\TestMonomorphicOneAndManyTarget;
use AlgoWeb\PODataLaravel\Models\TestMonomorphicSource;
use AlgoWeb\PODataLaravel\Models\TestMonomorphicTarget;
use AlgoWeb\PODataLaravel\Models\TestMorphManySource;
use AlgoWeb\PODataLaravel\Models\TestMorphManySourceAlternate;
use AlgoWeb\PODataLaravel\Models\TestMorphManyToManySource;
use AlgoWeb\PODataLaravel\Models\TestMorphManyToManyTarget;
use AlgoWeb\PODataLaravel\Models\TestMorphOneSource;
use AlgoWeb\PODataLaravel\Models\TestMorphOneSourceAlternate;
use AlgoWeb\PODataLaravel\Models\TestMorphTarget;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Mockery as m;
use POData\Providers\Metadata\SimpleMetadataProvider;

class MetadataProviderRelationTest extends TestCase
{
    public function testMonomorphicSourceAndTarget()
    {
        $app = App::make('app');
        $foo = new MetadataProvider($app);

        // only add one side of the expected relationships here, and explicitly reverse expected before checking for
        // reversed actual
        $expected = [];
        $expected[] = ["principalType" => TestMonomorphicManySource::class,
            "principalMult" => "*",
            "principalProp" => "manySource",
            "dependentType" => TestMonomorphicManyTarget::class,
            "dependentMult" => "*",
            "dependentProp" => "manyTarget"
        ];
        $expected[] = [
            "principalType" => TestMonomorphicSource::class,
            "principalMult" => "1",
            "principalProp" => "oneSource",
            "dependentType" => TestMonomorphicTarget::class,
            "dependentMult" => "0..1",
            "dependentProp" => "oneTarget"
        ];
        $expected[] = [
            "principalType" => TestMonomorphicSource::class,
            "principalMult" => "1",
            "principalProp" => "manySource",
            "dependentType" => TestMonomorphicTarget::class,
            "dependentMult" => "*",
            "dependentProp" => "manyTarget"
        ];
        $expected[] = [
            "principalType" => TestMorphManyToManySource::class,
            "principalMult" => "*",
            "principalProp" => "manySource",
            "dependentType" => TestMorphManyToManyTarget::class,
            "dependentMult" => "*",
            "dependentProp" => "manyTarget"
        ];
        $expected[] = [
            "principalType" => TestMonomorphicOneAndManySource::class,
            "principalMult" => "1",
            "principalProp" => "oneTarget",
            "dependentType" => TestMonomorphicOneAndManyTarget::class,
            "dependentMult" => "0..1",
            "dependentProp" => "manySource"
        ];
        $expected[] = [
            "principalType" => TestMonomorphicOneAndManySource::class,
            "principalMult" => "1",
            "principalProp" => "manyTarget",
            "dependentType" => TestMonomorphicOneAndManyTarget::class,
            "dependentMult" => "*",
            "dependentProp" => "manySource"
        ];
        $expected[] = [
            "principalType" => TestMorphManySource::class,
            "principalMult" => "1",
            "principalProp" => "morphTarget",
            "dependentType" => TestMorphTarget::class,
            "dependentMult" => "*",
            "dependentProp" => "morph"
        ];
        $expected[] = [
            "principalType" => TestMorphManySourceAlternate::class,
            "principalMult" => "1",
            "principalProp" => "morphTarget",
            "dependentType" => TestMorphTarget::class,
            "dependentMult" => "*",
            "dependentProp" => "morph"
        ];
        $expected[] = [
            "principalType" => TestMorphOneSource::class,
            "principalMult" => "1",
            "principalProp" => "morphTarget",
            "dependentType" => TestMorphTarget::class,
            "dependentMult" => "0..1",
            "dependentProp" => "morph"
        ];
        $expected[] = [
            "principalType" => TestMorphOneSourceAlternate::class,
            "principalMult" => "1",
            "principalProp" => "morphTarget",
            "dependentType" => TestMorphTarget::class,
            "dependentMult" => "0..1",
            "dependentProp" => "morph"
        ];

        $actual = $foo->calculateRoundTripRelations();
        $this->assertTrue(is_array($actual), "Bidirectional relations result not an array");
        $this->assertEquals(2 * count($expected), count($actual));
        foreach ($expected as $forward) {
            $this->assertTrue(in_array($forward, $actual));
            $reverse = $forward;
            $reverse['principalType'] = $forward['dependentType'];
            $reverse['principalMult'] = $forward['dependentMult'];
            $reverse['principalProp'] = $forward['dependentProp'];
            $reverse['dependentType'] = $forward['principalType'];
            $reverse['dependentMult'] = $forward['principalMult'];
            $reverse['dependentProp'] = $forward['principalProp'];
            $this->assertTrue(in_array($reverse, $actual));
        }
    }

    public function testCalcRoundTripFromTwoArmedPolymorphicRelationBothOneToOne()
    {
        $meta = [];
        $meta['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $meta['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $meta['photo'] = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];

        $this->setUpSchemaFacade();

        $simple = new SimpleMetadataProvider('Data', 'Data');
        App::instance('metadata', $simple);

        $classen = [ TestMorphOneSource::class, TestMorphOneSourceAlternate::class, TestMorphTarget::class];

        foreach ($classen as $className) {
            $testModel = new $className($meta);
            App::instance($className, $testModel);
        }

        $cache = m::mock(\Illuminate\Cache\Repository::class)->makePartial();
        $cache->shouldReceive('get')->withArgs(['metadata'])->andReturn(null);
        $cache->shouldReceive('put')->with('metadata', m::any(), 10);
        Cache::swap($cache);

        $foo = m::mock(MetadataProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getIsCaching')->andReturn(false);
        $foo->shouldReceive('getCandidateModels')->andReturn($classen)->once();

        $result = $foo->calculateRoundTripRelations();
        $this->assertEquals(4, count($result));
    }

    public function testCalcRoundTripFromTwoArmedPolymorphicRelationBothOneToMany()
    {
        $meta = [];
        $meta['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $meta['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $meta['photo'] = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];

        $this->setUpSchemaFacade();

        $simple = new SimpleMetadataProvider('Data', 'Data');
        App::instance('metadata', $simple);

        $classen = [ TestMorphManySource::class, TestMorphManySourceAlternate::class, TestMorphTarget::class];

        foreach ($classen as $className) {
            $testModel = new $className($meta);
            App::instance($className, $testModel);
        }

        $cache = m::mock(\Illuminate\Cache\Repository::class)->makePartial();
        $cache->shouldReceive('get')->withArgs(['metadata'])->andReturn(null)->never();
        $cache->shouldReceive('put')->with('metadata', m::any(), 10)->never();
        Cache::swap($cache);

        $foo = m::mock(MetadataProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getIsCaching')->andReturn(false);
        $foo->shouldReceive('getCandidateModels')->andReturn($classen)->once();

        $result = $foo->calculateRoundTripRelations();
        $this->assertEquals(4, count($result));
    }

    public function testRelationGroupingTwoArmedPolymorphicRelation()
    {
        $meta = [];
        $meta['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $meta['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $meta['photo'] = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];

        $this->setUpSchemaFacade();

        $simple = new SimpleMetadataProvider('Data', 'Data');
        App::instance('metadata', $simple);

        $classen = [ TestMorphManySource::class, TestMorphManySourceAlternate::class, TestMorphTarget::class];

        foreach ($classen as $className) {
            $testModel = new $className($meta);
            App::instance($className, $testModel);
        }

        $cache = m::mock(\Illuminate\Cache\Repository::class)->makePartial();
        $cache->shouldReceive('get')->withArgs(['metadata'])->andReturn(null)->never();
        $cache->shouldReceive('put')->with('metadata', m::any(), 10)->never();
        Cache::swap($cache);

        $foo = m::mock(MetadataProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getIsCaching')->andReturn(false);
        $foo->shouldReceive('getCandidateModels')->andReturn($classen)->once();

        $expected = [];
        $expected[TestMorphTarget::class] = [];
        $expected[TestMorphTarget::class][TestMorphManySource::class] = ['morphTarget'];
        $expected[TestMorphTarget::class][TestMorphManySourceAlternate::class] = ['morphTarget'];
        $actual = $foo->getPolymorphicRelationGroups();
        $this->assertEquals($expected, $actual);
    }

    public function testRelationGroupingMonomorphicRelation()
    {
        $meta = [];
        $meta['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $meta['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $meta['photo'] = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];

        $this->setUpSchemaFacade();

        $simple = new SimpleMetadataProvider('Data', 'Data');
        App::instance('metadata', $simple);

        $classen = [ TestMonomorphicSource::class, TestMonomorphicTarget::class];

        foreach ($classen as $className) {
            $testModel = new $className($meta);
            App::instance($className, $testModel);
        }

        $cache = m::mock(\Illuminate\Cache\Repository::class)->makePartial();
        $cache->shouldReceive('get')->withArgs(['metadata'])->andReturn(null)->never();
        $cache->shouldReceive('put')->with('metadata', m::any(), 10)->never();
        Cache::swap($cache);

        $foo = m::mock(MetadataProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getIsCaching')->andReturn(false);
        $foo->shouldReceive('getCandidateModels')->andReturn($classen)->once();

        $expected = [];
        $actual = $foo->getPolymorphicRelationGroups();
        $this->assertEquals($expected, $actual);
    }

    public function testRelationGroupingMonomorphicRelationWithSingleKnownSide()
    {
        $meta = [];
        $meta['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $meta['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $meta['photo'] = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];

        $this->setUpSchemaFacade();

        $simple = new SimpleMetadataProvider('Data', 'Data');
        App::instance('metadata', $simple);

        $classen = [ TestMonomorphicSource::class, TestMonomorphicTarget::class, TestMorphManyToManyTarget::class];

        foreach ($classen as $className) {
            $testModel = new $className($meta);
            App::instance($className, $testModel);
        }

        $cache = m::mock(\Illuminate\Cache\Repository::class)->makePartial();
        $cache->shouldReceive('get')->withArgs(['metadata'])->andReturn(null)->never();
        $cache->shouldReceive('put')->with('metadata', m::any(), 10)->never();
        Cache::swap($cache);

        $foo = m::mock(MetadataProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getIsCaching')->andReturn(false);
        $foo->shouldReceive('getCandidateModels')->andReturn($classen)->once();

        $expected = [];
        $actual = $foo->getPolymorphicRelationGroups();
        $this->assertEquals($expected, $actual);
    }

    public function testRelationGroupingMonomorphicRelationWithSingleUnknownSide()
    {
        $meta = [];
        $meta['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $meta['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $meta['photo'] = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];

        $this->setUpSchemaFacade();

        $simple = new SimpleMetadataProvider('Data', 'Data');
        App::instance('metadata', $simple);

        $classen = [ TestMonomorphicSource::class, TestMonomorphicTarget::class, TestMorphManyToManySource::class];

        foreach ($classen as $className) {
            $testModel = new $className($meta);
            App::instance($className, $testModel);
        }

        $cache = m::mock(\Illuminate\Cache\Repository::class)->makePartial();
        $cache->shouldReceive('get')->withArgs(['metadata'])->andReturn(null)->never();
        $cache->shouldReceive('put')->with('metadata', m::any(), 10)->never();
        Cache::swap($cache);

        $foo = m::mock(MetadataProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getIsCaching')->andReturn(false);
        $foo->shouldReceive('getCandidateModels')->andReturn($classen)->once();

        $expected = [];
        $actual = $foo->getPolymorphicRelationGroups();
        $this->assertEquals($expected, $actual);
    }

    private function setUpSchemaFacade()
    {
        $schema = Schema::getFacadeRoot();
        $schema->shouldReceive('hasTable')->withArgs([config('database.migrations')])->andReturn(true);
        $schema->shouldReceive('hasTable')->andReturn(true);
        $schema->shouldReceive('getColumnListing')->andReturn([]);
    }
}
