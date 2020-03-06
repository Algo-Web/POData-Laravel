<?php

declare(strict_types=1);

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
    public function boot(): void
    {
        $this->setupRoute();
    }

    private function setupRoute(): void
    {
        $authMiddleware   = $this->getAuthMiddleware();
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
    public function register(): void
    {
    }

    private function getAuthMiddleware(): ?string
    {
        $disable = $this->isAuthDisable();
        if ($disable) {
            return null;
        }

        $authMiddleware = 'auth.basic';

        if (interface_exists(\Illuminate\Contracts\Auth\Factory::class)) {
            /** @var \Illuminate\Contracts\Auth\Factory $manager */
            $manager        = App::make(\Illuminate\Contracts\Auth\Factory::class);
            $authMiddleware = $manager->guard('api') ? 'auth:api' : $authMiddleware;
        }

        return $authMiddleware;
    }

    /**
     * @return bool
     */
    protected function isAuthDisable(): bool
    {
        return true === boolval(env('APP_DISABLE_AUTH', null));
    }
}
