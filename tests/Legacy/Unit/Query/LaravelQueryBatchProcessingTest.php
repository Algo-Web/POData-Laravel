<?php

declare(strict_types=1);

namespace Tests\Legacy\AlgoWeb\PODataLaravel\Unit\Query;

use AlgoWeb\PODataLaravel\Query\LaravelQuery;
use AlgoWeb\PODataLaravel\Query\LaravelWriteQuery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Mockery as m;
use POData\Providers\Metadata\ResourceSet;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\KeyDescriptor;
use Tests\Legacy\AlgoWeb\PODataLaravel\TestCase;

class LaravelQueryBatchProcessingTest extends TestCase
{
    public function testQueuedSaveTriggeredOnTransactionCommitInBatch()
    {
        $db = App::make('db');
        $db->shouldReceive('beginTransaction')->andReturnNull()->once();
        $db->shouldReceive('commit')->andReturnNull()->once();
        $foo = new LaravelQuery();

        $model = m::mock(Model::class);
        $model->shouldReceive('save')->andReturn(true)->once();

        $foo->startTransaction(true);
        LaravelQuery::queueModel($model);
        $foo->commitTransaction();
    }

    public function testQueuedSaveNotTriggeredOnTransactionRollbackInBatch()
    {
        $db = App::make('db');
        $db->shouldReceive('beginTransaction')->andReturnNull()->once();
        $db->shouldReceive('rollBack')->andReturnNull()->once();
        $foo = new LaravelQuery();

        $model = m::mock(Model::class);
        $model->shouldReceive('save')->andReturn(true)->never();

        $foo->startTransaction(true);
        LaravelQuery::queueModel($model);
        $foo->rollBackTransaction();
    }

    public function testQueuedSaveTriggeredOnTransactionCommitNotInBatch()
    {
        $db = App::make('db');
        $db->shouldReceive('beginTransaction')->andReturnNull()->once();
        $db->shouldReceive('commit')->andReturnNull()->once();
        $foo = new LaravelQuery();

        $model = m::mock(Model::class);
        $model->shouldReceive('save')->andReturn(true)->never();

        $foo->startTransaction();
        LaravelQuery::queueModel($model);
        $foo->commitTransaction();
    }

    public function testUpdateResultGetsQueuedAndProcessed()
    {
        $db = App::make('db');
        $db->shouldReceive('beginTransaction')->andReturnNull()->once();
        $db->shouldReceive('commit')->andReturnNull()->once();

        $model = m::mock(Model::class);
        $model->shouldReceive('save')->andReturn(true)->once();

        $rSet    = m::mock(ResourceSet::class);
        $keyDesc = m::mock(KeyDescriptor::class);

        $writer = m::mock(LaravelWriteQuery::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $writer->shouldReceive('createUpdateCoreWrapper')->andReturn($model);

        $foo = m::mock(LaravelQuery::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getWriter')->andReturn($writer);

        $foo->startTransaction(true);
        $foo->updateResource($rSet, $model, $keyDesc, new \stdClass());
        $foo->commitTransaction();
    }

    public function testCreateResultGetsQueuedAndProcessed()
    {
        $db = App::make('db');
        $db->shouldReceive('beginTransaction')->andReturnNull()->once();
        $db->shouldReceive('commit')->andReturnNull()->once();

        $model = m::mock(Model::class);
        $model->shouldReceive('save')->andReturn(true)->once();

        $rSet = m::mock(ResourceSet::class);

        $writer = m::mock(LaravelWriteQuery::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $writer->shouldReceive('createUpdateCoreWrapper')->andReturn($model);

        $foo = m::mock(LaravelQuery::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getWriter')->andReturn($writer);

        $foo->startTransaction(true);
        $foo->createResourceforResourceSet($rSet, $model, new \stdClass());
        $foo->commitTransaction();
    }
}
