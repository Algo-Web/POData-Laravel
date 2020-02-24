<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 24/02/20
 * Time: 12:18 PM
 */

namespace AlgoWeb\PODataLaravel\Orchestra\Tests\Query;

use AlgoWeb\PODataLaravel\Query\LaravelQuery;

class DummyQuery extends LaravelQuery
{
    public function getTouchList()
    {
        return static::$touchList;
    }

    public function inBatch(): bool
    {
        return static::$inBatch;
    }
}
