<?php

namespace AlgoWeb\PODataLaravel\Models;

use AlgoWeb\PODataLaravel\Kernels\ConsoleKernel;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return [\AlgoWeb\PODataLaravel\Providers\MetadataProvider::class,
            \AlgoWeb\PODataLaravel\Providers\MetadataRouteProvider::class,
            \AlgoWeb\PODataLaravel\Providers\QueryProvider::class,
            \AlgoWeb\PODataLaravel\Providers\MetadataControllerProvider::class];
    }

    /**
     * Resolve application Console Kernel implementation.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function resolveApplicationConsoleKernel($app)
    {
        $app->singleton('Illuminate\Contracts\Console\Kernel', ConsoleKernel::class);
    }
}
