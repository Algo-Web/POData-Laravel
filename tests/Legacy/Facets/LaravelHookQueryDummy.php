<?php

namespace Tests\Legacy\Facets\AlgoWeb\PODataLaravel\Models;

use AlgoWeb\PODataLaravel\Providers\MetadataProvider;
use AlgoWeb\PODataLaravel\Query\LaravelHookQuery;

class LaravelHookQueryDummy extends LaravelHookQuery
{
    public function setMetadataProvider(MetadataProvider $meta)
    {
        $this->metadataProvider = $meta;
    }
}
