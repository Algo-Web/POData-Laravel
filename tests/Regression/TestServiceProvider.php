<?php


namespace Tests\Regression\AlgoWeb\PODataLaravel;


use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class TestServiceProvider extends BaseServiceProvider
{
    protected $defer = false;

    public function register()
    {
    }

    public function boot()
    {
        $this->loadMigrationsFrom(
            __DIR__ . DIRECTORY_SEPARATOR . config('testRegressionName') .'/database/migrations'
        );
    }

    protected function loadMigrationsFrom($path)
    {
        $migrator = $this->app->make('migrator');
        $migrationRepository = $migrator->getRepository();
        $migrationRepository->setSource('testbench');
        $migrationRepository->createRepository();
        $migrator->run($path);
    }
}
