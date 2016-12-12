<?php

namespace AlgoWeb\PODataLaravel\Controllers;

class MetadataControllerContainer
{
    private $metadata;

    public function setMetadata($meta)
    {
        $this->metadata = $meta;
    }

    public function getMetadata()
    {
        return $this->metadata;
    }

    public function getMapping($modelName, $verb)
    {
        return $this->metadata[$modelName][$verb];
    }
}
