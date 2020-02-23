<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 23/02/20
 * Time: 2:54 PM
 */

namespace AlgoWeb\PODataLaravel\Orchestra\Tests\Providers;

use AlgoWeb\PODataLaravel\Providers\MetadataProvider;

class DummyMetadataProvider extends MetadataProvider
{
    public function isBooted() : bool
    {
        return static::$isBooted;
    }
}
