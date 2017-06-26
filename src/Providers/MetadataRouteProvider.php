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

        Route::any(
            'odata.svc/{section}',
            ['uses' => 'AlgoWeb\PODataLaravel\Controllers\ODataController@index', 'middleware' => $auth_middleware]
        )
            ->where(['section' => '.*']);
        Route::any(
            'odata.svc',
            ['uses' => 'AlgoWeb\PODataLaravel\Controllers\ODataController@index', 'middleware' => $auth_middleware]
        );
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    private static function getAuthMiddleware()
    {
        $auth_middleware = 'auth.basic';

        try {
            if (class_exists(\Illuminate\Contracts\Auth\Factory::class)) {
                $manager = App::make(\Illuminate\Contracts\Auth\Factory::class);
                if ($manager->guard('api')) {
                    $auth_middleware = 'auth:api';
                }
            }
        } catch (\Exception $e) {

        }

        return $auth_middleware;
    }
}
