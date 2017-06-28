<?php

namespace AlgoWeb\PODataLaravel\Providers;

use AlgoWeb\PODataLaravel\Models\TestCase as TestCase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;
use Mockery as m;

class MetadataRouteProviderTest extends TestCase
{
    public function testShouldGetAuthApi()
    {
        $hasApiBitz = interface_exists(\Illuminate\Contracts\Auth\Factory::class);
        $expectedMiddleware = $hasApiBitz ? 'auth:api' : 'auth.basic';

        $allRoutes = Route::getRoutes();

        foreach ($allRoutes as $target => $route) {
            $actionArray = $route->getAction();
            $this->assertEquals($expectedMiddleware, $actionArray['middleware']);
        }
    }
}
