<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 22/02/20
 * Time: 4:22 AM.
 */
namespace AlgoWeb\PODataLaravel\Orchestra\Tests\Providers;

use AlgoWeb\PODataLaravel\Providers\MetadataBaseProvider;

class DummyMetadataBaseProvider extends MetadataBaseProvider
{
    public function register()
    {
    }

    public function boot()
    {
    }

    public function handlePostBoot(bool $isCaching, ?bool $hasCache, string $key, $meta): void
    {
        parent::handlePostBoot($isCaching, $hasCache, $key, $meta);
    }
}
