<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 7/03/20
 * Time: 10:55 AM
 */

namespace AlgoWeb\PODataLaravel\Orchestra\Tests\Unit\Query;

use AlgoWeb\PODataLaravel\Orchestra\Tests\Models\OrchestraTestModel;
use AlgoWeb\PODataLaravel\Orchestra\Tests\TestCase;
use AlgoWeb\PODataLaravel\Query\LaravelWriteQuery;
use Mockery as m;
use POData\Providers\Metadata\ResourceSet;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\KeyDescriptor;

class LaravelWriteQueryTest extends TestCase
{
    public function testVerifyDeleteResourceGetsCorrectParms()
    {
        $model = new OrchestraTestModel();
        $model->id = 42;

        $resourceSet = m::mock(ResourceSet::class)->makePartial();
        $resourceSet->shouldReceive('getResourceType->getInstanceType->getName')
            ->andReturn(OrchestraTestModel::class);

        $foo = m::mock(LaravelWriteQuery::class)->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('createUpdateDeleteCore')
            ->with(m::type(OrchestraTestModel::class), ['id' => 42], OrchestraTestModel::class, 'delete')
            ->andReturn(['id' => 42])->once();
        $foo->shouldReceive('deleteResource')->passthru();
        $foo->shouldReceive('unpackSourceEntity')->andReturn($model);

        $result = $foo->deleteResource($resourceSet, $model);
        $this->assertTrue($result);
    }

    public function testDefaultShouldUpdateIsFalse()
    {
        $resourceSet = m::mock(ResourceSet::class)->makePartial();
        $model = new OrchestraTestModel();
        $keyDesc = m::mock(KeyDescriptor::class)->makePartial();
        $data = [];

        $foo = m::mock(LaravelWriteQuery::class)->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('createUpdateMainWrapper')->passthru()->once();
        $foo->shouldReceive('unpackSourceEntity')->passthru()->once();
        $foo->shouldReceive('createUpdateCoreWrapper')
            ->with(m::any(), m::any(), 'update', m::any(), false)->once();
        $foo->shouldReceive('updateResource')->passthru();

        $foo->updateResource($resourceSet, $model, $keyDesc, $data);
    }

    public function testDefaultShouldUpdateIsFalseTopLevel()
    {
        $resourceSet = m::mock(ResourceSet::class)->makePartial();
        $model = new OrchestraTestModel();
        $keyDesc = m::mock(KeyDescriptor::class)->makePartial();
        $data = [];

        $foo = m::mock(LaravelWriteQuery::class)->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('createUpdateMainWrapper')
            ->with(m::any(), m::any(), m::any(), 'update', false)->once();
        $foo->shouldReceive('updateResource')->passthru();

        $foo->updateResource($resourceSet, $model, $keyDesc, $data);
    }
}
