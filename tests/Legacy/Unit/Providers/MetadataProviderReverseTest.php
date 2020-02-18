<?php

namespace Tests\Legacy\AlgoWeb\PODataLaravel\Unit\Providers;

use AlgoWeb\PODataLaravel\Models\MetadataGubbinsHolder;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\Association;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\EntityGubbins;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Map;
use AlgoWeb\PODataLaravel\Providers\MetadataProvider;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Mockery as m;
use POData\Common\InvalidOperationException;
use POData\Providers\Metadata\SimpleMetadataProvider;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMonomorphicSource;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMonomorphicTarget;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMorphManySource;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMorphManySourceAlternate;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMorphTarget;
use Tests\Legacy\AlgoWeb\PODataLaravel\TestCase;

class MetadataProviderReverseTest extends TestCase
{
    private $metadataProvider;

    public function setUp() : void
    {
        parent::setUp();
        $map = new Map();
        App::instance('objectmap', $map);
        $holder = new MetadataGubbinsHolder();
        $this->metadataProvider = m::mock(MetadataProvider::class)
            ->makePartial()->shouldAllowMockingProtectedMethods();
        $this->metadataProvider->shouldReceive('getRelationHolder')->andReturn($holder);
        $this->metadataProvider->reset();
    }

    public function testReverseAcrossSingleRelation()
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
        $cache->shouldReceive('get')->withArgs(['metadata'])->andReturn(null)->once();
        $cache->shouldReceive('forget')->withArgs(['metadata'])->andReturn(null)->once();
        $cache->shouldReceive('put')->with('metadata', m::any(), 10)->never();
        $cache->shouldReceive('get')->withArgs(['objectmap'])->andReturn(null)->once();
        $cache->shouldReceive('forget')->withArgs(['objectmap'])->andReturn(null)->once();
        $cache->shouldReceive('put')->with('objectmap', m::any(), 10)->never();
        Cache::swap($cache);

        $foo = $this->metadataProvider;
        $foo->shouldReceive('getIsCaching')->andReturn(false);
        $foo->shouldReceive('getCandidateModels')->andReturn($classen)->atLeast(1);

        $foo->boot();

        $source = new TestMonomorphicSource($meta);
        $targ = new TestMonomorphicTarget($meta);

        $forwardOne = $foo->resolveReverseProperty($source, 'oneSource');
        $this->assertEquals('oneTarget', $forwardOne);
        $forwardMany = $foo->resolveReverseProperty($source, 'manySource');
        $this->assertEquals('manyTarget', $forwardMany);
        $revOne = $foo->resolveReverseProperty($targ, 'oneTarget');
        $this->assertEquals('oneSource', $revOne);
        $revMany = $foo->resolveReverseProperty($targ, 'manyTarget');
        $this->assertEquals('manySource', $revMany);
    }

    public function testReverseAcrossTwoArmedPolymorphicRelation()
    {
        $meta = [];
        $meta['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $meta['alternate_id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
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
        $cache->shouldReceive('forget')->withArgs(['metadata'])->andReturn(null)->once();
        $cache->shouldReceive('put')->with('metadata', m::any(), 10)->never();
        $cache->shouldReceive('get')->withArgs(['objectmap'])->andReturn(null)->once();
        $cache->shouldReceive('forget')->withArgs(['objectmap'])->andReturn(null)->once();
        $cache->shouldReceive('put')->with('objectmap', m::any(), 10)->never();
        Cache::swap($cache);

        $foo = $this->metadataProvider;
        $foo->shouldReceive('getIsCaching')->andReturn(false);
        $foo->shouldReceive('getCandidateModels')->andReturn($classen)->atLeast(1);

        $foo->boot();

        $left = new TestMorphManySource($meta);
        $right = new TestMorphManySourceAlternate($meta);
        $base = new TestMorphTarget($meta);

        $leftForward = $foo->resolveReverseProperty($left, 'morphTarget');
        $this->assertEquals('morph_TestMorphManySource', $leftForward);
        $rightForward = $foo->resolveReverseProperty($right, 'morphTarget');
        $this->assertEquals('morph_TestMorphManySourceAlternate', $rightForward);
        $backLeft = $foo->resolveReverseProperty($base, 'morph_TestMorphManySource');
        $this->assertEquals('morphTarget', $backLeft);
        $backRight = $foo->resolveReverseProperty($base, 'morph_TestMorphManySourceAlternate');
        $this->assertEquals('morphTarget', $backRight);
    }

    public function testReverseAcrossNoRelations()
    {
        $meta = [];
        $meta['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $meta['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $meta['photo'] = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];

        $this->setUpSchemaFacade();

        $simple = new SimpleMetadataProvider('Data', 'Data');
        App::instance('metadata', $simple);

        $classen = [ TestMorphManySource::class];

        foreach ($classen as $className) {
            $testModel = new $className($meta);
            App::instance($className, $testModel);
        }

        $cache = m::mock(\Illuminate\Cache\Repository::class)->makePartial();
        $cache->shouldReceive('get')->withArgs(['metadata'])->andReturn(null)->once();
        $cache->shouldReceive('forget')->withArgs(['metadata'])->andReturn(null)->once();
        $cache->shouldReceive('put')->with('metadata', m::any(), 10)->never();
        $cache->shouldReceive('get')->withArgs(['objectmap'])->andReturn(null)->once();
        $cache->shouldReceive('forget')->withArgs(['objectmap'])->andReturn(null)->once();
        $cache->shouldReceive('put')->with('objectmap', m::any(), 10)->never();
        Cache::swap($cache);

        $foo = $this->metadataProvider;
        $foo->shouldReceive('getIsCaching')->andReturn(false);
        $foo->shouldReceive('getCandidateModels')->andReturn($classen)->atLeast(1);

        $foo->boot();

        $left = new TestMorphManySource($meta);

        $result = $foo->resolveReverseProperty($left, 'property');
        $this->assertNull($result);
    }

    public function testReverseAcrossMissingProperty()
    {
        $meta = [];
        $meta['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $meta['alternate_id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
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
        $cache->shouldReceive('forget')->withArgs(['metadata'])->andReturn(null)->once();
        $cache->shouldReceive('put')->with('metadata', m::any(), 10)->never();
        $cache->shouldReceive('get')->withArgs(['objectmap'])->andReturn(null)->once();
        $cache->shouldReceive('forget')->withArgs(['objectmap'])->andReturn(null)->once();
        $cache->shouldReceive('put')->with('objectmap', m::any(), 10)->never();
        Cache::swap($cache);

        $foo = $this->metadataProvider;
        $foo->shouldReceive('getIsCaching')->andReturn(false);
        $foo->shouldReceive('getCandidateModels')->andReturn($classen)->atLeast(1);

        $foo->boot();

        $left = new TestMorphManySource($meta);
        $right = new TestMorphManySourceAlternate($meta);
        $base = new TestMorphTarget($meta);

        $leftForward = $foo->resolveReverseProperty($left, 'property');
        $this->assertNull($leftForward);
    }

    public function testMetadataResolveReversePropertyMappingNotPresent()
    {
        $foo = m::mock(MetadataProvider::class)->makePartial();
        $foo->shouldReceive('getObjectMap->resolveEntity')->andReturn(null)->once();

        $expected = 'Source model not defined';
        $actual = null;

        $left = new TestMorphManySource([]);

        try {
            $foo->resolveReverseProperty($left, 'property');
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testMetadataResolveReversePropertyMappingNameNotString()
    {
        $foo = m::mock(MetadataProvider::class)->makePartial();
        $foo->shouldReceive('getObjectMap->resolveEntity')->andReturn(null)->never();

        $expected = 'Property name must be string';
        $actual = null;

        $left = new TestMorphManySource([]);

        try {
            $foo->resolveReverseProperty($left, new \stdClass());
        } catch (InvalidOperationException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testMetadataResolveReversePropertyMappingAcrossPolymorphicMapping()
    {
        $assoc = m::mock(Association::class);
        $assoc->shouldReceive('getFirst->getRelationName')->andReturn('property')->once();
        $assoc->shouldReceive('getLast->getRelationName')->andReturnNull()->never();

        $gubbins = m::mock(EntityGubbins::class)->makePartial();
        $gubbins->shouldReceive('resolveAssociation')->andReturn($assoc)->once();

        $foo = m::mock(MetadataProvider::class)->makePartial();
        $foo->shouldReceive('getObjectMap->resolveEntity')->andReturn($gubbins)->once();

        $expected = '';
        $actual = null;

        $left = new TestMorphManySource([]);

        try {
            $foo->resolveReverseProperty($left, 'property');
        } catch (InvalidOperationException $e) {
            $actual = $e->getMessage();
        }
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
