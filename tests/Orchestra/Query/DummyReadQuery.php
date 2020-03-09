<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 26/02/20
 * Time: 12:27 PM.
 */
namespace AlgoWeb\PODataLaravel\Orchestra\Tests\Query;

use AlgoWeb\PODataLaravel\Query\LaravelReadQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use POData\Common\InvalidOperationException;
use POData\Providers\Query\QueryResult;
use POData\Providers\Query\QueryType;
use POData\UriProcessor\QueryProcessor\SkipTokenParser\SkipTokenInfo;

class DummyReadQuery extends LaravelReadQuery
{
    public function packageResourceSetResults(
        QueryType $queryType,
        int $skip,
        QueryResult $result,
        $resultSet,
        int $resultCount,
        int $bulkSetCount
    ): void {
        parent::packageResourceSetResults($queryType, $skip, $result, $resultSet, $resultCount, $bulkSetCount);
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

    /**
     * @param  SkipTokenInfo             $skipToken
     * @param  Model|Builder             $sourceEntityInstance
     * @throws InvalidOperationException
     * @return mixed
     */
    public function processSkipToken(SkipTokenInfo $skipToken, $sourceEntityInstance)
    {
        return parent::processSkipToken($skipToken, $sourceEntityInstance);
    }

    /**
     * @param  string[]                  $eagerLoad
     * @throws InvalidOperationException
     * @return string[]
     */
    public function processEagerLoadList(array $eagerLoad = []): array
    {
        return parent::processEagerLoadList($eagerLoad);
    }
}
