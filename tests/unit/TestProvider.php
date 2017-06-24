<?php

namespace AlgoWeb\PODataLaravel\Providers;

class TestProvider extends MetadataBaseProvider
{
    /**
     * @param $isCaching
     * @param $hasCache
     * @param $key
     * @param $meta
     */
    public function handlePostBoot($isCaching, $hasCache, $key, $meta)
    {
        return parent::handlePostBoot($isCaching, $hasCache, $key, $meta);
    }

    public function register()
    {
    }
}
