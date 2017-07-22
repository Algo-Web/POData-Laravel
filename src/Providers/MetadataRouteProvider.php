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
        $authMiddleware = self::getAuthMiddleware();
        $controllerMethod = 'AlgoWeb\PODataLaravel\Controllers\ODataController@index';

        Route::any('odata.svc/{section}', ['uses' => $controllerMethod, 'middleware' => $authMiddleware])
            ->where(['section' => '.*']);
        Route::any('odata.svc', ['uses' => $controllerMethod, 'middleware' => $authMiddleware]);
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
        $authMiddleware = 'auth.basic';

        if (interface_exists(\Illuminate\Contracts\Auth\Factory::class)) {
            $manager = App::make(\Illuminate\Contracts\Auth\Factory::class);
            $authMiddleware = $manager->guard('api') ? 'auth:api' : $authMiddleware;
        }

        return $authMiddleware;
    }
}
