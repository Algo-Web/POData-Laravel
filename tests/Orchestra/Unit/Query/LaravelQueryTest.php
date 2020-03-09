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
use AlgoWeb\PODataLaravel\Query\LaravelBulkQuery;
use AlgoWeb\PODataLaravel\Query\LaravelQuery;
use AlgoWeb\PODataLaravel\Query\LaravelWriteQuery;
use Mockery as m;
use POData\Providers\Metadata\ResourceSet;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\KeyDescriptor;

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

    public function testDefaultBulkUpdateShouldBeFalse()
    {
        $bulk = m::mock(LaravelBulkQuery::class);
        $bulk->shouldReceive('updateBulkResource')
            ->with(m::any(), m::any(), m::any(), m::any(), false)->andReturnNull();

        $foo = m::mock(LaravelQuery::class)->makePartial();
        $foo->shouldReceive('getBulk')->andReturn($bulk);

        $rSet = m::mock(ResourceSet::class);
        $sourceEntityInstance = null;
        $keyDesc = [];
        $data = [];

        $result = $foo->updateBulkResource($rSet, $sourceEntityInstance, $keyDesc, $data);
        $this->assertNull($result);
    }

    public function testDefaultUpdateShouldBeFalse()
    {
        $bulk = m::mock(LaravelWriteQuery::class);
        $bulk->shouldReceive('updateResource')
            ->with(m::any(), m::any(), m::any(), m::any(), false)->andReturnNull();

        $foo = m::mock(LaravelQuery::class)->makePartial();
        $foo->shouldReceive('getWriter')->andReturn($bulk);

        $rSet = m::mock(ResourceSet::class);
        $sourceEntityInstance = null;
        $keyDesc = m::mock(KeyDescriptor::class);
        $data = [];

        $result = $foo->updateResource($rSet, $sourceEntityInstance, $keyDesc, $data);
        $this->assertNull($result);
    }
}
