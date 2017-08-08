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
        $this->setupRoute();
    }

    private function setupRoute()
    {
        $authMiddleware = $this->getAuthMiddleware();
        $controllerMethod = 'AlgoWeb\PODataLaravel\Controllers\ODataController@index';

        Route::get('odata.svc/$metadata', ['uses' => $controllerMethod, 'middleware' => null]);

        Route::any('odata.svc/{section}', ['uses' => $controllerMethod, 'middleware' => $authMiddleware])
            ->where(['section' => '.*']);
        Route::any('odata.svc', ['uses' => $controllerMethod, 'middleware' => null]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
    }

    private function getAuthMiddleware()
    {
        $disable = $this->isAuthDisable();
        if ($disable) {
            return null;
        }

        $authMiddleware = 'auth.basic';

        if (interface_exists(\Illuminate\Contracts\Auth\Factory::class)) {
            $manager = App::make(\Illuminate\Contracts\Auth\Factory::class);
            $authMiddleware = $manager->guard('api') ? 'auth:api' : $authMiddleware;
        }

        return $authMiddleware;
    }

    /**
     * @return bool
     */
    protected function isAuthDisable()
    {
        return true === config('APP_DISABLE_AUTH', null);
    }
}
