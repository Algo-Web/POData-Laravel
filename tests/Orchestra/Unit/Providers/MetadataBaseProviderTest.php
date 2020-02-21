<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 22/02/20
 * Time: 4:23 AM
 */

namespace AlgoWeb\PODataLaravel\Orchestra\Tests\Unit\Providers;

use AlgoWeb\PODataLaravel\Orchestra\Tests\Providers\DummyMetadataProvider;
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

        $foo = new DummyMetadataProvider($app);

        $foo->handlePostBoot(true, null, 'key', null);
    }

    public function testExplicitZeroCacheDurationMeansOne()
    {
        putenv('APP_METADATA_CACHE_DURATION=0');

        Cache::shouldReceive('put')->withArgs(['key', null, 1])->once();

        $app = m::mock(Application::class);

        $foo = new DummyMetadataProvider($app);

        $foo->handlePostBoot(true, null, 'key', null);
    }

    public function testNotExistInClassMap()
    {
        $app = m::mock(Application::class);

        $foo = new DummyMetadataProvider($app);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('AlgoWeb\PODataLaravel\Providers\MetadataBaseProvider was not found in autoload class map, this usually indicates you need to dump an optimised autoloader (`composer dump-autoload -o`)');

        $foo->checkClassMap([]);
    }
}
