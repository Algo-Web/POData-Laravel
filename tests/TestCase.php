<?php

namespace AlgoWeb\PODataLaravel\Models;

use Mockery;
use PHPUnit_Framework_TestCase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use POData\Providers\Metadata\SimpleMetadataProvider;
use AlgoWeb\PODataLaravel\Controllers\MetadataControllerContainer;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamWrapper;
use org\bovigo\vfs\vfsStreamDirectory;

class TestCase extends BaseTestCase
{

    /**
     * Creates the application.
     *
     * Needs to be implemented by subclasses.
     *
     * @return \Symfony\Component\HttpKernel\HttpKernelInterface
     */
    public function createApplication()
    {
        $database = \Mockery::mock(\Illuminate\Database\DatabaseManager::class)->makePartial();
        $database->shouldReceive('table')->withAnyArgs()->andReturn($this->getBuilder());

        $confRepo = \Mockery::mock(\Illuminate\Config\Repository::class)->makePartial();
        $confRepo->shouldReceive('shouldRecompile')->andReturn(false);

        $cacheRepo = \Mockery::mock(\Illuminate\Cache\Repository::class)->makePartial();

        $fileSys = \Mockery::mock(\Illuminate\Filesystem\Filesystem::class)->makePartial();
        $fileSys->shouldReceive('put')->andReturnNull();

        $log = \Mockery::mock(\Illuminate\Log\Writer::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $log->shouldReceive('writeLog')->withAnyArgs()->andReturnNull();

        // Lifted straight out of the stock bootstrap/app.php shipped with Laravel
        // and repointed to underlying classes
        $app = new \AlgoWeb\PODataLaravel\Models\TestApplication($fileSys);
        $app['env'] = 'testing';
        $app->instance('config', $confRepo);
        $app->config->set(
            'app.providers',
            [
                \AlgoWeb\PODataLaravel\Providers\MetadataControllerProvider::class,
                \Illuminate\View\ViewServiceProvider::class,
            ]
        );
        $app->config->set(
            'view.paths',
            [
                realpath(base_path('resources/views'))
            ]
        );
        $app->instance('cache.store', $cacheRepo);
        $app->instance('db', $database);
        $app->instance('log', $log);

        $app->singleton(
            \Illuminate\Contracts\Http\Kernel::class,
            \Illuminate\Foundation\Http\Kernel::class
        );

        $app->singleton(
            \Illuminate\Contracts\Console\Kernel::class,
            \AlgoWeb\PODataLaravel\Kernels\ConsoleKernel::class
        );

        $app->singleton(
            \Illuminate\Contracts\Debug\ExceptionHandler::class,
            \Illuminate\Foundation\Exceptions\Handler::class
        );

        $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
        $kernel->bootstrap();

        $app->singleton(
            'metadata',
            function () {
                return new SimpleMetadataProvider('Data', 'Data');
            }
        );

        $app->singleton('metadataControllers', function ($app) {
            return new MetadataControllerContainer();
        });

        $app->singleton('files', function () {
            return new \Illuminate\Filesystem\Filesystem();
        });

        return $app;
    }

    protected function getBuilder()
    {
        $grammar = new \Illuminate\Database\Query\Grammars\Grammar;
        $processor = \Mockery::mock('Illuminate\Database\Query\Processors\Processor')->makePartial();
        $connect = \Mockery::mock('Illuminate\Database\ConnectionInterface');
        $connect->shouldReceive('getQueryGrammar')->andReturn($grammar);
        $connect->shouldReceive('getPostProcessor')->andReturn($processor);

        return new \Illuminate\Database\Query\Builder(
            $connect,
            $grammar,
            $processor
        );
    }
}
