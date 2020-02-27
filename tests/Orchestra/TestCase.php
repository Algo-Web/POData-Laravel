<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 1/02/20
 * Time: 1:21 PM.
 */
namespace AlgoWeb\PODataLaravel\Orchestra\Tests;

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
        $prop   = $reflec->getProperty('namespace');
        $prop->setAccessible(true);
        $prop->setValue($app, 'AlgoWeb\\PODataLaravel\\Orchestra\\');

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

    protected static function resetMetadataProvider($provider)
    {
        $reset = function () {
            self::$isBooted       = false;
            self::$afterExtract   = null;
            self::$afterUnify     = null;
            self::$afterVerify    = null;
            self::$afterImplement = null;
        };
        return call_user_func($reset->bindTo($provider, get_class($provider)));
    }
}
