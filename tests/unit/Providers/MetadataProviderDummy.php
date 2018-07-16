<?php

namespace AlgoWeb\PODataLaravel\Providers;

use AlgoWeb\PODataLaravel\Controllers\ElectricBoogalooController;
use AlgoWeb\PODataLaravel\Controllers\MetadataControllerContainer;
use AlgoWeb\PODataLaravel\Controllers\TestController;
use AlgoWeb\PODataLaravel\Models\TestApplication;
use AlgoWeb\PODataLaravel\Models\TestCase as TestCase;
use AlgoWeb\PODataLaravel\Models\TestModel;
use AlgoWeb\PODataLaravel\Providers\MetadataControllerProvider as Provider;
use ErrorException;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Mockery as m;

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
