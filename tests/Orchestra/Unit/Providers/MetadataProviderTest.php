<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 23/02/20
 * Time: 2:56 PM.
 */
namespace AlgoWeb\PODataLaravel\Orchestra\Tests\Unit\Providers;

use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\EntityGubbins;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Map;
use AlgoWeb\PODataLaravel\Orchestra\Tests\Models\RelationTestDummyModel;
use AlgoWeb\PODataLaravel\Orchestra\Tests\Providers\DummyMetadataProvider;
use AlgoWeb\PODataLaravel\Orchestra\Tests\TestCase;
use AlgoWeb\PODataLaravel\Providers\MetadataProvider;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Mockery as m;
use POData\Providers\Metadata\IMetadataProvider;
use POData\Providers\Metadata\ResourceEntityType;
use POData\Providers\Metadata\SimpleMetadataProvider;

class MetadataProviderTest extends TestCase
{
    public function checkNotBootedAfterInstantiation()
    {
        $app = m::mock(Application::class);

        $foo = new DummyMetadataProvider($app);
        $this->assertFalse($foo->isBooted());
    }

    public function testCheckBootMethod()
    {
        $app = m::mock(Application::class);

        $rType = m::mock(ResourceEntityType::class)->makePartial();
        $rType->shouldReceive('hasBaseType')->andReturn(false)->atLeast(1);
        $rType->shouldReceive('isAbstract')->andReturn(false);
        $rType->shouldReceive('getFullName')->andReturn('name');

        $meta = m::mock(SimpleMetadataProvider::class)->makePartial();
        $meta->shouldReceive('getContainerNamespace')->andReturn('name')->once();
        $meta->shouldReceive('addEntityType')->andReturn($rType);
        $meta->oDataEntityMap['name.name'] = 'foo';

        $gubbins = m::mock(EntityGubbins::class)->makePartial();
        $gubbins->shouldReceive('getName')->andReturn('name')->atLeast(1);
        $gubbins->shouldReceive('getClassName')->andReturn(RelationTestDummyModel::class)->atLeast(1);

        $map = m::mock(Map::class);
        $map->shouldReceive('getEntities')->andReturn([$gubbins]);
        $map->shouldReceive('setAssociations')->once();
        $map->shouldReceive('isOK')->andReturn(true)->times(1);
        $map->shouldReceive('getAssociations')->andReturn([])->atLeast(1);

        App::shouldReceive('forgetInstance')->withArgs(['metadata'])->once();
        App::shouldReceive('forgetInstance')->withArgs(['objectmap'])->once();
        App::shouldReceive('make')->withArgs(['metadata'])->andReturn($meta)->atLeast(1);
        App::shouldReceive('make')->withArgs(['objectmap'])->andReturn($map)->atLeast(1);

        $foo = new DummyMetadataProvider($app);
        $this->assertFalse($foo->isBooted());

        $foo->boot();
        $this->assertTrue($foo->isBooted());

        self::resetMetadataProvider($foo);
        $this->assertFalse($foo->isBooted());
    }

    public function runInArtisanProvider() : array
    {
        $result = [];

        $result[] = ['console' => false, 'unit' => false, 'result' => false];
        $result[] = ['console' => false, 'unit' => true, 'result' => false];
        $result[] = ['console' => true, 'unit' => false, 'result' => true];
        $result[] = ['console' => true, 'unit' => true, 'result' => false];


        return $result;
    }

    /**
     * @dataProvider runInArtisanProvider
     *
     * @param bool $console
     * @param bool $unit
     * @param $expected
     */
    public function testIsRunningInArtisan(bool $console, bool $unit, $expected)
    {
        App::shouldReceive('runningInConsole')->andReturn($console);
        App::shouldReceive('runningUnitTests')->andReturn($unit);

        $app = m::mock(Application::class);
        $foo = new MetadataProvider($app);

        $actual = $foo->isRunningInArtisan();
        $this->assertEquals($expected, $actual, 'Expected ' . $expected . ', actual ' . $actual);
    }

    public function testBootFromCache()
    {
        $app = m::mock(Application::class);
        $foo = new DummyMetadataProvider($app);
        $foo->setIsCaching(true);
        $this->assertTrue($foo->getIsCaching());
        $this->assertFalse($foo->isBooted());

        Cache::put('metadata', 'foo', 60);
        Cache::put('objectmap', 'bar', 60);

        $foo->boot();
        $this->assertTrue($foo->isBooted());
        $this->assertEquals('foo', App::make('metadata'));
        $this->assertEquals('bar', App::make('objectmap'));
    }

    public function testDontBootFromCacheMetadataNull()
    {
        $app = m::mock(Application::class);
        $foo = new DummyMetadataProvider($app);
        $foo->setIsCaching(true);
        $this->assertTrue($foo->getIsCaching());
        $this->assertFalse($foo->isBooted());

        Cache::put('metadata', null, 60);
        Cache::put('objectmap', 'bar', 60);

        $foo->boot();
        $this->assertTrue($foo->isBooted());
        $this->assertTrue(App::make('metadata') instanceof SimpleMetadataProvider);
        $this->assertTrue(App::make('objectmap') instanceof Map);
    }
}
