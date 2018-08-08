<?php

namespace AlgoWeb\PODataLaravel\Controllers;

class MetadataControllerContainer
{
    private $metadata;

    /**
     * @param array|array[] $meta
     *
     * @return void
     */
    public function setMetadata(array $meta)
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
     * @param string $modelName
     * @param string $verb
     *
     * @return array[]
     */
    public function getMapping($modelName, $verb)
    {
        return $this->metadata[$modelName][$verb];
    }
}
