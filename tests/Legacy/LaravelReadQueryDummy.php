<?php

namespace Tests\AlgoWeb\PODataLaravel\Models;

use AlgoWeb\PODataLaravel\Providers\MetadataProvider;
use AlgoWeb\PODataLaravel\Query\LaravelReadQuery;

class LaravelReadQueryDummy extends LaravelReadQuery
{
    public function setMetadataProvider(MetadataProvider $meta)
    {
        $this->metadataProvider = $meta;
    }

    public function applyFiltering(
        $top,
        $skip,
        $sourceEntityInstance,
        $nullFilter,
        $rawLoad,
        callable $isvalid = null
    ) {
        return parent::applyFiltering($top, $skip, $sourceEntityInstance, $nullFilter, $rawLoad, $isvalid);
    }
}
