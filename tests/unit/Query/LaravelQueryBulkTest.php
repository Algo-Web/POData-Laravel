<?php

namespace AlgoWeb\PODataLaravel\Query;

use AlgoWeb\PODataLaravel\Controllers\MetadataControllerContainer;
use AlgoWeb\PODataLaravel\Models\TestCase;
use AlgoWeb\PODataLaravel\Models\TestModel;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Mockery as m;
use POData\Providers\Metadata\ResourceEntityType;
use POData\Providers\Metadata\ResourceSet;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\KeyDescriptor;

class LaravelQueryBulkTest extends TestCase
{
    protected $origFacade = [];

    public function testContainerRetrieval()
    {
        $foo = new LaravelQuery();
        $result = $foo->getControllerContainer();
        $this->assertTrue($result instanceof MetadataControllerContainer);
    }

    public function testBulkCreate()
    {
        $resultModel = m::mock(TestModel::class);
        $container = m::mock(MetadataControllerContainer::class);
        $container->shouldReceive('getMapping')->andReturn(null)->once();

        $db = DB::getFacadeRoot();
        $db->shouldReceive('rollBack')->andReturnNull()->never();
        $db->shouldReceive('beginTransaction')->andReturnNull()->once();
        $db->shouldReceive('commit')->andReturnNull()->once();

        $iType = new \ReflectionClass(TestModel::class);
        $type = m::mock(ResourceEntityType::class);
        $type->shouldReceive('getInstanceType')->andReturn($iType);
        $source = m::mock(ResourceSet::class);
        $source->shouldReceive('getResourceType')->andReturn($type);
        $data = [ ['data']];

        $foo = m::mock(LaravelQuery::class)->makePartial();
        $foo->shouldReceive('createResourceforResourceSet')->andReturn($resultModel);
        $foo->shouldReceive('getControllerContainer')->andReturn($container);

        $actual = $foo->createBulkResourceforResourceSet($source, $data);
        $this->assertEquals(1, count($actual));
        $this->assertTrue($actual[0] instanceof TestModel, get_class($actual[0]));
    }

    public function testBulkCreateFailure()
    {
        $container = m::mock(MetadataControllerContainer::class);
        $container->shouldReceive('getMapping')->andReturn(null)->once();
        $db = DB::getFacadeRoot();
        $db->shouldReceive('rollBack')->andReturnNull()->once();
        $db->shouldReceive('beginTransaction')->andReturnNull()->once();
        $db->shouldReceive('commit')->andReturnNull()->never();

        $iType = new \ReflectionClass(TestModel::class);
        $type = m::mock(ResourceEntityType::class);
        $type->shouldReceive('getInstanceType')->andReturn($iType);
        $source = m::mock(ResourceSet::class);
        $source->shouldReceive('getResourceType')->andReturn($type);
        $data = [ ['data']];

        $foo = m::mock(LaravelQuery::class)->makePartial();
        $foo->shouldReceive('createResourceforResourceSet')->andReturn(null);
        $foo->shouldReceive('getControllerContainer')->andReturn($container);

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
        $container = m::mock(MetadataControllerContainer::class);
        $container->shouldReceive('getMapping')->andReturn(null)->once();

        $db = DB::getFacadeRoot();
        $db->shouldReceive('rollBack')->andReturnNull()->never();
        $db->shouldReceive('beginTransaction')->andReturnNull()->once();
        $db->shouldReceive('commit')->andReturnNull()->once();

        $iType = new \ReflectionClass(TestModel::class);
        $type = m::mock(ResourceEntityType::class);
        $type->shouldReceive('getInstanceType')->andReturn($iType);
        $source = m::mock(ResourceSet::class);
        $source->shouldReceive('getResourceType')->andReturn($type);
        $data = [ ['data']];
        $keys = [ m::mock(KeyDescriptor::class)];
        $resultModel = m::mock(TestModel::class);

        $foo = m::mock(LaravelQuery::class)->makePartial();
        $foo->shouldReceive('updateResource')->andReturn($resultModel);
        $foo->shouldReceive('getControllerContainer')->andReturn($container);

        $actual = $foo->updateBulkResource($source, null, $keys, $data);
        $this->assertEquals(1, count($actual));
        $this->assertTrue($actual[0] instanceof TestModel, get_class($actual[0]));
    }

    public function testBulkUpdateFailure()
    {
        $container = m::mock(MetadataControllerContainer::class);
        $container->shouldReceive('getMapping')->andReturn(null)->once();
        $db = DB::getFacadeRoot();
        $db->shouldReceive('rollBack')->andReturnNull()->once();
        $db->shouldReceive('beginTransaction')->andReturnNull()->once();
        $db->shouldReceive('commit')->andReturnNull()->never();

        $iType = new \ReflectionClass(TestModel::class);
        $type = m::mock(ResourceEntityType::class);
        $type->shouldReceive('getInstanceType')->andReturn($iType);
        $source = m::mock(ResourceSet::class);
        $source->shouldReceive('getResourceType')->andReturn($type);
        $data = [ ['data']];
        $keys = [ m::mock(KeyDescriptor::class)];

        $foo = m::mock(LaravelQuery::class)->makePartial();
        $foo->shouldReceive('updateResource')->andReturn(null);
        $foo->shouldReceive('getControllerContainer')->andReturn($container);

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
