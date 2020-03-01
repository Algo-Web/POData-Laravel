<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 24/02/20
 * Time: 2:51 PM.
 */
namespace AlgoWeb\PODataLaravel\Orchestra\Tests\Unit\Query;

use AlgoWeb\PODataLaravel\Orchestra\Tests\Query\DummyQuery;
use AlgoWeb\PODataLaravel\Orchestra\Tests\TestCase;

class LaravelQueryTest extends TestCase
{
    public function testNotInBatchOnCreate()
    {
        $foo = new DummyQuery();
        $this->assertFalse($foo->inBatch());
    }

    public function testStartAndCommitTransactionEndsUpNotInBatch()
    {
        $foo = new DummyQuery();
        $foo->startTransaction(true);
        $this->assertTrue($foo->inBatch());
        $foo->commitTransaction();
        $this->assertFalse($foo->inBatch());
    }

    public function testStartAndRollbackTransactionEndsUpNotInBatch()
    {
        $foo = new DummyQuery();
        $foo->startTransaction(true);
        $this->assertTrue($foo->inBatch());
        $foo->rollBackTransaction();
        $this->assertFalse($foo->inBatch());
    }
}
