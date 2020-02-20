<?php

namespace AlgoWeb\PODataLaravel\Providers;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;

abstract class MetadataBaseProvider extends ServiceProvider
{

    /**
     * @return bool
     */
    protected function getIsCaching()
    {
        return true === env('APP_METADATA_CACHING', false);
    }

    /**
     * @param bool $isCaching
     * @param bool|null $hasCache
     * @param string $key
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

    /**
     * @return array
     */
    protected function getClassMap()
    {
        $classes = get_declared_classes();
        $autoClass = null;
        foreach ($classes as $class) {
            if (\Illuminate\Support\Str::startsWith($class, 'Composer\\Autoload\\ComposerStaticInit')) {
                $autoClass = $class;
            }
        }

        $classes = $autoClass::$classMap;
        return array_keys($classes);
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
