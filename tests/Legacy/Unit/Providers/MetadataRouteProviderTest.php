<?php declare(strict_types=1);

namespace Tests\Legacy\AlgoWeb\PODataLaravel\Unit\Providers;

use AlgoWeb\PODataLaravel\Providers\MetadataRouteProvider;
use Illuminate\Support\Facades\Route;
use Mockery as m;
use Tests\Legacy\AlgoWeb\PODataLaravel\TestCase as TestCase;

class MetadataRouteProviderTest extends TestCase
{
    public function testShouldGetAuthApiWithAuthEnabled()
    {
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
        $expected = ['odata.svc/$metadata' => null, 'odata.svc/{section}' => null, 'odata.svc' => null];
        $actual   = [];

        $foo = m::mock(MetadataRouteProvider::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('isAuthDisable')->andReturn(true)->once();
        $foo->boot();

        $allRoutes = Route::getRoutes();

        foreach ($allRoutes as $target => $route) {
            $uri          = $route->uri();
            $actionArray  = $route->getAction();
            $actual[$uri] = $actionArray['middleware'];
        }
        $this->assertEquals($expected, $actual);
    }
}
