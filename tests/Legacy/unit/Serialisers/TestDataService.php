<?php

namespace AlgoWeb\PODataLaravel\Serialisers;

use POData\SimpleDataService;
use POData\UriProcessor\UriProcessor;

class TestDataService extends SimpleDataService
{
    public function handleRequest()
    {
        $this->createProviders();
        $this->getHost()->validateQueryParameters();

        $uriProcessor = UriProcessor::process($this);
        return $uriProcessor;
        // we don't want to serialise the result here - we're intending to feed result description to serialiser(s)
        // under test
    }
}
