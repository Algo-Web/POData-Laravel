<?php

namespace AlgoWeb\PODataLaravel\Providers;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class MetadataRouteProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        self::setupRoute();
    }

    private static function setupRoute()
    {
        $auth_middleware = self::getAuthMiddleware();
        $controllerMethod = 'AlgoWeb\PODataLaravel\Controllers\ODataController@index';

        Route::any('odata.svc/{section}', ['uses' => $controllerMethod, 'middleware' => $auth_middleware])
            ->where(['section' => '.*']);
        Route::any('odata.svc', ['uses' => $controllerMethod, 'middleware' => $auth_middleware]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
    }

    private static function getAuthMiddleware()
    {
        $auth_middleware = 'auth.basic';

        if (interface_exists(\Illuminate\Contracts\Auth\Factory::class)) {
            $manager = App::make(\Illuminate\Contracts\Auth\Factory::class);
            if ($manager->guard('api')) {
                $auth_middleware = 'auth:api';
            }
        }

        return $auth_middleware;
    }
}
