<?php

namespace AlgoWeb\PODataLaravel\Query;

use AlgoWeb\PODataLaravel\Models\TestCase;
use AlgoWeb\PODataLaravel\Models\TestModel;
use Illuminate\Support\Facades\DB;
use Mockery as m;
use POData\Providers\Metadata\ResourceSet;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\KeyDescriptor;

class LaravelQueryBulkTest extends TestCase
{
    protected $origFacade = [];

    public function testBulkCreate()
    {
        $db = DB::getFacadeRoot();
        $db->shouldReceive('rollBack')->andReturnNull()->never();
        $db->shouldReceive('beginTransaction')->andReturnNull()->once();
        $db->shouldReceive('commit')->andReturnNull()->once();

        $source = m::mock(ResourceSet::class);
        $data = [ ['data']];
        $resultModel = m::mock(TestModel::class);

        $foo = m::mock(LaravelQuery::class)->makePartial();
        $foo->shouldReceive('createResourceforResourceSet')->andReturn($resultModel);

        $actual = $foo->createBulkResourceforResourceSet($source, $data);
        $this->assertEquals(1, count($actual));
        $this->assertTrue($actual[0] instanceof TestModel, get_class($actual[0]));
    }

    public function testBulkCreateFailure()
    {
        $db = DB::getFacadeRoot();
        $db->shouldReceive('rollBack')->andReturnNull()->once();
        $db->shouldReceive('beginTransaction')->andReturnNull()->once();
        $db->shouldReceive('commit')->andReturnNull()->never();

        $source = m::mock(ResourceSet::class);
        $data = [ ['data']];

        $foo = m::mock(LaravelQuery::class)->makePartial();
        $foo->shouldReceive('createResourceforResourceSet')->andReturn(null);

        $expected = 'Bulk model creation failed';
        $actual = null;

        try {
            $foo->createBulkResourceforResourceSet($source, $data);
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testBulkUpdate()
    {
        $db = DB::getFacadeRoot();
        $db->shouldReceive('rollBack')->andReturnNull()->never();
        $db->shouldReceive('beginTransaction')->andReturnNull()->once();
        $db->shouldReceive('commit')->andReturnNull()->once();

        $source = m::mock(ResourceSet::class);
        $data = [ ['data']];
        $keys = [ m::mock(KeyDescriptor::class)];
        $resultModel = m::mock(TestModel::class);

        $foo = m::mock(LaravelQuery::class)->makePartial();
        $foo->shouldReceive('updateResource')->andReturn($resultModel);

        $actual = $foo->updateBulkResource($source, null, $keys, $data);
        $this->assertEquals(1, count($actual));
        $this->assertTrue($actual[0] instanceof TestModel, get_class($actual[0]));
    }

    public function testBulkUpdateFailure()
    {
        $db = DB::getFacadeRoot();
        $db->shouldReceive('rollBack')->andReturnNull()->once();
        $db->shouldReceive('beginTransaction')->andReturnNull()->once();
        $db->shouldReceive('commit')->andReturnNull()->never();

        $source = m::mock(ResourceSet::class);
        $data = [ ['data']];
        $keys = [ m::mock(KeyDescriptor::class)];

        $foo = m::mock(LaravelQuery::class)->makePartial();
        $foo->shouldReceive('updateResource')->andReturn(null);

        $expected = 'Bulk model update failed';
        $actual = null;

        try {
            $foo->updateBulkResource($source, null, $keys, $data);
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testBulkUpdateCountMismatch()
    {
        $source = m::mock(ResourceSet::class);
        $data = [ ['data']];
        $keys = [ ];
        $resultModel = m::mock(TestModel::class);

        $foo = m::mock(LaravelQuery::class)->makePartial();
        $foo->shouldReceive('updateResource')->andReturn($resultModel);

        $expected = 'Key descriptor array and data array must be same length';
        $actual = null;

        try {
            $foo->updateBulkResource($source, null, $keys, $data);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function setUp()
    {
        parent::setUp();
        $this->origFacade['DB'] = DB::getFacadeRoot();
    }

    public function tearDown()
    {
        DB::swap($this->origFacade['DB']);
        parent::tearDown();
    }
}
