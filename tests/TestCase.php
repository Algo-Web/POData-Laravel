<?php

namespace AlgoWeb\PODataLaravel\Models;

use AlgoWeb\PODataLaravel\Kernels\ConsoleKernel;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as BaseTestCase;

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
            $conn = \Mockery::mock('Illuminate\Database\ConnectionInterface')->makePartial();
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

    public function setUp()
    {
        parent::setUp();
        date_default_timezone_set('UTC');
        $this->origFacade['schema'] = Schema::getFacadeRoot();
        $builder = $this->getBuilder();
        $database = $this->getDatabase($builder);
        App::instance('db', $database);



        //Schema::swap($builder);
    }

    public function tearDown()
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
