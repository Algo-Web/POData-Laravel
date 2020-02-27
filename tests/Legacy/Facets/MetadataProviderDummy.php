<?php declare(strict_types=1);

namespace Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models;

use AlgoWeb\PODataLaravel\Providers\MetadataProvider;

class MetadataProviderDummy extends MetadataProvider
{
    protected $candidates = null;

    public function setCandidateModels(array $cand)
    {
        $this->candidates = $cand;
    }

    public function getCandidateModels()
    {
        if (null !== $this->candidates) {
            return $this->candidates;
        }
        return parent::getCandidateModels();
    }
}
