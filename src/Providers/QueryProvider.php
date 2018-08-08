<?php

namespace AlgoWeb\PODataLaravel\Providers;

use AlgoWeb\PODataLaravel\Query\LaravelQuery as LaravelQuery;
use Illuminate\Support\ServiceProvider;

class QueryProvider extends ServiceProvider
{
    /**
     * @return void|null
     */
    public function boot()
    {
        //
    }

    /**
     * @return void
     */
    public function register()
    {
        $this->app->singleton(
            'odataquery',
            function () {
                return new LaravelQuery();
            }
        );
    }
}
