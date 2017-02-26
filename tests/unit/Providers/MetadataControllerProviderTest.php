<?php

namespace AlgoWeb\PODataLaravel\Providers;

use AlgoWeb\PODataLaravel\Controllers\ElectricBoogalooController;
use AlgoWeb\PODataLaravel\Controllers\MetadataControllerContainer;
use AlgoWeb\PODataLaravel\Models\TestCase as TestCase;
use AlgoWeb\PODataLaravel\Models\TestModel;
use Illuminate\Support\Facades\App;
use Mockery as m;
use AlgoWeb\PODataLaravel\Providers\MetadataControllerProvider as Provider;
use AlgoWeb\PODataLaravel\Controllers\TestController;

class MetadataControllerProviderTest extends TestCase
{
    public function testMapAssemblySplitModelHandling()
    {
        $controller1 = new TestController();
        $controller1->setMapping([TestModel::class =>
            [
                'create' => 'storeTestModel',
                'read' => 'showTestModel'
            ]]);

        $controller2 = new ElectricBoogalooController();
        $controller2->setMapping([TestModel::class =>
            [
                'update' => 'updateTestModel',
                'delete' => 'destroyTestModel'
            ]]);

        $container = m::mock(MetadataControllerContainer::class)->makePartial();
        App::instance('metadataControllers', $container);

        $expectedMap = ['create' => [ 'method' => 'storeTestModel', 'controller' => TestController::class]];
        $expectedMap['read'] = [ 'method' => 'showTestModel', 'controller' => TestController::class];
        $expectedMap['update'] = [ 'method' => 'updateTestModel', 'controller' => ElectricBoogalooController::class];
        $expectedMap['delete'] = [ 'method' => 'destroyTestModel', 'controller' => ElectricBoogalooController::class];

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
}