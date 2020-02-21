<?php


namespace Tests\System\AlgoWeb\PODataLaravel;

use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    public static function setUpBeforeClass() : void
    {
        putenv('APP_DISABLE_AUTH=true');
    }

    public static function tearDownAfterClass() : void
    {
        putenv('APP_DISABLE_AUTH=false');
    }

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
        $prop->setValue($app, 'Tests\\System\\AlgoWeb\\PODataLaravel');
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


    public function getServiceDocument($version, $jsonLevel = null)
    {
        switch ($version) {
            case 1:
                $version = '1.0;';
                break;
            case 2:
                $version = '2.0;';
                break;
            case 3:
                $version = '3.0;';
                break;
            case 4:
                $this->markTestSkipped('Odata Version 4 not implomented yet');
                $version = '4.0;';
                break;
            default:
                $this->fail('Requested a version not between 1 and 4');
        }
        $headers = [ 'DataServiceVersion' => $version, 'MaxDataServiceVersion' => $version];
        if ($jsonLevel !== null) {
            $headers['HTTP_ACCEPT'] = 'application/json;odata=' . $jsonLevel;
        }
        $response = $this->call(
            'GET',
            '/odata.svc/$metadata',
            [],
            [],
            [],
            $headers
        );
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($version, $response->headers->get('DataServiceVersion'));
        return $response;
    }
    public function getMetadataDocument($version)
    {
        switch ($version) {
            case 1:
                $version = '1.0;';
                break;
            case 2:
                $version = '2.0;';
                break;
            case 3:
                $version = '3.0;';
                break;
            case 4:
                $this->markTestSkipped('Odata Version 4 not implomented yet');
                $version = '4.0;';
                break;
            default:
                $this->fail('Requested a version not between 1 and 4');
        }
        $response = $this->call(
            'GET',
            '/odata.svc/$metadata',
            [],
            [],
            [],
            [ 'DataServiceVersion' => $version, 'MaxDataServiceVersion' => $version]
        );
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($version, $response->headers->get('DataServiceVersion'));
        return $response;
    }
}
