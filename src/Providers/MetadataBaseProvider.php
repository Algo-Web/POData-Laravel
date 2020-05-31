<?php

declare(strict_types=1);

namespace AlgoWeb\PODataLaravel\Providers;

use AlgoWeb\PODataLaravel\Controllers\MetadataControllerContainer;
use AlgoWeb\PODataLaravel\Models\ClassReflectionHelper;
use AlgoWeb\PODataLaravel\Models\IMetadataRelationshipContainer;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Map;
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
     * @param bool          $isCaching
     * @param bool|null     $hasCache
     * @param string        $key
     * @param mixed         $meta
     */
    protected function handlePostBoot(bool $isCaching, ?bool $hasCache, string $key, $meta): void
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

    /**
     * @return string
     */
    protected function getAppNamespace(): string
    {
        return ClassReflectionHelper::getAppNamespace();
    }
}
