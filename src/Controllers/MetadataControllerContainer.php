<?php

declare(strict_types=1);

namespace AlgoWeb\PODataLaravel\Controllers;

/**
 * Class MetadataControllerContainer
 * @package AlgoWeb\PODataLaravel\Controllers
 */
class MetadataControllerContainer
{
    /** @var array[] */
    private $metadata;

    /**
     * @param array[] $meta
     */
    public function setMetadata(array $meta): void
    {
        $this->metadata = $meta;
    }

    /**
     * @return array[]
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * @param  string $modelName
     * @param  string $verb
     * @return mixed
     */
    public function getMapping($modelName, $verb)
    {
        return $this->metadata[$modelName][$verb];
    }
}
