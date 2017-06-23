<?php

namespace AlgoWeb\PODataLaravel\Providers;

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
        Route::any(
            'odata.svc/{section}',
            [ 'uses' => 'AlgoWeb\PODataLaravel\Controllers\ODataController@index', 'middleware' => 'auth:api']
        )
            ->where(['section' => '.*']);
        Route::any(
            'odata.svc',
            [ 'uses' => 'AlgoWeb\PODataLaravel\Controllers\ODataController@index', 'middleware' => 'auth:api']
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
}
