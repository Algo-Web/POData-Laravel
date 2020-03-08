<?php

declare(strict_types=1);


namespace Tests\Regression\AlgoWeb\PODataLaravel;

use AlgoWeb\PODataLaravel\Tests\Connections\CloneInMemoryPDO;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class TestServiceProvider extends BaseServiceProvider
{
    protected $defer = false;

    public function register()
    {
    }

    /**
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function boot()
    {
        $this->loadMigrationsFrom(
            __DIR__ . DIRECTORY_SEPARATOR . config('testRegressionName') .'/database/migrations'
        );
    }

    protected function loadMigrationsFrom($path)
    {
        $src = DB::connection('testbench-master')->getPdo();
        $dst = DB::connection('testbench')->getPdo();

        if (!Schema::connection('testbench-master')->hasTable('migrations')) {
            $migrator = $this->app->make('migrator');
            $migrationRepository = $migrator->getRepository();
            $migrationRepository->setSource('testbench-master');
            $migrationRepository->createRepository();
            $migrator->run($path);
        }

        CloneInMemoryPDO::clone($src, $dst);
    }
}
