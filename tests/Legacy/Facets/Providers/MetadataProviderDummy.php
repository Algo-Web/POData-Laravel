<?php

namespace Tests\Legacy\Facets\AlgoWeb\PODataLaravel\Providers;

use AlgoWeb\PODataLaravel\Providers\MetadataProvider;

class MetadataProviderDummy extends MetadataProvider
{
    public function getCandidateModels()
    {
        return parent::getCandidateModels();
    }

    public function getAppNamespace()
    {
        return parent::getAppNamespace();
    }
}
