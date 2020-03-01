<?php

declare(strict_types=1);

namespace Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Query;

use AlgoWeb\PODataLaravel\Providers\MetadataProvider;
use AlgoWeb\PODataLaravel\Query\LaravelReadQuery;

class LaravelReadQueryDummy extends LaravelReadQuery
{
    public function setMetadataProvider(MetadataProvider $meta)
    {
        $this->metadataProvider = $meta;
    }

    public function applyFiltering(
        $sourceEntityInstance,
        bool $nullFilter,
        array $rawLoad = [],
        int $top = 0,
        int $skip = 0,
        callable $isvalid = null
    ) {
        return parent::applyFiltering($sourceEntityInstance, $nullFilter, $rawLoad, $top, $skip, $isvalid);
    }
}
