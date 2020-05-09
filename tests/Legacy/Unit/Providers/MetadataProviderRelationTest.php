<?php

declare(strict_types=1);

namespace Tests\Legacy\AlgoWeb\PODataLaravel\Unit\Providers;

use AlgoWeb\PODataLaravel\Models\MetadataGubbinsHolder;
use AlgoWeb\PODataLaravel\Models\MetadataRelationshipContainer;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Map;
use AlgoWeb\PODataLaravel\Providers\MetadataProvider;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Mockery as m;
use POData\Providers\Metadata\ResourceAssociationSet;
use POData\Providers\Metadata\ResourceEntityType;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourcePropertyKind;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Metadata\SimpleMetadataProvider;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\MetadataProviderDummy;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMonomorphicChildOfMorphTarget;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMonomorphicManySource;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMonomorphicManyTarget;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMonomorphicParentOfMorphTarget;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMorphManySource;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMorphManySourceAlternate;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMorphManyToManySource;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMorphManyToManyTarget;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMorphOneSource;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMorphTarget;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMorphTargetChild;
use Tests\Legacy\AlgoWeb\PODataLaravel\TestCase;

class MetadataProviderRelationTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $map = new Map();
        App::instance('objectmap', $map);
        $foo = m::mock(MetadataProvider::class)->makePartial();
        self::resetMetadataProvider($foo);
    }

    public function testBootFromTwoArmedPolymorphicRelationBothOneToMany()
    {
        $meta                    = [];
        $meta['alternate_id']    = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $meta['id']              = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $meta['name']            = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $meta['photo']           = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];
        $meta['morph_id']        = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];

        $this->setUpSchemaFacade();

        $classen = [ TestMorphManySource::class, TestMorphManySourceAlternate::class, TestMorphTarget::class];

        foreach ($classen as $className) {
            $testModel = new $className($meta);
            App::instance($className, $testModel);
        }

        $cache = m::mock(\Illuminate\Cache\Repository::class)->makePartial();
        $cache->shouldReceive('get')->withArgs(['metadata'])->andReturn(null)->once();
        $cache->shouldReceive('put')->with('metadata', m::any(), 10)->never();
        $cache->shouldReceive('get')->withArgs(['objectmap'])->andReturn(null)->once();
        $cache->shouldReceive('put')->with('objectmap', m::any(), 10)->never();
        $cache->shouldReceive('forget')->andReturn(null);
        Cache::swap($cache);

        $foo = new MetadataProviderDummy(App::make('app'));
        $foo->setCandidateModels($classen);

        $foo->boot();
        $simple = App::make('metadata');
        // now verify that actual-PK field shows up alongside literal-PK field for unknown-side models
        $source = $simple->resolveResourceType('TestMorphManySource');
        $this->assertNotNull($source);
        $this->assertTrue($source instanceof ResourceEntityType, get_class($source));
        $actualPK = $source->resolveProperty('id');
        $this->assertNotNull($actualPK);
        $this->assertTrue($actualPK instanceof ResourceProperty, get_class($actualPK));
        $this->assertTrue($actualPK->isKindOf(ResourcePropertyKind::KEY()));
        unset($source, $literalPK, $actualPK);

        $source = $simple->resolveResourceType('TestMorphManySourceAlternate');
        $this->assertNotNull($source);
        $this->assertTrue($source instanceof ResourceEntityType, get_class($source));
        $actualPK = $source->resolveProperty('alternate_id');
        $this->assertNotNull($actualPK);
        $this->assertTrue($actualPK instanceof ResourceProperty, get_class($actualPK));
        $this->assertTrue($actualPK->isKindOf(ResourcePropertyKind::KEY()));
    }

    public function testMonomorphicManyToManyRelation()
    {
        $metaRaw                    = [];
        $metaRaw['id']              = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name']            = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metaRaw['photo']           = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];
        $metaRaw['morph_id']        = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];

        $this->setUpSchemaFacade();

        $cacheStore = Cache::getFacadeRoot();
        Cache::shouldReceive('get')->withArgs(['metadata'])->andReturn(null)->once();
        Cache::shouldReceive('get')->withArgs(['objectmap'])->andReturn(null)->once();
        Cache::shouldReceive('forget')->withArgs(['metadataControllers'])->andReturn(null);
        Cache::shouldReceive('forget')->withArgs(['metadata'])->andReturn(null)->once();
        Cache::shouldReceive('forget')->withArgs(['objectmap'])->andReturn(null)->once();

        $classen = [TestMonomorphicManySource::class, TestMonomorphicManyTarget::class];

        $types = [];

        foreach ($classen as $className) {
            $testModel = new $className($metaRaw);
            App::instance($className, $testModel);
        }

        $abstractSet = m::mock(ResourceSet::class);

        $abstract = m::mock(ResourceEntityType::class);
        $abstract->shouldReceive('isAbstract')->andReturn(true)->atLeast(1);
        $abstract->shouldReceive('getFullName')->andReturn('polyMorphicPlaceholder');
        $abstract->shouldReceive('setCustomState')->andReturn(null);
        $abstract->shouldReceive('getCustomState')->andReturn($abstractSet);

        $holder = new MetadataRelationshipContainer();
        $foo    = m::mock(MetadataProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
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
        $metaRaw                    = [];
        $metaRaw['id']              = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name']            = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metaRaw['photo']           = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];
        $metaRaw['morph_id']        = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];
        $metaRaw['child_id']        = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];

        $this->setUpSchemaFacade();

        $cacheStore = Cache::getFacadeRoot();
        Cache::shouldReceive('get')->withArgs(['metadata'])->andReturn(null)->once();
        Cache::shouldReceive('get')->withArgs(['objectmap'])->andReturn(null)->once();
        Cache::shouldReceive('forget')->withArgs(['metadataControllers'])->andReturn(null);
        Cache::shouldReceive('forget')->withArgs(['metadata'])->andReturn(null)->once();
        Cache::shouldReceive('forget')->withArgs(['objectmap'])->andReturn(null)->once();

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

        $metadata  = App::make('metadata');
        $targAssoc = 'TestMorphOneSource_morphTarget_TestMorphTarget';
        $set       = $metadata->resolveAssociationSet($targAssoc);
        $this->assertTrue(isset($set));
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
        $metaRaw                    = [];
        $metaRaw['id']              = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name']            = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metaRaw['photo']           = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];
        $metaRaw['morph_id']        = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];

        $this->setUpSchemaFacade();

        $cacheStore = Cache::getFacadeRoot();
        Cache::shouldReceive('get')->withArgs(['metadata'])->andReturn(null)->once();
        Cache::shouldReceive('get')->withArgs(['objectmap'])->andReturn(null)->once();
        Cache::shouldReceive('forget')->withArgs(['metadataControllers'])->andReturn(null);
        Cache::shouldReceive('forget')->withArgs(['metadata'])->andReturn(null)->once();
        Cache::shouldReceive('forget')->withArgs(['objectmap'])->andReturn(null)->once();

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

        $metadata  = App::make('metadata');
        $targAssoc = 'TestMorphManySource_morphTarget_TestMorphTarget';
        $set       = $metadata->resolveAssociationSet($targAssoc);
        $this->assertTrue(isset($set));
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
        $this->markTestSkipped('Skipped until figure out/remedy root cause in POData');
        $metaRaw          = [];
        $metaRaw['id']    = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name']  = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metaRaw['photo'] = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];

        $this->setUpSchemaFacade();

        $cacheStore = Cache::getFacadeRoot();
        Cache::shouldReceive('get')->withArgs(['metadata'])->andReturn(null)->once();
        Cache::shouldReceive('get')->withArgs(['objectmap'])->andReturn(null)->once();
        Cache::shouldReceive('forget')->withArgs(['metadataControllers'])->andReturn(null);
        Cache::shouldReceive('forget')->withArgs(['metadata'])->andReturn(null)->once();
        Cache::shouldReceive('forget')->withArgs(['objectmap'])->andReturn(null)->once();

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

        $metadata  = App::make('metadata');
        $targAssoc = 'TestMorphManyToManyTarget_manyTarget_polyMorphicPlaceholder';
        $set       = $metadata->resolveAssociationSet($targAssoc);
        $this->assertTrue(isset($set));
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
        $this->markTestSkipped('Skipped until figure out/remedy root cause in POData');
        $metaRaw          = [];
        $metaRaw['id']    = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name']  = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
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

        $metadata  = App::make('metadata');
        $targAssoc = 'TestMorphTargetChild_morph_polyMorphicPlaceholder';
        $set       = $metadata->resolveAssociationSet($targAssoc);
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
        $set      = $metadata->resolveAssociationSet($revAssoc);
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
        $xml     = $metadata->getXML();
        $accTail = 'cg:GetterAccess="Public" cg:SetterAccess="Public"/>';

        $rel1 = '<NavigationProperty Name="morph" Relationship="Data.TestMorphTargetChild_morph_polyMorphicPlaceholder"'
                . ' ToRole="polyMorphicPlaceholders" FromRole="TestMorphTargetChildren_morph" ' . $accTail;
        $rel2 = '<NavigationProperty Name="childMorph" Relationship="Data.TestMorphTarget_childMorph_'
                . 'TestMorphTargetChild" ToRole="TestMorphTargetChildren" FromRole="TestMorphTargets_childMorph" '
                .$accTail;
        $type1 = '<EntityType OpenType="false" Abstract="false" Name="TestMorphTargetChild">';
        $type2 = '<EntityType OpenType="false" BaseType="Data.polyMorphicPlaceholder" Abstract="false" '
                 . 'Name="TestMorphTarget">';

        $this->assertTrue(false !== strpos($xml, $rel1));
        $this->assertTrue(false !== strpos($xml, $rel2));
        $this->assertTrue(false !== strpos($xml, $type1));
        $this->assertTrue(false !== strpos($xml, $type2));
    }

    public function testExposeHasManyThroughRelation()
    {
        $metaRaw                     = [];
        $metaRaw['id']               = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name']             = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metaRaw['photo']            = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];
        $metaRaw['child_id']         = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];
        $metaRaw['parent_id']        = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];

        $this->setUpSchemaFacade();

        $cacheStore = Cache::getFacadeRoot();
        Cache::shouldReceive('get')->withArgs(['metadata'])->andReturn(null)->once();
        Cache::shouldReceive('get')->withArgs(['objectmap'])->andReturn(null)->once();
        Cache::shouldReceive('forget')->withArgs(['metadataControllers'])->andReturn(null);
        Cache::shouldReceive('forget')->withArgs(['metadata'])->andReturn(null)->once();
        Cache::shouldReceive('forget')->withArgs(['objectmap'])->andReturn(null)->once();

        $classen = [TestMonomorphicParentOfMorphTarget::class, TestMorphTarget::class,
            TestMonomorphicChildOfMorphTarget::class];
        //shuffle($classen);

        foreach ($classen as $className) {
            $testModel = new $className($metaRaw);
            App::instance($className, $testModel);
        }

        $app = App::make('app');
        $foo = new MetadataProviderDummy($app);
        $foo->setCandidateModels($classen);
        $foo->boot();

        $metadata  = App::make('metadata');
        $xml       = $metadata->getXML();
        $relString = 'TestMonomorphicParentOfMorphTargets_monomorphicChildren';
        $this->assertTrue(false !== strpos($xml, $relString));
    }

    private function setUpSchemaFacade()
    {
        $schema = Schema::getFacadeRoot();
        $schema->shouldReceive('hasTable')->withArgs([config('database.migrations')])->andReturn(true);
        $schema->shouldReceive('hasTable')->andReturn(true);
        $schema->shouldReceive('getColumnListing')->andReturn([]);
    }
}
