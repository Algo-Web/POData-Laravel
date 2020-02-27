<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 23/02/20
 * Time: 2:54 PM.
 */
namespace AlgoWeb\PODataLaravel\Orchestra\Tests\Providers;

use AlgoWeb\PODataLaravel\Providers\MetadataProvider;

class DummyMetadataProvider extends MetadataProvider
{
    /** @var bool|null */
    protected $caching = null;

    public function isBooted() : bool
    {
        return static::$isBooted;
    }

    public function getIsCaching()
    {
        if (null !== $this->caching) {
            return $this->caching;
        }
        return parent::getIsCaching();
    }

    public function setIsCaching($caching)
    {
        $this->caching = (null === $caching) ? null : boolval($caching);
    }
}
