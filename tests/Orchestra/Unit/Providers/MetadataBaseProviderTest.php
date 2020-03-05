<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 22/02/20
 * Time: 4:23 AM.
 */
namespace AlgoWeb\PODataLaravel\Orchestra\Tests\Unit\Providers;

use AlgoWeb\PODataLaravel\Orchestra\Tests\Providers\DummyMetadataBaseProvider;
use AlgoWeb\PODataLaravel\Orchestra\Tests\TestCase;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Cache;
use Mockery as m;

class MetadataBaseProviderTest extends TestCase
{
    public function testNoCacheMeansPutCall()
    {
        Cache::shouldReceive('put')->withAnyArgs()->once();

        $app = m::mock(Application::class);

        $foo = new DummyMetadataBaseProvider($app);

        $foo->handlePostBoot(true, null, 'key', null);
    }

    public function testExplicitZeroCacheDurationMeansOne()
    {
        putenv('APP_METADATA_CACHE_DURATION=0');

        Cache::shouldReceive('put')->withArgs(['key', null, 1])->once();

        $app = m::mock(Application::class);

        $foo = new DummyMetadataBaseProvider($app);

        $foo->handlePostBoot(true, null, 'key', null);
    }
}
