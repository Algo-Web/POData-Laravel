<?php

namespace AlgoWeb\PODataLaravel\Controllers;

class MetadataControllerContainer
{
    private $metadata;

    /**
     * @param array $meta
     */
    public function setMetadata(array $meta)
    {
        $this->metadata = $meta;
    }

    /**
     * @return array
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * @param $modelName
     * @param $verb
     * @return array
     */
    public function getMapping($modelName, $verb)
    {
        return $this->metadata[$modelName][$verb];
    }
}
