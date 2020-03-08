<?php

declare(strict_types=1);

namespace Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Providers;

use AlgoWeb\PODataLaravel\Providers\MetadataProvider;

class MetadataProviderDummy extends MetadataProvider
{
    public function getCandidateModels(): array
    {
        return parent::getCandidateModels();
    }

    public function getAppNamespace(): string
    {
        return parent::getAppNamespace();
    }
}
