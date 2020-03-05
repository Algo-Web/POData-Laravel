<?php

declare(strict_types=1);

namespace AlgoWeb\PODataLaravel\Providers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;

abstract class MetadataBaseProvider extends ServiceProvider
{
    /** @var Application */
    protected $app;

    /**
     * @return bool
     */
    protected function getIsCaching()
    {
        return true === env('APP_METADATA_CACHING', false);
    }

    /**
     * @param bool      $isCaching
     * @param bool|null $hasCache
     * @param string    $key
     * @param $meta
     */
    protected function handlePostBoot(bool $isCaching, $hasCache, string $key, $meta)
    {
        if (!$isCaching) {
            Cache::forget($key);
            return;
        }
        $hasCache = isset($hasCache) ? boolval($hasCache) : false;
        if (!$hasCache) {
            $cacheTime = abs(intval(env('APP_METADATA_CACHE_DURATION', 10)));
            $cacheTime = max($cacheTime, 1);
            Cache::put($key, $meta, $cacheTime);
        }
    }

    protected function getAppNamespace()
    {
        try {
            $startName = App::getNamespace();
        } catch (\Exception $e) {
            $startName = 'App';
        }
        return $startName;
    }
}
