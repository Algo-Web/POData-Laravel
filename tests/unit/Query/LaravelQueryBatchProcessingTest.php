<?php

namespace AlgoWeb\PODataLaravel\Query;

use AlgoWeb\PODataLaravel\Controllers\MetadataControllerContainer;
use AlgoWeb\PODataLaravel\Controllers\TestController;
use AlgoWeb\PODataLaravel\Interfaces\AuthInterface;
use AlgoWeb\PODataLaravel\Models\LaravelBulkQueryDummy;
use AlgoWeb\PODataLaravel\Models\LaravelQueryDummy;
use AlgoWeb\PODataLaravel\Models\TestBulkCreateRequest;
use AlgoWeb\PODataLaravel\Models\TestBulkUpdateRequest;
use AlgoWeb\PODataLaravel\Models\TestCase;
use AlgoWeb\PODataLaravel\Models\TestModel;
use AlgoWeb\PODataLaravel\Providers\MetadataProvider;
use AlgoWeb\PODataLaravel\Requests\TestRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Mockery as m;
use POData\Common\ODataException;
use POData\Providers\Metadata\ResourceEntityType;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Metadata\Type\Int32;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\KeyDescriptor;

class LaravelQueryBatchTest extends TestCase
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
}