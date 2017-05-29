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
        $valueArray = [];

        Route::any(
            'odata.svc/{section}',
            [ 'uses' => 'AlgoWeb\PODataLaravel\Controllers\ODataController@index', 'middleware' => 'auth.basic']
        )
            ->where(['section' => '.*']);
        Route::any(
            'odata.svc',
            [ 'uses' => 'AlgoWeb\PODataLaravel\Controllers\ODataController@index', 'middleware' => 'auth.basic']
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
