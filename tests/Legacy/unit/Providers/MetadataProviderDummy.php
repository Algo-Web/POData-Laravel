<?php

namespace Tests\AlgoWeb\PODataLaravel\Providers;

use AlgoWeb\PODataLaravel\Providers\MetadataProvider;
use Tests\AlgoWeb\PODataLaravel\Controllers\ElectricBoogalooController;
use AlgoWeb\PODataLaravel\Controllers\MetadataControllerContainer;
use Tests\AlgoWeb\PODataLaravel\Controllers\TestController;
use Tests\AlgoWeb\PODataLaravel\Models\TestApplication;
use Tests\AlgoWeb\PODataLaravel\Models\TestCase as TestCase;
use Tests\AlgoWeb\PODataLaravel\Models\TestModel;
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
