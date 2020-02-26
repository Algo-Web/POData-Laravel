<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 26/02/20
 * Time: 12:27 PM
 */

namespace AlgoWeb\PODataLaravel\Orchestra\Tests\Query;

use AlgoWeb\PODataLaravel\Query\LaravelReadQuery;
use POData\Providers\Query\QueryResult;
use POData\Providers\Query\QueryType;

class DummyReadQuery extends LaravelReadQuery
{
    public function packageResourceSetResults(
        QueryType $queryType,
        int $skip,
        QueryResult $result,
        $resultSet,
        int $resultCount,
        int $bulkSetCount
    ) {
        return parent::packageResourceSetResults($queryType, $skip, $result, $resultSet, $resultCount, $bulkSetCount);
    }

    public function applyBasicFiltering(
        $sourceEntityInstance,
        bool $nullFilter
    ) {
        return parent::applyFiltering($sourceEntityInstance, $nullFilter, []);
    }

    public function applyFilterFiltering(
        $sourceEntityInstance,
        bool $nullFilter,
        callable $isvalid = null
    ) {
        return parent::applyFiltering($sourceEntityInstance, $nullFilter, [], PHP_INT_MAX, 0, $isvalid);
    }
}
