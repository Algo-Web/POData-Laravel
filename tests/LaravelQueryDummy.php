<?php

namespace AlgoWeb\PODataLaravel\Models;

use AlgoWeb\PODataLaravel\Query\LaravelHookQuery;
use AlgoWeb\PODataLaravel\Query\LaravelQuery;

class LaravelQueryDummy extends LaravelQuery
{
    public function prepareBulkRequestInput($paramList, array $data, array $keyDescriptors = null)
    {
        return parent::prepareBulkRequestInput($paramList, $data, $keyDescriptors);
    }

    public function setModelHook(LaravelHookQuery $hook)
    {
        $this->modelHook = $hook;
    }
}
