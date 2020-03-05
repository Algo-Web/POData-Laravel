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

    public function handlePostBoot(bool $isCaching, $hasCache, string $key, $meta)
    {
        return parent::handlePostBoot($isCaching, $hasCache, $key, $meta);
    }
}
