<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 22/02/20
 * Time: 4:22 AM
 */

namespace AlgoWeb\PODataLaravel\Orchestra\Tests\Providers;

use AlgoWeb\PODataLaravel\Providers\MetadataBaseProvider;

class DummyMetadataProvider extends MetadataBaseProvider
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

    public function checkClassMap($classMap)
    {
        parent::checkClassMap($classMap);
    }
}