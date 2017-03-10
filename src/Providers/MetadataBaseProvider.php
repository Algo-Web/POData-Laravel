<?php

namespace AlgoWeb\PODataLaravel\Providers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;

abstract class MetadataBaseProvider extends ServiceProvider
{

    /**
     * @return mixed
     */
    protected function getIsCaching()
    {
        return true === env('APP_METADATA_CACHING', false);
    }

    /**
     * @param $isCaching
     * @param $hasCache
     * @param $key
     * @param $meta
     */
    protected function handlePostBoot($isCaching, $hasCache, $key, $meta)
    {
        if ($isCaching) {
            assert(isset($hasCache));
            if (!$hasCache) {
                $cacheTime = env('APP_METADATA_CACHE_DURATION', null);
                $cacheTime = !is_numeric($cacheTime) ? 10 : abs($cacheTime);
                Cache::put($key, $meta, $cacheTime);
            }
        } else {
            Cache::forget($key);
        }
    }

    /**
     * @param $classMap
     * @return array
     */
    protected function getClassMap()
    {
        $classes = get_declared_classes();
        $AutoClass = null;
        foreach ($classes as $class) {
            if (\Illuminate\Support\Str::startsWith($class, "Composer\\Autoload\\ComposerStaticInit")) {
                $AutoClass = $class;
            }
        }

        $Classes = $AutoClass::$classMap;
        return array_keys($Classes);
    }
}
