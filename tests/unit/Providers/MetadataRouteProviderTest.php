<?php

namespace AlgoWeb\PODataLaravel\Providers;

use AlgoWeb\PODataLaravel\Models\TestCase as TestCase;
use Illuminate\Config\Repository;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;
use Mockery as m;

class MetadataRouteProviderTest extends TestCase
{
    public function testShouldGetAuthApiWithAuthEnabled()
    {
        $hasApiBitz = interface_exists(\Illuminate\Contracts\Auth\Factory::class);
        $expectedMiddleware = $hasApiBitz ? 'auth:api' : 'auth.basic';
        $expected = ['odata.svc/$metadata' => null, 'odata.svc/{section}' => $expectedMiddleware, 'odata.svc' => null];
        $actual = [];

        $allRoutes = Route::getRoutes();

        foreach ($allRoutes as $target => $route) {
            $uri = $route->uri();
            $actionArray = $route->getAction();
            $actual[$uri] = $actionArray['middleware'];
        }
        $this->assertEquals($expected, $actual);
    }

    public function testShouldGetNullMiddlewareWithAuthDisabled()
    {
        $expected = ['odata.svc/$metadata' => null, 'odata.svc/{section}' => null, 'odata.svc' => null];
        $actual = [];

        $foo = m::mock(MetadataRouteProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('isAuthDisable')->andReturn(true)->once();
        $foo->boot();

        $allRoutes = Route::getRoutes();

        foreach ($allRoutes as $target => $route) {
            $uri = $route->uri();
            $actionArray = $route->getAction();
            $actual[$uri] = $actionArray['middleware'];
        }
        $this->assertEquals($expected, $actual);
    }
}
