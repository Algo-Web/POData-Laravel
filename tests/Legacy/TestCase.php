<?php declare(strict_types=1);

namespace Tests\Legacy\AlgoWeb\PODataLaravel;

use AlgoWeb\PODataLaravel\Serialisers\ModelSerialiser;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Kernels\ConsoleKernel;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestModel;

class TestCase extends BaseTestCase
{
    protected $origFacade = [];
    
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
     * @param  \Illuminate\Foundation\Application $app
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
     */
    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    protected function getBuilder(ConnectionInterface $conn = null, Processor $proc = null)
    {
        $grammar = new \Illuminate\Database\Query\Grammars\Grammar;
        if (null !== $proc) {
            $processor = $proc;
        } else {
            $processor = \Mockery::mock(Processor::class)->makePartial();
        }
        if (null != $conn) {
            $connect = $conn;
        } else {
            $connect = \Mockery::mock('Illuminate\Database\ConnectionInterface')->makePartial();
            $connect->shouldReceive('getQueryGrammar')->andReturn($grammar);
            $connect->shouldReceive('getPostProcessor')->andReturn($processor);
        }
        return new \Illuminate\Database\Query\Builder(
            $connect,
            $grammar,
            $processor
        );
    }

    protected function getDatabase(Builder $builder = null, ConnectionInterface $conn = null)
    {
        if (null === $conn) {
            $schema = \Mockery::mock(\Illuminate\Database\Schema\Builder::class);
            $conn   = \Mockery::mock('Illuminate\Database\ConnectionInterface')->makePartial();
            $conn->shouldReceive('getSchemaBuilder')->andReturn($schema);
        }
        if (null === $builder) {
            $builder = $this->getBuilder($conn);
        }

        $database = \Mockery::mock(\Illuminate\Database\DatabaseManager::class)->makePartial();
        $database->shouldReceive('table')->withAnyArgs()->andReturn($builder);
        $database->shouldReceive('connection')->andReturn($conn);

        return $database;
    }

    public function setUp() : void
    {
        parent::setUp();
        date_default_timezone_set('UTC');
        $this->origFacade['schema'] = Schema::getFacadeRoot();
        $builder                    = $this->getBuilder();
        $database                   = $this->getDatabase($builder);
        App::instance('db', $database);
        // Clear any residual metadata bitz from previous runs
        $foo = new TestModel();
        self::resetModel($foo);

        $foo = new ModelSerialiser();
        self::resetModelSerialiser($foo);
        putenv('APP_METADATA_CACHE_DURATION=10');

        //Schema::swap($builder);
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

    protected static function resetModelSerialiser($serialiser)
    {
        $reset = function () {
            self::$mutatorCache  = [];
            self::$metadataCache = [];
        };
        return call_user_func($reset->bindTo($serialiser, get_class($serialiser)));
    }

    protected static function resetModel($model)
    {
        $reset = function () {
            self::$tableData            = [];
            self::$tableColumnsDoctrine = [];
            self::$tableColumns         = [];
        };
        return call_user_func($reset->bindTo($model, get_class($model)));
    }

    public function tearDown() : void
    {
        //Schema::swap($this->origFacade['schema']);
        parent::tearDown();
    }

    protected function getPackageAliases($app)
    {
        return [
            'Schema' => \AlgoWeb\PODataLaravel\Facades\Schema::class
        ];
    }
}
