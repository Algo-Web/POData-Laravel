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
use AlgoWeb\PODataLaravel\Models\TestMorphTargetAlternate;
use AlgoWeb\PODataLaravel\Models\TestMorphTargetChild;
use AlgoWeb\PODataLaravel\Models\TestPolymorphicDualSource;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Mockery as m;
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
            "principalType" => TestMonomorphicManySource::class,
            "principalRSet" => TestMonomorphicManySource::class,
            "principalMult" => "*",
            "principalProp" => "manySource",
            "dependentType" => TestMonomorphicManyTarget::class,
            "dependentRSet" => TestMonomorphicManyTarget::class,
            "dependentMult" => "*",
            "dependentProp" => "manyTarget"
        ];
        $expected[] = [
            "principalType" => TestMonomorphicSource::class,
            "principalRSet" => TestMonomorphicSource::class,
            "principalMult" => "1",
            "principalProp" => "oneSource",
            "dependentType" => TestMonomorphicTarget::class,
            "dependentRSet" => TestMonomorphicTarget::class,
            "dependentMult" => "0..1",
            "dependentProp" => "oneTarget"
        ];
        $expected[] = [
            "principalType" => TestMonomorphicSource::class,
            "principalRSet" => TestMonomorphicSource::class,
            "principalMult" => "1",
            "principalProp" => "manySource",
            "dependentType" => TestMonomorphicTarget::class,
            "dependentRSet" => TestMonomorphicTarget::class,
            "dependentMult" => "*",
            "dependentProp" => "manyTarget"
        ];
        $expected[] = [
            "principalType" => TestMorphManyToManySource::class,
            "principalRSet" => TestMorphManyToManySource::class,
            "principalMult" => "*",
            "principalProp" => "manySource",
            "dependentType" => TestMorphManyToManyTarget::class,
            "dependentRSet" => TestMorphManyToManyTarget::class,
            "dependentMult" => "*",
            "dependentProp" => "manyTarget"
        ];
        $expected[] = [
            "principalType" => TestMonomorphicOneAndManySource::class,
            "principalRSet" => TestMonomorphicOneAndManySource::class,
            "principalMult" => "1",
            "principalProp" => "oneTarget",
            "dependentType" => TestMonomorphicOneAndManyTarget::class,
            "dependentRSet" => TestMonomorphicOneAndManyTarget::class,
            "dependentMult" => "0..1",
            "dependentProp" => "oneSource"
        ];
        $expected[] = [
            "principalType" => TestMonomorphicOneAndManySource::class,
            "principalRSet" => TestMonomorphicOneAndManySource::class,
            "principalMult" => "1",
            "principalProp" => "manyTarget",
            "dependentType" => TestMonomorphicOneAndManyTarget::class,
            "dependentRSet" => TestMonomorphicOneAndManyTarget::class,
            "dependentMult" => "*",
            "dependentProp" => "manySource"
        ];
        $expected[] = [
            "principalType" => TestMorphManySource::class,
            "principalRSet" => TestMorphManySource::class,
            "principalMult" => "1",
            "principalProp" => "morphTarget",
            "dependentType" => TestMorphTarget::class,
            "dependentRSet" => TestMorphTarget::class,
            "dependentMult" => "*",
            "dependentProp" => "morph"
        ];
        $expected[] = [
            "principalType" => TestMorphManySourceAlternate::class,
            "principalRSet" => TestMorphManySourceAlternate::class,
            "principalMult" => "1",
            "principalProp" => "morphTarget",
            "dependentType" => TestMorphTarget::class,
            "dependentRSet" => TestMorphTarget::class,
            "dependentMult" => "*",
            "dependentProp" => "morph"
        ];
        $expected[] = [
            "principalType" => TestMorphOneSource::class,
            "principalRSet" => TestMorphOneSource::class,
            "principalMult" => "1",
            "principalProp" => "morphTarget",
            "dependentType" => TestMorphTarget::class,
            "dependentRSet" => TestMorphTarget::class,
            "dependentMult" => "0..1",
            "dependentProp" => "morph"
        ];
        $expected[] = [
            "principalType" => TestMorphOneSourceAlternate::class,
            "principalRSet" => TestMorphOneSourceAlternate::class,
            "principalMult" => "1",
            "principalProp" => "morphTarget",
            "dependentType" => TestMorphTarget::class,
            "dependentRSet" => TestMorphTarget::class,
            "dependentMult" => "0..1",
            "dependentProp" => "morph"
        ];
        $expected[] = [
            "principalType" => TestPolymorphicDualSource::class,
            "principalRSet" => TestPolymorphicDualSource::class,
            "principalMult" => "1",
            "principalProp" => "morphAlternate",
            "dependentType" => TestMorphTargetAlternate::class,
            "dependentRSet" => TestMorphTargetAlternate::class,
            "dependentMult" => "0..1",
            "dependentProp" => "morph"
        ];
        $expected[] = [
            "principalType" => TestPolymorphicDualSource::class,
            "principalRSet" => TestPolymorphicDualSource::class,
            "principalMult" => "1",
            "principalProp" => "morphTarget",
            "dependentType" => TestMorphTarget::class,
            "dependentRSet" => TestMorphTarget::class,
            "dependentMult" => "0..1",
            "dependentProp" => "morph"
        ];

        $expected[] = [
            "principalType" => TestMorphTargetChild::class,
            "principalRSet" => TestMorphTargetChild::class,
            "principalMult" => "0..1",
            "principalProp" => "morph",
            "dependentType" => TestMorphTarget::class,
            "dependentRSet" => TestMorphTarget::class,
            "dependentMult" => "1",
            "dependentProp" => "childMorph"
        ];

        $actual = $foo->calculateRoundTripRelations();
        $this->assertTrue(is_array($actual), "Bidirectional relations result not an array");
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

        $foo = m::mock(MetadataProviderOld::class)->makePartial()->shouldAllowMockingProtectedMethods();
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

        $foo = m::mock(MetadataProviderOld::class)->makePartial()->shouldAllowMockingProtectedMethods();
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

        $foo = m::mock(MetadataProviderOld::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getIsCaching')->andReturn(false);
        $foo->shouldReceive('getCandidateModels')->andReturn($classen)->once();

        $expected = [];
        $expected[TestMorphTarget::class] = [];
        $expected[TestMorphTarget::class][TestMorphManySource::class] = ['morphTarget'];
        $expected[TestMorphTarget::class][TestMorphManySourceAlternate::class] = ['morphTarget'];
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

        $foo = m::mock(MetadataProviderOld::class)->makePartial()->shouldAllowMockingProtectedMethods();
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

        $foo = m::mock(MetadataProviderOld::class)->makePartial()->shouldAllowMockingProtectedMethods();
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

        $foo = m::mock(MetadataProviderOld::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getIsCaching')->andReturn(false);
        $foo->shouldReceive('getCandidateModels')->andReturn($classen)->once();

        $expected = [];
        $actual = $foo->getPolymorphicRelationGroups();
        $this->assertEquals($expected, $actual);
    }

    public function testPolymorphicRelationUpdateWithTwoArmedPolymorphicAndSingleMonomorphicRelation()
    {
        $meta = [];
        $meta['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $meta['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $meta['photo'] = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];

        $this->setUpSchemaFacade();

        $simple = new SimpleMetadataProvider('Data', 'Data');
        App::instance('metadata', $simple);

        $classen = [ TestMorphManySource::class, TestMorphManySourceAlternate::class, TestMorphTarget::class,
            TestMonomorphicSource::class, TestMonomorphicTarget::class];

        foreach ($classen as $className) {
            $testModel = new $className($meta);
            App::instance($className, $testModel);
        }

        $cache = m::mock(\Illuminate\Cache\Repository::class)->makePartial();
        $cache->shouldReceive('get')->withArgs(['metadata'])->andReturn(null)->never();
        $cache->shouldReceive('put')->with('metadata', m::any(), 10)->never();
        Cache::swap($cache);

        $foo = m::mock(MetadataProviderOld::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getIsCaching')->andReturn(false);
        $foo->shouldReceive('getCandidateModels')->andReturn($classen)->atLeast(1);

        $rels = $foo->calculateRoundTripRelations();
        $expected = $foo->calculateRoundTripRelations();
        $expected[4]['principalRSet'] = 'polyMorphicPlaceholder';
        $expected[6]['principalRSet'] = 'polyMorphicPlaceholder';
        $expected[5]['dependentRSet'] = 'polyMorphicPlaceholder';
        $expected[7]['dependentRSet'] = 'polyMorphicPlaceholder';

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

        $foo = m::mock(MetadataProviderOld::class)->makePartial()->shouldAllowMockingProtectedMethods();
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

        $foo = m::mock(MetadataProviderOld::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getCandidateModels')->andReturn($classen);
        $foo->shouldReceive('addResourceSet')->withAnyArgs()->passthru();
        $foo->shouldReceive('getEntityTypesAndResourceSets')->withAnyArgs()->andReturn([$types, null, null]);

        $meta = \Mockery::mock(SimpleMetadataProvider::class)->makePartial();
        $meta->shouldReceive('addEntityType')
            ->with(m::any(), 'polyMorphicPlaceholder', true, null)
            ->andReturn($abstract);
        $meta->shouldReceive('addKeyProperty')->andReturnNull()->atLeast(1);
        $meta->shouldReceive('addPrimitiveProperty')->andReturnNull()->atLeast(1);
        $meta->shouldReceive('addResourceSetReferencePropertyBidirectional')
            ->withAnyArgs()->andReturn(null)->atLeast(1);
        $meta->shouldReceive('addResourceReferenceSinglePropertyBidirectional')
            ->withAnyArgs()->andReturn(null)->never();
        $meta->shouldReceive('addResourceReferencePropertyBidirectional')
            ->withAnyArgs()->andReturn(null)->never();

        App::instance('metadata', $meta);

        $foo->boot();
    }

    private function setUpSchemaFacade()
    {
        $schema = Schema::getFacadeRoot();
        $schema->shouldReceive('hasTable')->withArgs([config('database.migrations')])->andReturn(true);
        $schema->shouldReceive('hasTable')->andReturn(true);
        $schema->shouldReceive('getColumnListing')->andReturn([]);
    }
}
