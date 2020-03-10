<?php

declare(strict_types=1);


namespace Tests\Northwind\AlgoWeb\PODataLaravel;

use AlgoWeb\PODataLaravel\Tests\Connections\CloneInMemoryPDO;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class TestServiceProvider extends BaseServiceProvider
{
    protected $defer = false;

    public function register()
    {
        require_once(__DIR__ . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'DatabaseSeeder.php');
    }

    /**
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function boot()
    {
        $src = DB::connection('testbench-nw-master')->getPdo();
        $dst = DB::connection('testbench-nw')->getPdo();

        if (!Schema::connection('testbench-nw-master')->hasTable('migrations')) {
            $this->loadMigrationsFrom(
                __DIR__ . '/database/migrations'
            );
            $this->seed();
        }

        CloneInMemoryPDO::clone($src, $dst);
    }

    /**
     * @param  array|string                                               $path
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function loadMigrationsFrom($path)
    {
        $migrator            = $this->app->make('migrator');
        $migrationRepository = $migrator->getRepository();
        $migrationRepository->setSource('testbench-nw-master');
        $migrationRepository->createRepository();
        $migrator->run($path);
    }

    protected function seed()
    {
        Model::unguarded(function () {
            $seeder = $this->getSeeder();
            if (method_exists($seeder, 'run')) {
                $seeder->run();
            } else {
                $seeder->__invoke();
            }
        });
    }

    /**
     * Get a seeder instance from the container.
     *
     * @return \Illuminate\Database\Seeder
     */
    protected function getSeeder()
    {
        $class = $this->app->make('DatabaseSeeder');

        return $class->setContainer($this->app);
    }
}
