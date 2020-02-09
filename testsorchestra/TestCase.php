<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 1/02/20
 * Time: 1:21 PM
 */

namespace AlgoWeb\PODataLaravel\Orchestra\Tests;

use AlgoWeb\PODataLaravel\Kernels\ConsoleKernel;
use AlgoWeb\PODataLaravel\Providers\MetadataProvider;
use Illuminate\Support\Facades\App;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            TestServiceProvider::class,
            \AlgoWeb\PODataLaravel\Providers\MetadataProvider::class,
            \AlgoWeb\PODataLaravel\Providers\MetadataRouteProvider::class,
            \AlgoWeb\PODataLaravel\Providers\QueryProvider::class,
            \AlgoWeb\PODataLaravel\Providers\MetadataControllerProvider::class,
            \Orchestra\Database\ConsoleServiceProvider::class,
            ];
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

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application $app
     * @return void
     * @throws \ReflectionException
     */
    protected function getEnvironmentSetUp($app)
    {
        // Brute-force set app namespace
        $reflec = new \ReflectionClass($app);
        $prop = $reflec->getProperty('namespace');
        $prop->setAccessible(true);
        $prop->setValue($app, "AlgoWeb\\PODataLaravel\\Orchestra\\");

        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    public function setUp()
    {
        parent::setUp();
        $this->loadMigrationsFrom(realpath(__DIR__ . '/database/migrations'));
        App::make(MetadataProvider::class)->boot();
        date_default_timezone_set('UTC');
    }

    protected function assertSeeShim($result, $expected)
    {
        if (method_exists($result, 'assertSee')) {
            $result->assertSee($expected);
        } else {
            $this->assertContains($expected, $result->response->getOriginalContent());
        }
    }
}
