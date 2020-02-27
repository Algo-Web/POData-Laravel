<?php declare(strict_types=1);

namespace AlgoWeb\PODataLaravel\Providers;

use AlgoWeb\PODataLaravel\Query\LaravelQuery as LaravelQuery;
use Illuminate\Support\ServiceProvider;

class QueryProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.  Post-boot.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.  Boot-time only.
     *
     * @return void
     */
    public function register()
    {
        $this->app->/* @scrutinizer ignore-call */singleton(
            'odataquery',
            function () {
                return new LaravelQuery();
            }
        );
    }
}
