<?php

declare(strict_types=1);

namespace Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Providers;

use AlgoWeb\PODataLaravel\Providers\MetadataBaseProvider;

class TestProvider extends MetadataBaseProvider
{
    /**
     * @param bool $isCaching
     * @param $hasCache
     * @param string $key
     * @param $meta
     */
    public function handlePostBoot(bool $isCaching, ?bool $hasCache, string $key, $meta): void
    {
        parent::handlePostBoot($isCaching, $hasCache, $key, $meta);
    }

    public function register()
    {
    }
}
