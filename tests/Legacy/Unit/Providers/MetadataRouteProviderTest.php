<?php

declare(strict_types=1);

namespace Tests\Legacy\AlgoWeb\PODataLaravel\Unit\Providers;

use AlgoWeb\PODataLaravel\Providers\MetadataRouteProvider;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;
use Mockery as m;
use Tests\Legacy\AlgoWeb\PODataLaravel\TestCase as TestCase;

class MetadataRouteProviderTest extends TestCase
{
    public function tearDown(): void
    {
        putenv('APP_ENABLE_AUTH=false');
        parent::tearDown();
    }

    public function testShouldGetAuthApiWithAuthEnabled()
    {
        putenv('APP_ENABLE_AUTH=true');

        $app = App::make('app');
        $prov = new MetadataRouteProvider($app);
        $prov->boot();

        $hasApiBitz         = interface_exists(\Illuminate\Contracts\Auth\Factory::class);
        $expectedMiddleware = $hasApiBitz ? 'auth:api' : 'auth.basic';
        $expected           = ['odata.svc/$metadata' => null, 'odata.svc/{section}' => $expectedMiddleware, 'odata.svc' => null];
        $actual             = [];

        $allRoutes = Route::getRoutes();

        foreach ($allRoutes as $target => $route) {
            $uri          = $route->uri();
            $actionArray  = $route->getAction();
            $actual[$uri] = $actionArray['middleware'];
        }
        $this->assertEquals($expected, $actual);
    }

    public function testShouldGetNullMiddlewareWithAuthDisabled()
    {
        putenv('APP_ENABLE_AUTH=false');
        $expected = ['odata.svc/$metadata' => null, 'odata.svc/{section}' => null, 'odata.svc' => null];
        $actual   = [];

        $foo = m::mock(MetadataRouteProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('isAuthEnable')->andReturn(false)->once();
        $foo->boot();

        $allRoutes = Route::getRoutes();

        foreach ($allRoutes as $target => $route) {
            $uri          = $route->uri();
            $actionArray  = $route->getAction();
            $actual[$uri] = $actionArray['middleware'];
        }
        $this->assertEquals($expected, $actual);
    }

    /**
     * @throws \ReflectionException
     */
    public function testReportAuthEnabledWhenAuthEnabledIsTrue()
    {
        putenv('APP_ENABLE_AUTH=true');

        $app = App::make('app');
        $prov = new MetadataRouteProvider($app);

        $reflec = new \ReflectionClass($prov);

        $method = $reflec->getMethod('isAuthEnable');
        $method->setAccessible(true);

        $expected = true;
        $actual = $method->invokeArgs($prov, []);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @throws \ReflectionException
     */
    public function testReportAuthDisabledWhenAuthEnabledIsFalse()
    {
        putenv('APP_ENABLE_AUTH=false');

        $app = App::make('app');
        $prov = new MetadataRouteProvider($app);

        $reflec = new \ReflectionClass($prov);

        $method = $reflec->getMethod('isAuthEnable');
        $method->setAccessible(true);

        $expected = false;
        $actual = $method->invokeArgs($prov, []);

        $this->assertEquals($expected, $actual);
    }
}
