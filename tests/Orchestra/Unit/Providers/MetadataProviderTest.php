<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 23/02/20
 * Time: 2:56 PM.
 */
namespace AlgoWeb\PODataLaravel\Orchestra\Tests\Unit\Providers;

use _HumbugBox7a5b998f2c3f\Roave\BetterReflection\Reflection\ReflectionClass;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationMonomorphic;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\EntityGubbins;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Map;
use AlgoWeb\PODataLaravel\Orchestra\Tests\Models\OrchestraTestModel;
use AlgoWeb\PODataLaravel\Orchestra\Tests\Models\RelationTestDummyModel;
use AlgoWeb\PODataLaravel\Orchestra\Tests\Providers\DummyMetadataProvider;
use AlgoWeb\PODataLaravel\Orchestra\Tests\TestCase;
use AlgoWeb\PODataLaravel\Providers\MetadataProvider;
use AlgoWeb\PODataLaravel\Query\LaravelReadQuery;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Mockery as m;
use POData\Common\InvalidOperationException;
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

    public function runInArtisanProvider(): array
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

    /**
     * @throws \ReflectionException
     */
    public function testExtractHandlesBindingException()
    {
        $map = new Map();

        $app = App::make('app');
        $foo = new MetadataProvider($app);

        App::shouldReceive('make')->withArgs(['objectmap'])->andReturn($map)->once();
        App::shouldReceive('make')->withArgs(['app'])->andReturn($app);
        App::shouldReceive('make')->withArgs([OrchestraTestModel::class])->andThrows(BindingResolutionException::class);

        $reflec = new \ReflectionClass($foo);
        $method = $reflec->getMethod('extract');
        $method->setAccessible(true);

        $names = [OrchestraTestModel::class];

        /** @var Map $result */
        $result = $method->invoke($foo, $names);
        $this->assertEquals(0, count($result->getEntities()));
    }

    /**
     * @throws \ReflectionException
     */
    public function testImplementHandlesBadBaseType()
    {
        $badType = m::mock(ResourceEntityType::class);
        $badType->shouldReceive('hasBaseType')->andReturn(true)->once();

        $meta = m::mock(SimpleMetadataProvider::class);
        $meta->shouldReceive('getContainerNamespace')->andReturn('Data');
        $meta->shouldReceive('addEntityType')->andReturn($badType);

        $app = App::make('app');
        $foo = new MetadataProvider($app);

        App::shouldReceive('make')->withArgs(['metadata'])->andReturn($meta)->once();

        $reflec = new \ReflectionClass($foo);
        $method = $reflec->getMethod('implement');
        $method->setAccessible(true);

        $gubbins = m::mock(EntityGubbins::class);
        $gubbins->shouldReceive('getClassName')->andReturn(OrchestraTestModel::class)->once();
        $gubbins->shouldReceive('getName')->andReturn('OrchestraTestModel')->once();

        $map = m::mock(Map::class);
        $map->shouldReceive('getEntities')->andReturn([$gubbins])->once();

        $this->expectException(InvalidOperationException::class);

        $method->invoke($foo, $map);
    }

    /**
     * @throws \ReflectionException
     */
    public function testImplementDetectsCountMismatch()
    {
        $badType = m::mock(ResourceEntityType::class)->makePartial();
        $badType->shouldReceive('hasBaseType')->andReturn(false)->once();
        $badType->shouldReceive('isAbstract')->andReturn(false);
        $badType->shouldReceive('getFullName')->andReturn('fullName');

        $meta = m::mock(SimpleMetadataProvider::class);
        $meta->shouldReceive('getContainerNamespace')->andReturn('Data');
        $meta->shouldReceive('addEntityType')->andReturn($badType);
        $meta->shouldReceive('addResourceSet')->passthru()->once();
        $meta->oDataEntityMap['all.your'] = ['base.are.belong.to.us'];
        $meta->oDataEntityMap['Data.OrchestraTestModel'] = null;

        $app = App::make('app');
        $foo = new MetadataProvider($app);

        App::shouldReceive('make')->withArgs(['metadata'])->andReturn($meta);

        $reflec = new \ReflectionClass($foo);
        $method = $reflec->getMethod('implement');
        $method->setAccessible(true);

        $gubbins = m::mock(EntityGubbins::class)->makePartial();
        $gubbins->shouldReceive('getClassName')->andReturn(OrchestraTestModel::class)->once();
        $gubbins->shouldReceive('getName')->andReturn('OrchestraTestModel')->once();

        $map = m::mock(Map::class);
        $map->shouldReceive('getEntities')->andReturn([$gubbins])->once();

        $this->expectException(InvalidOperationException::class);
        $this->expectExceptionMessage('Expected 2 items, actually got 3');

        $method->invoke($foo, $map);
    }

    /**
     * @throws \ReflectionException
     */
    public function testImplementHandlesBadAssociation()
    {
        $badAssociation = m::mock(AssociationMonomorphic::class);
        $badAssociation->shouldReceive('isOk')->andReturn(false)->once();

        $app = App::make('app');
        $foo = new MetadataProvider($app);

        $meta = m::mock(SimpleMetadataProvider::class);
        $meta->shouldReceive('getContainerNamespace')->andReturn('Data');

        App::shouldReceive('make')->withArgs(['metadata'])->andReturn($meta);

        $reflec = new \ReflectionClass($foo);
        $method = $reflec->getMethod('implement');
        $method->setAccessible(true);

        $map = m::mock(Map::class);
        $map->shouldReceive('getEntities')->andReturn([])->once();
        $map->shouldReceive('getAssociations')->andReturn([$badAssociation])->twice();

        $this->expectException(InvalidOperationException::class);
        $this->expectExceptionMessage('');

        $method->invoke($foo, $map);
    }
}
