<?php

namespace AlgoWeb\PODataLaravel\Models;

use AlgoWeb\PODataLaravel\Kernels\ConsoleKernel;
use Illuminate\Database\ConnectionInterface;
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

    protected function getBuilder(ConnectionInterface $conn = null)
    {
        $grammar = new \Illuminate\Database\Query\Grammars\Grammar;
        $processor = \Mockery::mock('Illuminate\Database\Query\Processors\Processor')->makePartial();
        if (null != $conn) {
            $connect = $conn;
        } else {
            $connect = \Mockery::mock('Illuminate\Database\ConnectionInterface');
            $connect->shouldReceive('getQueryGrammar')->andReturn($grammar);
            $connect->shouldReceive('getPostProcessor')->andReturn($processor);
        }
        return new \Illuminate\Database\Query\Builder(
            $connect,
            $grammar,
            $processor
        );
    }
}
