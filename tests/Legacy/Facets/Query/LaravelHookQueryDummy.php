<?php

declare(strict_types=1);

namespace Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Query;

use AlgoWeb\PODataLaravel\Providers\MetadataProvider;
use AlgoWeb\PODataLaravel\Query\LaravelHookQuery;

class LaravelHookQueryDummy extends LaravelHookQuery
{
    public function setMetadataProvider(MetadataProvider $meta)
    {
        $this->metadataProvider = $meta;
    }
}
