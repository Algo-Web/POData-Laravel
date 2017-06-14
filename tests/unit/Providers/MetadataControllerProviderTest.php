<?php

namespace AlgoWeb\PODataLaravel\Providers;

use AlgoWeb\PODataLaravel\Controllers\ElectricBoogalooController;
use AlgoWeb\PODataLaravel\Controllers\MetadataControllerContainer;
use AlgoWeb\PODataLaravel\Models\TestApplication;
use AlgoWeb\PODataLaravel\Models\TestCase as TestCase;
use AlgoWeb\PODataLaravel\Models\TestModel;
use ErrorException;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Mockery as m;
use AlgoWeb\PODataLaravel\Providers\MetadataControllerProvider as Provider;
use AlgoWeb\PODataLaravel\Controllers\TestController;

class MetadataControllerProviderTest extends TestCase
{
    public function testMapAssemblySplitEvenModelHandling()
    {
        $controller1 = m::mock(TestController::class)->makePartial();
        $controller1->setMapping([TestModel::class =>
            [
                'create' => 'storeTestModel',
                'read' => 'showTestModel'
            ]]);
        $controller1->shouldReceive('getMappings')->passthru()->once();

        $controller2 = m::mock(ElectricBoogalooController::class)->makePartial();
        $controller2->setMapping([TestModel::class =>
            [
                'update' => 'updateTestModel',
                'delete' => 'destroyTestModel'
            ]]);
        $controller2->shouldReceive('getMappings')->passthru()->once();

        $container = m::mock(MetadataControllerContainer::class)->makePartial();
        App::instance('metadataControllers', $container);

        $expectedMap = ['create' => [ 'method' => 'storeTestModel', 'controller' => get_class($controller1)]];
        $expectedMap['read'] = [ 'method' => 'showTestModel', 'controller' => get_class($controller1)];
        $expectedMap['update'] = [ 'method' => 'updateTestModel', 'controller' => get_class($controller2)];
        $expectedMap['delete'] = [ 'method' => 'destroyTestModel', 'controller' => get_class($controller2)];

        $foo = m::mock(MetadataControllerProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getCandidateControllers')->andReturn([$controller1, $controller2])->once();

        $foo->boot();

        $result = $container->getMetadata();
        $this->assertTrue(is_array($result));
        $inner = $result[TestModel::class];
        $this->assertTrue(is_array($inner));
        $this->assertEquals(4, count($inner));
        foreach ($expectedMap as $verb => $gubbins) {
            $this->assertTrue(isset($inner[$verb]) && is_array($inner[$verb]));
            $this->assertEquals($gubbins['method'], $inner[$verb]['method']);
            $this->assertEquals($gubbins['controller'], $inner[$verb]['controller']);
        }
    }

    public function testMapAssemblySplitUnevenModelHandling()
    {
        $controller1 = m::mock(TestController::class)->makePartial();
        $controller1->setMapping([TestModel::class =>
            [
                'create' => 'storeTestModel',
            ]]);
        $controller1->shouldReceive('getMappings')->passthru()->once();

        $controller2 = m::mock(ElectricBoogalooController::class)->makePartial();
        $controller2->setMapping([TestModel::class =>
            [
                'read' => 'showTestModel',
                'update' => 'updateTestModel',
                'delete' => 'destroyTestModel'
            ]]);
        $controller2->shouldReceive('getMappings')->passthru()->once();

        $container = m::mock(MetadataControllerContainer::class)->makePartial();
        App::instance('metadataControllers', $container);

        $expectedMap = ['create' => [ 'method' => 'storeTestModel', 'controller' => get_class($controller1)]];
        $expectedMap['read'] = [ 'method' => 'showTestModel', 'controller' => get_class($controller2)];
        $expectedMap['update'] = [ 'method' => 'updateTestModel', 'controller' => get_class($controller2)];
        $expectedMap['delete'] = [ 'method' => 'destroyTestModel', 'controller' => get_class($controller2)];

        $foo = m::mock(MetadataControllerProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getCandidateControllers')->andReturn([$controller1, $controller2])->once();

        $foo->boot();

        $result = $container->getMetadata();
        $this->assertTrue(is_array($result));
        $inner = $result[TestModel::class];
        $this->assertTrue(is_array($inner));
        $this->assertEquals(4, count($inner));
        foreach ($expectedMap as $verb => $gubbins) {
            $this->assertTrue(isset($inner[$verb]) && is_array($inner[$verb]));
            $this->assertEquals($gubbins['method'], $inner[$verb]['method']);
            $this->assertEquals($gubbins['controller'], $inner[$verb]['controller']);
        }
    }


    public function testMapAssemblyCollisionHandlingThrowException()
    {
        $controller1 = new TestController();
        $controller2 = new ElectricBoogalooController();
        $controller2->setMapping($controller1->getMapping());

        $container = m::mock(MetadataControllerContainer::class)->makePartial();
        App::instance('metadataControllers', $container);

        $expectedMap = ['create' => [ 'method' => 'storeTestModel', 'controller' => TestController::class]];
        $expectedMap['read'] = [ 'method' => 'showTestModel', 'controller' => TestController::class];
        $expectedMap['update'] = [ 'method' => 'updateTestModel', 'controller' => ElectricBoogalooController::class];
        $expectedMap['delete'] = [ 'method' => 'destroyTestModel', 'controller' => ElectricBoogalooController::class];

        $foo = m::mock(MetadataControllerProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getCandidateControllers')->andReturn([$controller1, $controller2])->once();

        $expected = 'assert(): Mapping already defined for model AlgoWeb\PODataLaravel\Models\TestModel'
                    .' and CRUD verb create failed';
        $actual = null;

        try {
            $foo->boot();
        } catch (\ErrorException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testMapAssemblySkipMissingControllers()
    {
        $container = m::mock(MetadataControllerContainer::class)->makePartial();
        App::instance('metadataControllers', $container);

        $expectedMap = ['create' => [ 'method' => 'storeTestModel', 'controller' => TestController::class]];
        $expectedMap['read'] = [ 'method' => 'showTestModel', 'controller' => TestController::class];
        $expectedMap['update'] = [ 'method' => 'updateTestModel', 'controller' => ElectricBoogalooController::class];
        $expectedMap['delete'] = [ 'method' => 'destroyTestModel', 'controller' => ElectricBoogalooController::class];

        $foo = m::mock(MetadataControllerProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getClassMap')->andReturn([PODATA_LARAVEL_APP_ROOT_NAMESPACE.'500MileController']);

        $foo->boot();

        $result = $container->getMetadata();
        $this->assertTrue(is_array($result));
        $this->assertEquals(0, count($result));
    }

    public function testBootHasCacheAndIsCaching()
    {
        //$cache = m::mock(\Illuminate\Cache\Repository::class)->makePartial();
        $cache = Cache::getFacadeRoot();
        $cache->shouldReceive('has')->withArgs(['metadataControllers'])->andReturn(true)->once();
        $cache->shouldReceive('get')->withArgs(['metadataControllers'])->andReturn('aybabtu')->once();
        //Cache::swap($cache);

        $foo = m::mock(MetadataControllerProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getIsCaching')->andReturn(true)->once();

        $foo->boot();
        $result = App::make('metadataControllers');
        $this->assertEquals('aybabtu', $result);
    }

    public function testBootHasCacheAndIsNotCaching()
    {
        //$cache = m::mock(\Illuminate\Cache\Repository::class)->makePartial();
        $cache = Cache::getFacadeRoot();
        $cache->shouldReceive('has')->withArgs(['metadataControllers'])->andReturn(true)->never();
        $cache->shouldReceive('get')->withArgs(['metadataControllers'])->andReturn('aybabtu')->never();
        $cache->shouldReceive('forget')->andReturnNull()->once();
        //Cache::swap($cache);

        $foo = m::mock(MetadataControllerProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getIsCaching')->andReturn(false)->once();

        $foo->boot();
    }

    public function testBootNoCacheAndIsCaching()
    {
        //$cache = m::mock(\Illuminate\Cache\Repository::class)->makePartial();
        $cache = Cache::getFacadeRoot();
        $cache->shouldReceive('has')->withArgs(['metadataControllers'])->andReturn(false)->once();
        $cache->shouldReceive('get')->withArgs(['metadataControllers'])->andReturn('aybabtu')->never();
        $cache->shouldReceive('put')->with('metadataControllers', m::any(), 10)->once();
        //Cache::swap($cache);

        $foo = m::mock(MetadataControllerProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getIsCaching')->andReturn(true)->once();

        $foo->boot();
    }

    public function testBootNonExistentControllers()
    {
        $container = m::mock(MetadataControllerContainer::class);
        $container->shouldReceive('setMetadata')->withArgs([[]])->passthru()->once();
        $container->shouldReceive('setMetadata')->with(m::not([]))->andReturnNull()->never();
        $container->shouldReceive('getMetadata')->passthru();
        App::instance('metadataControllers', $container);

        $cache = Cache::getFacadeRoot();
        $cache->shouldReceive('has')->withArgs(['metadataControllers'])->andReturn(false)->never();
        $cache->shouldReceive('get')->withArgs(['metadataControllers'])->andReturn('aybabtu')->never();
        $cache->shouldReceive('forget')->with('metadataControllers')->once();

        $foo = m::mock(MetadataControllerProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getClassMap')->andReturn(['abc', 'def'])->once();
        $foo->shouldReceive('getIsCaching')->andReturn(false)->once();

        $foo->boot();
        $result = App::make('metadataControllers');
        $metadata = $result->getMetadata();
        $this->assertTrue(is_array($metadata));
        $this->assertEquals(0, count($metadata));
    }

    public function testBootExceptionThrownDuringResolution()
    {
        $app = App::make('app');
        $app->setConsole(false);

        $container = m::mock(MetadataControllerContainer::class);
        $container->shouldReceive('setMetadata')->withArgs([[]])->passthru()->never();
        $container->shouldReceive('setMetadata')->with(m::not([]))->andReturnNull()->never();
        $container->shouldReceive('getMetadata')->passthru();
        App::instance('metadataControllers', $container);

        App::instance(TestController::class, $container);

        $cache = Cache::getFacadeRoot();
        $cache->shouldReceive('has')->withArgs(['metadataControllers'])->andReturn(false)->never();
        $cache->shouldReceive('get')->withArgs(['metadataControllers'])->andReturn('aybabtu')->never();
        $cache->shouldReceive('forget')->with('metadataControllers')->never();

        $foo = m::mock(MetadataControllerProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getClassMap')->andReturn([TestController::class])->once();
        $foo->shouldReceive('getIsCaching')->andReturn(false)->once();

        $expected = 'assert(): Resolved result not a controller failed';
        $actual = null;

        try {
            $foo->boot();
        } catch (ErrorException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }
}
