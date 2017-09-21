<?php

namespace AlgoWeb\PODataLaravel\Providers;

use AlgoWeb\PODataLaravel\Models\MetadataGubbinsHolder;
use AlgoWeb\PODataLaravel\Models\MetadataProviderDummy;
use AlgoWeb\PODataLaravel\Models\MetadataRelationHolder;
use AlgoWeb\PODataLaravel\Models\TestCase;
use AlgoWeb\PODataLaravel\Models\TestMonomorphicChildOfMorphTarget;
use AlgoWeb\PODataLaravel\Models\TestMonomorphicManySource;
use AlgoWeb\PODataLaravel\Models\TestMonomorphicManyTarget;
use AlgoWeb\PODataLaravel\Models\TestMonomorphicOneAndManySource;
use AlgoWeb\PODataLaravel\Models\TestMonomorphicOneAndManyTarget;
use AlgoWeb\PODataLaravel\Models\TestMonomorphicParentOfMorphTarget;
use AlgoWeb\PODataLaravel\Models\TestMonomorphicSource;
use AlgoWeb\PODataLaravel\Models\TestMonomorphicTarget;
use AlgoWeb\PODataLaravel\Models\TestMorphManySource;
use AlgoWeb\PODataLaravel\Models\TestMorphManySourceAlternate;
use AlgoWeb\PODataLaravel\Models\TestMorphManyToManySource;
use AlgoWeb\PODataLaravel\Models\TestMorphManyToManyTarget;
use AlgoWeb\PODataLaravel\Models\TestMorphOneSource;
use AlgoWeb\PODataLaravel\Models\TestMorphOneSourceAlternate;
use AlgoWeb\PODataLaravel\Models\TestMorphTarget;
use AlgoWeb\PODataLaravel\Models\TestMorphTargetAlternate;
use AlgoWeb\PODataLaravel\Models\TestMorphTargetChild;
use AlgoWeb\PODataLaravel\Models\TestPolymorphicDualSource;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Mockery as m;
use POData\Providers\Metadata\ResourceAssociationSet;
use POData\Providers\Metadata\ResourceEntityType;
use POData\Providers\Metadata\ResourceSet;
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
        $expected[] = [
            'principalType' => TestMonomorphicManySource::class,
            'principalRSet' => TestMonomorphicManySource::class,
            'principalMult' => '*',
            'principalProp' => 'manySource',
            'dependentType' => TestMonomorphicManyTarget::class,
            'dependentRSet' => TestMonomorphicManyTarget::class,
            'dependentMult' => '*',
            'dependentProp' => 'manyTarget'
        ];
        $expected[] = [
            'principalType' => TestMonomorphicSource::class,
            'principalRSet' => TestMonomorphicSource::class,
            'principalMult' => '0..1',
            'principalProp' => 'oneSource',
            'dependentType' => TestMonomorphicTarget::class,
            'dependentRSet' => TestMonomorphicTarget::class,
            'dependentMult' => '1',
            'dependentProp' => 'oneTarget'
        ];
        $expected[] = [
            'principalType' => TestMonomorphicSource::class,
            'principalRSet' => TestMonomorphicSource::class,
            'principalMult' => '*',
            'principalProp' => 'manySource',
            'dependentType' => TestMonomorphicTarget::class,
            'dependentRSet' => TestMonomorphicTarget::class,
            'dependentMult' => '1',
            'dependentProp' => 'manyTarget'
        ];
        $expected[] = [
            'principalType' => TestMorphManyToManySource::class,
            'principalRSet' => TestMorphManyToManySource::class,
            'principalMult' => '*',
            'principalProp' => 'manySource',
            'dependentType' => TestMorphManyToManyTarget::class,
            'dependentRSet' => TestMorphManyToManyTarget::class,
            'dependentMult' => '*',
            'dependentProp' => 'manyTarget'
        ];
        $expected[] = [
            'principalType' => TestMonomorphicOneAndManySource::class,
            'principalRSet' => TestMonomorphicOneAndManySource::class,
            'principalMult' => '0..1',
            'principalProp' => 'oneTarget',
            'dependentType' => TestMonomorphicOneAndManyTarget::class,
            'dependentRSet' => TestMonomorphicOneAndManyTarget::class,
            'dependentMult' => '1',
            'dependentProp' => 'oneSource'
        ];
        $expected[] = [
            'principalType' => TestMonomorphicOneAndManySource::class,
            'principalRSet' => TestMonomorphicOneAndManySource::class,
            'principalMult' => '*',
            'principalProp' => 'manyTarget',
            'dependentType' => TestMonomorphicOneAndManyTarget::class,
            'dependentRSet' => TestMonomorphicOneAndManyTarget::class,
            'dependentMult' => '1',
            'dependentProp' => 'manySource'
        ];
        $expected[] = [
            'principalType' => TestMorphManySource::class,
            'principalRSet' => TestMorphManySource::class,
            'principalMult' => '*',
            'principalProp' => 'morphTarget',
            'dependentType' => TestMorphTarget::class,
            'dependentRSet' => TestMorphTarget::class,
            'dependentMult' => '1',
            'dependentProp' => 'morph'
        ];
        $expected[] = [
            'principalType' => TestMorphManySourceAlternate::class,
            'principalRSet' => TestMorphManySourceAlternate::class,
            'principalMult' => '*',
            'principalProp' => 'morphTarget',
            'dependentType' => TestMorphTarget::class,
            'dependentRSet' => TestMorphTarget::class,
            'dependentMult' => '1',
            'dependentProp' => 'morph'
        ];
        $expected[] = [
            'principalType' => TestMorphOneSource::class,
            'principalRSet' => TestMorphOneSource::class,
            'principalMult' => '0..1',
            'principalProp' => 'morphTarget',
            'dependentType' => TestMorphTarget::class,
            'dependentRSet' => TestMorphTarget::class,
            'dependentMult' => '1',
            'dependentProp' => 'morph'
        ];
        $expected[] = [
            'principalType' => TestMorphOneSourceAlternate::class,
            'principalRSet' => TestMorphOneSourceAlternate::class,
            'principalMult' => '0..1',
            'principalProp' => 'morphTarget',
            'dependentType' => TestMorphTarget::class,
            'dependentRSet' => TestMorphTarget::class,
            'dependentMult' => '1',
            'dependentProp' => 'morph'
        ];
        $expected[] = [
            'principalType' => TestPolymorphicDualSource::class,
            'principalRSet' => TestPolymorphicDualSource::class,
            'principalMult' => '0..1',
            'principalProp' => 'morphAlternate',
            'dependentType' => TestMorphTargetAlternate::class,
            'dependentRSet' => TestMorphTargetAlternate::class,
            'dependentMult' => '1',
            'dependentProp' => 'morph'
        ];
        $expected[] = [
            'principalType' => TestPolymorphicDualSource::class,
            'principalRSet' => TestPolymorphicDualSource::class,
            'principalMult' => '0..1',
            'principalProp' => 'morphTarget',
            'dependentType' => TestMorphTarget::class,
            'dependentRSet' => TestMorphTarget::class,
            'dependentMult' => '1',
            'dependentProp' => 'morph'
        ];

        $expected[] = [
            'principalType' => TestMorphTargetChild::class,
            'principalRSet' => TestMorphTargetChild::class,
            'principalMult' => '1',
            'principalProp' => 'morph',
            'dependentType' => TestMorphTarget::class,
            'dependentRSet' => TestMorphTarget::class,
            'dependentMult' => '0..1',
            'dependentProp' => 'childMorph'
        ];
        $expected[] = [
            'principalType' => TestMonomorphicChildOfMorphTarget::class,
            'principalRSet' => TestMonomorphicChildOfMorphTarget::class,
            'principalMult' => '1',
            'principalProp' => 'morphTarget',
            'dependentType' => TestMorphTarget::class,
            'dependentRSet' => TestMorphTarget::class,
            'dependentMult' => '*',
            'dependentProp' => 'monomorphicChildren'
        ];
        $expected[] = [
            'principalType' => TestMonomorphicParentOfMorphTarget::class,
            'principalRSet' => TestMonomorphicParentOfMorphTarget::class,
            'principalMult' => '*',
            'principalProp' => 'morphTargets',
            'dependentType' => TestMorphTarget::class,
            'dependentRSet' => TestMorphTarget::class,
            'dependentMult' => '1',
            'dependentProp' => 'monomorphicParent'
        ];

        $actual = $foo->calculateRoundTripRelations();
        $this->assertTrue(is_array($actual), 'Bidirectional relations result not an array');
        $counter = 0;
        $this->assertEquals(2 * count($expected), count($actual));
        foreach ($expected as $forward) {
            $this->assertTrue(in_array($forward, $actual), $counter);
            $reverse = $forward;
            $reverse['principalType'] = $forward['dependentType'];
            $reverse['principalMult'] = $forward['dependentMult'];
            $reverse['principalProp'] = $forward['dependentProp'];
            $reverse['principalRSet'] = $forward['dependentRSet'];
            $reverse['dependentType'] = $forward['principalType'];
            $reverse['dependentMult'] = $forward['principalMult'];
            $reverse['dependentProp'] = $forward['principalProp'];
            $reverse['dependentRSet'] = $forward['principalRSet'];
            $this->assertTrue(in_array($reverse, $actual), $counter);
            $counter++;
        }

        $counter = 0;
        foreach ($actual as $forward) {
            $reverse = $forward;
            $reverse['principalType'] = $forward['dependentType'];
            $reverse['principalMult'] = $forward['dependentMult'];
            $reverse['principalProp'] = $forward['dependentProp'];
            $reverse['principalRSet'] = $forward['dependentRSet'];
            $reverse['dependentType'] = $forward['principalType'];
            $reverse['dependentMult'] = $forward['principalMult'];
            $reverse['dependentProp'] = $forward['principalProp'];
            $reverse['dependentRSet'] = $forward['principalRSet'];
            $match = in_array($forward, $expected) || in_array($reverse, $expected);
            $this->assertTrue($match, 'Reverse pass: '.$counter);
            $counter++;
        }
    }

    public function testCalcRoundTripFromTwoArmedPolymorphicRelationBothOneToOne()
    {
        $metaRaw = [];
        $metaRaw['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['alternate_id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metaRaw['photo'] = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];

        $this->setUpSchemaFacade();

        $simple = new SimpleMetadataProvider('Data', 'Data');
        App::instance('metadata', $simple);

        $classen = [ TestMorphOneSource::class, TestMorphOneSourceAlternate::class, TestMorphTarget::class];

        foreach ($classen as $className) {
            $testModel = new $className($metaRaw, null);
            App::instance($className, $testModel);
        }

        $cache = m::mock(\Illuminate\Cache\Repository::class)->makePartial();
        $cache->shouldReceive('get')->withArgs(['metadata'])->andReturn(null);
        $cache->shouldReceive('put')->with('metadata', m::any(), 10);
        $cache->shouldReceive('forget')->andReturn(null);
        Cache::swap($cache);

        $holder = new MetadataGubbinsHolder();
        $foo = m::mock(MetadataProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getRelationHolder')->andReturn($holder);
        $foo->shouldReceive('getIsCaching')->andReturn(false);
        $foo->shouldReceive('getCandidateModels')->andReturn($classen)->atLeast(1);
        $foo->boot();

        $result = $foo->calculateRoundTripRelations();
        $this->assertEquals(4, count($result));
    }

    public function testCalcRoundTripFromTwoArmedPolymorphicRelationBothOneToMany()
    {
        $meta = [];
        $metaRaw['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['alternate_id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metaRaw['photo'] = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];

        $this->setUpSchemaFacade();

        $simple = new SimpleMetadataProvider('Data', 'Data');
        App::instance('metadata', $simple);

        $classen = [ TestMorphManySource::class, TestMorphManySourceAlternate::class, TestMorphTarget::class];

        foreach ($classen as $className) {
            $testModel = new $className($metaRaw, null);
            App::instance($className, $testModel);
        }

        $cache = m::mock(\Illuminate\Cache\Repository::class)->makePartial();
        $cache->shouldReceive('get')->withArgs(['metadata'])->andReturn(null)->once();
        $cache->shouldReceive('put')->with('metadata', m::any(), 10)->never();
        $cache->shouldReceive('forget')->andReturn(null);
        Cache::swap($cache);

        $holder = new MetadataGubbinsHolder();
        $foo = m::mock(MetadataProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getRelationHolder')->andReturn($holder);
        $foo->shouldReceive('getIsCaching')->andReturn(false);
        $foo->shouldReceive('getCandidateModels')->andReturn($classen)->atLeast(1);
        $foo->reset();
        $foo->boot();

        $result = $foo->calculateRoundTripRelations();
        $this->assertEquals(4, count($result));
    }

    public function testBootFromTwoArmedPolymorphicRelationBothOneToMany()
    {
        $meta = [];
        $meta['alternate_id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
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
        $cache->shouldReceive('get')->withArgs(['metadata'])->andReturn(null)->once();
        $cache->shouldReceive('put')->with('metadata', m::any(), 10)->never();
        $cache->shouldReceive('forget')->andReturn(null);
        Cache::swap($cache);

        $foo = new MetadataProviderDummy(App::make('app'));
        $foo->setCandidateModels($classen);

        $foo->boot();
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

        $holder = new MetadataGubbinsHolder();
        $foo = m::mock(MetadataProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getRelationHolder')->andReturn($holder);
        $foo->shouldReceive('getIsCaching')->andReturn(false);
        $foo->shouldReceive('getCandidateModels')->andReturn($classen)->once();

        $expected = [];
        $expected[TestMorphTarget::class] = [];
        $expected[TestMorphManySource::class] = [];
        $expected[TestMorphManySourceAlternate::class] = [];
        $expected[TestMorphTarget::class][TestMorphTarget::class] = ['morph'];
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

        $holder = new MetadataGubbinsHolder();
        $foo = m::mock(MetadataProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getRelationHolder')->andReturn($holder);
        $foo->shouldReceive('getIsCaching')->andReturn(false);
        $foo->shouldReceive('getCandidateModels')->andReturn($classen)->once();

        $expected = [];
        $actual = $foo->getPolymorphicRelationGroups();
        $this->assertEquals($expected, $actual);
    }

    public function testRelationGroupingMonomorphicRelationWithSingleKnownSide()
    {
        $metaRaw = [];
        $metaRaw['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metaRaw['photo'] = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];

        $this->setUpSchemaFacade();

        $simple = new SimpleMetadataProvider('Data', 'Data');
        App::instance('metadata', $simple);

        $classen = [ TestMonomorphicSource::class, TestMonomorphicTarget::class, TestMorphManyToManyTarget::class];

        foreach ($classen as $className) {
            $testModel = new $className($metaRaw, null);
            App::instance($className, $testModel);
        }

        $cache = m::mock(\Illuminate\Cache\Repository::class)->makePartial();
        $cache->shouldReceive('get')->withArgs(['metadata'])->andReturn(null)->once();
        $cache->shouldReceive('put')->with('metadata', m::any(), 10)->never();
        $cache->shouldReceive('forget')->andReturn(null);
        Cache::swap($cache);

        $holder = new MetadataGubbinsHolder();
        $foo = m::mock(MetadataProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getRelationHolder')->andReturn($holder);
        $foo->shouldReceive('getIsCaching')->andReturn(false);
        $foo->shouldReceive('getCandidateModels')->andReturn($classen)->atLeast(1);
        $foo->reset();
        $foo->boot();

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

        $holder = new MetadataGubbinsHolder();
        $foo = m::mock(MetadataProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getRelationHolder')->andReturn($holder);
        $foo->shouldReceive('getIsCaching')->andReturn(false);
        $foo->shouldReceive('getCandidateModels')->andReturn($classen)->once();

        $expected = [];
        $actual = $foo->getPolymorphicRelationGroups();
        $this->assertEquals($expected, $actual);
    }

    public function testPolymorphicRelationUpdateWithTwoArmedPolymorphicAndSingleMonomorphicRelation()
    {
        $metaRaw = [];
        $metaRaw['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['alternate_id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metaRaw['photo'] = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];

        $this->setUpSchemaFacade();

        $simple = new SimpleMetadataProvider('Data', 'Data');
        App::instance('metadata', $simple);

        $classen = [ TestMorphManySource::class, TestMorphManySourceAlternate::class, TestMorphTarget::class,
            TestMonomorphicSource::class, TestMonomorphicTarget::class];

        foreach ($classen as $className) {
            $testModel = new $className($metaRaw, null);
            App::instance($className, $testModel);
        }

        $cache = m::mock(\Illuminate\Cache\Repository::class)->makePartial();
        $cache->shouldReceive('get')->withArgs(['metadata'])->andReturn(null)->once();
        $cache->shouldReceive('put')->with('metadata', m::any(), 10)->never();
        $cache->shouldReceive('forget')->andReturn(null);
        Cache::swap($cache);

        $foo = new MetadataProviderDummy(App::make('app'));
        $foo->reset();
        $foo->setCandidateModels($classen);
        $foo->boot();

        $rels = $foo->calculateRoundTripRelations();
        $expected = $foo->calculateRoundTripRelations();
        $expected[0]['principalRSet'] = 'polyMorphicPlaceholder';
        $expected[1]['principalRSet'] = 'polyMorphicPlaceholder';
        $expected[2]['principalRSet'] = 'polyMorphicPlaceholder';
        $expected[3]['principalRSet'] = 'polyMorphicPlaceholder';
        $expected[0]['dependentRSet'] = 'polyMorphicPlaceholder';
        $expected[1]['dependentRSet'] = 'polyMorphicPlaceholder';
        $expected[2]['dependentRSet'] = 'polyMorphicPlaceholder';
        $expected[3]['dependentRSet'] = 'polyMorphicPlaceholder';

        // if groups is empty, bail right back out - nothing to do
        // else - need to loop through rels
        //  -- if neither principal type nor dependent type in groups, continue
        //  -- if one type is known and other is not
        //     -- check that unknown type is in list of types connected to known type - if not, KABOOM, else check that unknownProp is in connected property list for unknown type.  If not, KABOOM, else switch unknown type for placeholder
        //  -- if both types are known, need to figure out who is who - check properties available.  Whichever matches with connected property list is unknown.
        $actual = $foo->getRepairedRoundTripRelations();

        $this->assertEquals(count($rels), count($actual));
        $this->assertNotEquals($rels, $actual);
        $this->assertEquals($expected, $actual);
    }

    public function testGetRepairedRelationsWhenNoPolymorphics()
    {
        // yes, this is not actual structure - we're testing that in absence of polymorphic-relation gubbins,
        // raw relations are passed through unmodified
        $expected = ['foo', 'bar'];

        $holder = new MetadataGubbinsHolder();
        $foo = m::mock(MetadataProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getRelationHolder')->andReturn($holder);
        $foo->shouldReceive('calculateRoundTripRelations')->andReturn($expected);
        $foo->shouldReceive('getPolymorphicRelationGroups')->andReturn([]);
        $foo->reset();

        $actual = $foo->getRepairedRoundTripRelations();
        $this->assertEquals($expected, $actual);
    }

    public function testMonomorphicManyToManyRelation()
    {
        $metaRaw = [];
        $metaRaw['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metaRaw['photo'] = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];

        $this->setUpSchemaFacade();

        $cacheStore = Cache::getFacadeRoot();
        $cacheStore->shouldReceive('get')->withArgs(['metadata'])->andReturn(null)->once();

        $classen = [TestMonomorphicManySource::class, TestMonomorphicManyTarget::class];

        $types = [];

        foreach ($classen as $className) {
            $testModel = new $className($metaRaw);
            $testType = ($testModel->getXmlSchema());
            App::instance($className, $testModel);
            $types[$className] = $testType;
        }

        $abstractSet = m::mock(ResourceSet::class);

        $abstract = m::mock(ResourceEntityType::class);
        $abstract->shouldReceive('isAbstract')->andReturn(true)->atLeast(1);
        $abstract->shouldReceive('getFullName')->andReturn('polyMorphicPlaceholder');
        $abstract->shouldReceive('setCustomState')->andReturn(null);
        $abstract->shouldReceive('getCustomState')->andReturn($abstractSet);

        $holder = new MetadataGubbinsHolder();
        $foo = m::mock(MetadataProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getRelationHolder')->andReturn($holder);
        $foo->shouldReceive('getCandidateModels')->andReturn($classen);
        $foo->shouldReceive('addResourceSet')->withAnyArgs()->passthru();
        $foo->shouldReceive('getEntityTypesAndResourceSets')->withAnyArgs()->andReturn([$types, null, null]);

        $meta = new SimpleMetadataProvider('Data', 'Data');

        App::instance('metadata', $meta);

        $foo->boot();
    }

    public function testMorphOneToMorphTargetConcreteTypes()
    {
        $metaRaw = [];
        $metaRaw['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metaRaw['photo'] = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];

        $this->setUpSchemaFacade();

        $cacheStore = Cache::getFacadeRoot();
        $cacheStore->shouldReceive('get')->withArgs(['metadata'])->andReturn(null)->once();

        $classen = [TestMorphOneSource::class, TestMorphTarget::class];
        //shuffle($classen);

        foreach ($classen as $className) {
            $testModel = new $className($metaRaw);
            App::instance($className, $testModel);
        }

        $app = App::make('app');
        $foo = new MetadataProviderDummy($app);
        $foo->setCandidateModels($classen);
        $foo->boot();

        $metadata = App::make('metadata');
        $targAssoc = 'TestMorphOneSource_morphTarget_polyMorphicPlaceholder';
        $set = $metadata->resolveAssociationSet($targAssoc);
        $this->assertTrue($set instanceof ResourceAssociationSet, get_class($set));
        $end1Concrete = $set->getEnd1()->getConcreteType();
        $this->assertTrue($end1Concrete instanceof ResourceEntityType);
        $this->assertFalse($end1Concrete->isAbstract());
        $name1 = $end1Concrete->getInstanceType()->getName();
        $this->assertEquals(TestMorphOneSource::class, $name1);
        $end2Concrete = $set->getEnd2()->getConcreteType();
        $this->assertTrue($end2Concrete instanceof ResourceEntityType);
        $this->assertFalse($end2Concrete->isAbstract());
        $name2 = $end2Concrete->getInstanceType()->getName();
        $this->assertEquals(TestMorphTarget::class, $name2);
    }

    public function testMorphManyToMorphTargetConcreteTypes()
    {
        $metaRaw = [];
        $metaRaw['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metaRaw['photo'] = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];

        $this->setUpSchemaFacade();

        $cacheStore = Cache::getFacadeRoot();
        $cacheStore->shouldReceive('get')->withArgs(['metadata'])->andReturn(null)->once();

        $classen = [TestMorphManySource::class, TestMorphTarget::class];
        shuffle($classen);

        foreach ($classen as $className) {
            $testModel = new $className($metaRaw);
            App::instance($className, $testModel);
        }

        $app = App::make('app');
        $foo = new MetadataProviderDummy($app);
        $foo->setCandidateModels($classen);
        $foo->boot();

        $metadata = App::make('metadata');
        $targAssoc = 'TestMorphManySource_morphTarget_polyMorphicPlaceholder';
        $set = $metadata->resolveAssociationSet($targAssoc);
        $this->assertTrue($set instanceof ResourceAssociationSet, get_class($set));
        $end1Concrete = $set->getEnd1()->getConcreteType();
        $this->assertTrue($end1Concrete instanceof ResourceEntityType);
        $this->assertFalse($end1Concrete->isAbstract());
        $name1 = $end1Concrete->getInstanceType()->getName();
        $this->assertEquals(TestMorphManySource::class, $name1);
        $end2Concrete = $set->getEnd2()->getConcreteType();
        $this->assertTrue($end2Concrete instanceof ResourceEntityType);
        $this->assertFalse($end2Concrete->isAbstract());
        $name2 = $end2Concrete->getInstanceType()->getName();
        $this->assertEquals(TestMorphTarget::class, $name2);
    }

    public function testMorphManyToManyConcreteTypes()
    {
        $metaRaw = [];
        $metaRaw['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metaRaw['photo'] = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];

        $this->setUpSchemaFacade();

        $cacheStore = Cache::getFacadeRoot();
        $cacheStore->shouldReceive('get')->withArgs(['metadata'])->andReturn(null)->once();

        $classen = [TestMorphManyToManySource::class, TestMorphManyToManyTarget::class];
        shuffle($classen);

        foreach ($classen as $className) {
            $testModel = new $className($metaRaw);
            App::instance($className, $testModel);
        }

        $app = App::make('app');
        $foo = new MetadataProviderDummy($app);
        $foo->setCandidateModels($classen);
        $foo->boot();

        $metadata = App::make('metadata');
        $targAssoc = 'TestMorphManyToManyTarget_manyTarget_polyMorphicPlaceholder';
        $set = $metadata->resolveAssociationSet($targAssoc);
        $this->assertTrue($set instanceof ResourceAssociationSet, get_class($set));
        $end1Concrete = $set->getEnd1()->getConcreteType();
        $this->assertTrue($end1Concrete instanceof ResourceEntityType);
        $this->assertFalse($end1Concrete->isAbstract());
        $name1 = $end1Concrete->getInstanceType()->getName();
        $this->assertEquals(TestMorphManyToManyTarget::class, $name1);
        $end2Concrete = $set->getEnd2()->getConcreteType();
        $this->assertTrue($end2Concrete instanceof ResourceEntityType);
        $this->assertFalse($end2Concrete->isAbstract());
        $name2 = $end2Concrete->getInstanceType()->getName();
        $this->assertEquals(TestMorphManyToManySource::class, $name2);
    }

    public function testKnownOnBothEndsConcreteTypes()
    {
        $metaRaw = [];
        $metaRaw['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metaRaw['photo'] = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];

        $this->setUpSchemaFacade();

        $cacheStore = Cache::getFacadeRoot();
        $cacheStore->shouldReceive('get')->withArgs(['metadata'])->andReturn(null)->once();

        $classen = [TestMorphTargetChild::class, TestMorphTarget::class];
        //shuffle($classen);

        foreach ($classen as $className) {
            $testModel = new $className($metaRaw);
            App::instance($className, $testModel);
        }

        $app = App::make('app');
        $foo = new MetadataProviderDummy($app);
        $foo->setCandidateModels($classen);
        $foo->boot();

        $metadata = App::make('metadata');
        $targAssoc = 'TestMorphTargetChild_morph_polyMorphicPlaceholder';
        $set = $metadata->resolveAssociationSet($targAssoc);
        $this->assertTrue(isset($set), 'Association set not retrieved');
        $this->assertTrue($set instanceof ResourceAssociationSet, get_class($set));
        $end1Concrete = $set->getEnd1()->getConcreteType();
        $this->assertTrue($end1Concrete instanceof ResourceEntityType);
        $this->assertFalse($end1Concrete->isAbstract());
        $name1 = $end1Concrete->getInstanceType()->getName();
        $this->assertEquals(TestMorphTargetChild::class, $name1);
        $end2Concrete = $set->getEnd2()->getConcreteType();
        $this->assertTrue($end2Concrete instanceof ResourceEntityType);
        $this->assertFalse($end2Concrete->isAbstract());
        $name2 = $end2Concrete->getInstanceType()->getName();
        $this->assertEquals(TestMorphTarget::class, $name2);

        $revAssoc = 'TestMorphTarget_childMorph_TestMorphTargetChild';
        $set = $metadata->resolveAssociationSet($revAssoc);
        $this->assertTrue(isset($set), 'Association set not retrieved');
        $this->assertTrue($set instanceof ResourceAssociationSet, get_class($set));
        $end1Concrete = $set->getEnd1()->getConcreteType();
        $this->assertTrue($end1Concrete instanceof ResourceEntityType);
        $this->assertFalse($end1Concrete->isAbstract());
        $name1 = $end1Concrete->getInstanceType()->getName();
        $this->assertEquals(TestMorphTarget::class, $name1);
        $end2Concrete = $set->getEnd2()->getConcreteType();
        $this->assertTrue($end2Concrete instanceof ResourceEntityType);
        $this->assertFalse($end2Concrete->isAbstract());
        $name2 = $end2Concrete->getInstanceType()->getName();
        $this->assertEquals(TestMorphTargetChild::class, $name2);

        // now verify xml output to reduce chances of tripping ourselves up in future
        // model on known-side of relation - TestMorphTargetChild - must have its relation glommed onto placeholder
        // model on unknown-side - TestMorphTarget - must have its relation glommed onto child model
        $xml = $metadata->getXML();
        $accTail = 'cg:GetterAccess="Public" cg:SetterAccess="Public"/>';

        $rel1 = '<NavigationProperty Name="morph" Relationship="Data.TestMorphTargetChild_morph_polyMorphicPlaceholder"'
                .' ToRole="polyMorphicPlaceholders" FromRole="TestMorphTargetChildren_morph" '.$accTail;
        $rel2 = '<NavigationProperty Name="childMorph" Relationship="Data.TestMorphTarget_childMorph_'
                .'TestMorphTargetChild" ToRole="TestMorphTargetChildren" FromRole="TestMorphTargets_childMorph" '
                .$accTail;
        $type1 = '<EntityType OpenType="false" Abstract="false" Name="TestMorphTargetChild">';
        $type2 = '<EntityType OpenType="false" BaseType="Data.polyMorphicPlaceholder" Abstract="false" '
                 .'Name="TestMorphTarget">';

        $this->assertTrue(false !== strpos($xml, $rel1));
        $this->assertTrue(false !== strpos($xml, $rel2));
        $this->assertTrue(false !== strpos($xml, $type1));
        $this->assertTrue(false !== strpos($xml, $type2));
    }

    private function setUpSchemaFacade()
    {
        $schema = Schema::getFacadeRoot();
        $schema->shouldReceive('hasTable')->withArgs([config('database.migrations')])->andReturn(true);
        $schema->shouldReceive('hasTable')->andReturn(true);
        $schema->shouldReceive('getColumnListing')->andReturn([]);
    }
}
