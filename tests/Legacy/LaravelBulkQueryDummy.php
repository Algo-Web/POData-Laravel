<?php

namespace Tests\AlgoWeb\PODataLaravel\Models;

use AlgoWeb\PODataLaravel\Controllers\MetadataControllerContainer;
use AlgoWeb\PODataLaravel\Interfaces\AuthInterface;
use AlgoWeb\PODataLaravel\Query\LaravelBulkQuery;
use AlgoWeb\PODataLaravel\Query\LaravelHookQuery;
use AlgoWeb\PODataLaravel\Query\LaravelQuery;
use Illuminate\Http\JsonResponse;

class LaravelBulkQueryDummy extends LaravelBulkQuery
{
    public function prepareBulkRequestInput($paramList, array $data, array $keyDescriptors = null)
    {
        return parent::prepareBulkRequestInput($paramList, $data, $keyDescriptors);
    }

    public function setQuery(LaravelQuery $query)
    {
        $this->query = $query;
    }

    public function setControllerContainer(MetadataControllerContainer $container)
    {
        $this->controllerContainer = $container;
    }

    public function createUpdateDeleteProcessOutput(JsonResponse $result)
    {
        return parent::createUpdateDeleteProcessOutput($result);
    }
}
