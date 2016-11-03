<?php

namespace AlgoWeb\PODataLaravel\Models;

use Mockery;
use PHPUnit_Framework_TestCase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use POData\Providers\Metadata\SimpleMetadataProvider;
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
        $confRepo = \Mockery::mock(\Illuminate\Config\Repository::class)->makePartial();
        $confRepo->shouldReceive('shouldRecompile')->andReturn(false);

        $cacheRepo = \Mockery::mock(\Illuminate\Cache\Repository::class)->makePartial();

        $fileSys = \Mockery::mock(\Illuminate\Filesystem\Filesystem::class)->makePartial();
        $fileSys->shouldReceive('put')->andReturnNull();

        // Lifted straight out of the stock bootstrap/app.php shipped with Laravel
        // and repointed to underlying classes
        $app = new \AlgoWeb\PODataLaravel\Models\TestApplication($fileSys);
        $app['env'] = 'testing';
        $app->instance('config', $confRepo);
        $app->config->set('app.providers', []);
        $app->instance('cache.store', $cacheRepo);

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

        return $app;
    }
}
