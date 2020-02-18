<?php

namespace Tests\Regression\AlgoWeb\PODataLaravel;

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
            /*\Orchestra\Database\ConsoleServiceProvider::class,*/];
    }


    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application $app
     * @throws \ReflectionException
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // Brute-force set app namespace
        $reflec = new \ReflectionClass($app);
        $prop = $reflec->getProperty('namespace');
        $prop->setAccessible(true);
        $regressionName = explode('\\', get_class($this))[4];
        $prop->setValue($app, __NAMESPACE__ . '\\' . $regressionName);
        $app['config']->set('testRegressionName', $regressionName);
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    public function setUp() : void
    {
        parent::setUp();
        //$this->loadMigrationsFrom(realpath(__DIR__ . '/database/migrations'));
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
