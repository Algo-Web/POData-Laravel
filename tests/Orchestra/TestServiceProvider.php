<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 1/02/20
 * Time: 9:01 PM.
 */
namespace AlgoWeb\PODataLaravel\Orchestra\Tests;

use AlgoWeb\PODataLaravel\Providers\MetadataProvider;
use Illuminate\Database\Migrations\MigrationRepositoryInterface;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema as Schema;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use POData\Providers\Metadata\SimpleMetadataProvider;

class TestServiceProvider extends BaseServiceProvider
{
    protected $defer = false;

    public function register()
    {
    }

    public function boot()
    {
        $this->loadMigrationsFrom(
            __DIR__ . '/database/migrations'
        );
    }

    protected function loadMigrationsFrom($path)
    {
        $migrator            = $this->app->make('migrator');
        $migrationRepository = $migrator->getRepository();
        $migrationRepository->setSource('testbench');
        $migrationRepository->createRepository();
        $migrator->run($path);
    }
}
