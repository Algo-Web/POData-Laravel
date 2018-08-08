<?php

namespace AlgoWeb\PODataLaravel\Providers;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class MetadataRouteProvider extends ServiceProvider
{
    /**
     * @return null
     */
    public function boot()
    {
        $this->setupRoute();
    }

    /**
     * @return void
     */
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
     * @return void
     */
    public function register()
    {
    }

    /**
     * @return string
     */
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
        return true === env('APP_DISABLE_AUTH', null);
    }
}
