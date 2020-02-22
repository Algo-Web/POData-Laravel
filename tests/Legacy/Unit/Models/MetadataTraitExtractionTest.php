<?php

namespace Tests\Legacy\AlgoWeb\PODataLaravel\Unit\Models;

use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubPolymorphic;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubRelationType as RelType;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\StringType;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Mockery as m;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestGetterModel;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestModel;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMonomorphicSource;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMorphOneSource;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMorphTargetChild;
use Tests\Legacy\AlgoWeb\PODataLaravel\TestCase;

class MetadataTraitExtractionTest extends TestCase
{
    /**
     * @throws \Exception
     */
    public function setUp(): void
    {
        parent::setUp();
        $foo = new TestGetterModel();
        $foo->reset();
    }

    public function testExtractGubbinsMonomorphicSource()
    {
        $metaRaw = [];
        $metaRaw['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metaRaw['photo'] = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];

        $rawKeys = array_keys($metaRaw);
        $relations = ['oneSource', 'manySource'];
        $relKeys = ['one_id', 'many_id'];
        $relMults = [RelType::NULL_ONE(), RelType::MANY()];

        $foo = new TestMonomorphicSource($metaRaw);

        $result = $foo->extractGubbins();
        $this->assertEquals(1, count($result->getKeyFields()));

        $this->assertEquals('id', $result->getKeyFields()['id']->getName());
        $this->assertEquals(3, count($result->getFields()));
        $fields = $result->getFields();
        foreach ($fields as $field) {
            $this->assertTrue(in_array($field->getName(), $rawKeys));
        }
        $this->assertEquals(2, count($result->getStubs()));
        $stubs = $result->getStubs();
        foreach ($stubs as $stub) {
            $this->assertTrue($stub->isOk());
            $this->assertTrue(in_array($stub->getKeyField(), $relKeys));
            $this->assertTrue(in_array($stub->getRelationName(), $relations));
        }
    }

    public function testExtractGubbinsTestMorphTargetChild()
    {
        $metaRaw = [];
        $metaRaw['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metaRaw['photo'] = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];

        $rawKeys = array_keys($metaRaw);
        $relations = ['morph'];
        $relKeys = ['id'];
        $relMults = [RelType::ONE()];

        $foo = new TestMorphTargetChild($metaRaw);

        $result = $foo->extractGubbins();
        $this->assertEquals(1, count($result->getKeyFields()));
        $this->assertEquals('id', $result->getKeyFields()['id']->getName());
        $this->assertEquals(3, count($result->getFields()));
        $fields = $result->getFields();
        foreach ($fields as $field) {
            $this->assertTrue(in_array($field->getName(), $rawKeys));
        }
        $this->assertEquals(1, count($result->getStubs()));
        $stubs = $result->getStubs();
        $this->assertTrue($stubs['morph'] instanceof AssociationStubPolymorphic, get_class($stubs['morph']));
        $this->assertNull($stubs['morph']->getTargType());
        foreach ($stubs as $stub) {
            $this->assertTrue($stub->isOk());
            $this->assertTrue(in_array($stub->getKeyField(), $relKeys));
            $this->assertTrue(in_array($stub->getRelationName(), $relations));
        }
    }

    public function testCollidingPrimitivePropertyNames()
    {
        $meta = [];
        $meta['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $meta['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $meta['Name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $meta['photo'] = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];

        $foo = new TestModel($meta, null);
        $foo->name = 'Commence Primary Ignition';

        $expected = 'Property names must be unique, without regard to case';
        $actual = null;

        try {
            $result = $foo->extractGubbins();
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testPropertyAndRelationNameCollision()
    {
        $meta = [];
        $meta['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $meta['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $meta['MORPHTARGET'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $meta['photo'] = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];

        $foo = new TestMorphOneSource($meta, null);
        $foo->name = 'Commence Primary Ignition';

        $expected = 'Property names must be unique, without regard to case';
        $actual = null;

        try {
            $result = $foo->extractGubbins();
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    /**
     * @throws \ReflectionException
     */
    public function testExcludedGetterIsActuallyExcluded()
    {
        $expected = [];
        $expected['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => 'name'];
        $expected['added_at'] =
            ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => '2017-10-11T00:00:00'];
        $expected['weight'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => '100'];
        $expected['code'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => 'code'];

        $type = m::mock(StringType::class)->makePartial();

        $name = m::mock(Column::class)->makePartial();
        $name->shouldReceive('getType')->andReturn($type)->atLeast(1);
        $addedAt = m::mock(Column::class)->makePartial();
        $addedAt->shouldReceive('getType')->andReturn($type)->atLeast(1);
        $weight = m::mock(Column::class)->makePartial();
        $weight->shouldReceive('getType')->andReturn($type)->atLeast(1);
        $code = m::mock(Column::class)->makePartial();
        $code->shouldReceive('getType')->andReturn($type)->atLeast(1);

        $rawColumns = ['name' => $name, 'added_at' => $addedAt, 'weight' => $weight, 'code' => $code];

        $manager = m::mock(AbstractSchemaManager::class)->makePartial();
        $manager->shouldReceive('listTableColumns')->andReturn($rawColumns)->atLeast(1);

        $columns = [ 'name', 'added_at', 'weight', 'code'];

        $builder = m::mock(Blueprint::class)->makePartial();
        $builder->shouldReceive('hasTable')->andReturn(true)->atLeast(1);
        $builder->shouldReceive('getColumnListing')->andReturn($columns)->atLeast(1);

        $connect = m::mock(Connection::class)->makePartial();
        $connect->shouldReceive('getSchemaBuilder')->andReturn($builder);
        $connect->shouldReceive('getDoctrineSchemaManager')->andReturn($manager)->atLeast(1);

        $foo = m::mock(TestGetterModel::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getCasts')->passthru();
        $foo->shouldReceive('getConnection')->andReturn($connect);
        // exclude the WeightCode getter from results
        $foo->shouldReceive('getHidden')->andReturn(['WeightCode', 'weightCode']);
        $foo->weight = 10;
        $foo->name = 'name';
        $foo->added_at = '2017-10-11T00:00:00';
        $foo->code = 'code';

        $actual = $foo->metadata();
        $this->assertEquals($expected, $actual);
        $final = $foo->metadata();
        $this->assertEquals($expected, $final);
    }

    public function testGettersChangeMetadataType()
    {
        $type = m::mock(StringType::class)->makePartial();

        $name = m::mock(Column::class)->makePartial();
        $name->shouldReceive('getType')->andReturn($type)->atLeast(1);
        $addedAt = m::mock(Column::class)->makePartial();
        $addedAt->shouldReceive('getType')->andReturn($type)->atLeast(1);
        $weight = m::mock(Column::class)->makePartial();
        $weight->shouldReceive('getType')->andReturn($type)->atLeast(1);
        $code = m::mock(Column::class)->makePartial();
        $code->shouldReceive('getType')->andReturn($type)->atLeast(1);

        $rawColumns = ['name' => $name, 'added_at' => $addedAt, 'weight' => $weight, 'code' => $code];

        $manager = m::mock(AbstractSchemaManager::class)->makePartial();
        $manager->shouldReceive('listTableColumns')->andReturn($rawColumns)->atLeast(1);

        $columns = [ 'name', 'added_at', 'weight', 'code'];

        $builder = m::mock(Blueprint::class)->makePartial();
        $builder->shouldReceive('hasTable')->andReturn(true)->atLeast(1);
        $builder->shouldReceive('getColumnListing')->andReturn($columns)->atLeast(1);

        $connect = m::mock(Connection::class)->makePartial();
        $connect->shouldReceive('getSchemaBuilder')->andReturn($builder);
        $connect->shouldReceive('getDoctrineSchemaManager')->andReturn($manager)->atLeast(1);

        $foo = m::mock(TestGetterModel::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getConnection')->andReturn($connect);
        $foo->reset();
        $foo->setCasts(['weight' => 'float']);
        $foo->weight = 10;
        $foo->name = 'name';
        $foo->added_at = '2017-10-11T00:00:00';
        $foo->code = 'code';

        $result = $foo->metadata();
        $this->assertEquals('float', $result['weight']['type']);
        // now reset metadata, change to an excluded cast type, and verify that weight drops back to its underlying
        // metadata type
        $foo->reset();
        $foo->setCasts(['weight' => 'ARRAY']);
        $result = $foo->metadata();
        $this->assertEquals('string', $result['weight']['type']);
    }

    public function testGettersDontChangeMetadataFillableNullable()
    {
        $expected = [];
        $expected['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => 'name'];
        $expected['added_at'] =
            ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => '2017-10-11T00:00:00'];
        $expected['weight'] = ['type' => 'float', 'nullable' => false, 'fillable' => true, 'default' => '100'];
        $expected['code'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => 'code'];
        $expected['WeightCode'] = ['type' => 'text', 'nullable' => true, 'fillable' => false, 'default' => '100code'];

        $type = m::mock(StringType::class)->makePartial();

        $name = m::mock(Column::class)->makePartial();
        $name->shouldReceive('getType')->andReturn($type)->atLeast(1);
        $addedAt = m::mock(Column::class)->makePartial();
        $addedAt->shouldReceive('getType')->andReturn($type)->atLeast(1);
        $weight = m::mock(Column::class)->makePartial();
        $weight->shouldReceive('getType')->andReturn($type)->atLeast(1);
        $code = m::mock(Column::class)->makePartial();
        $code->shouldReceive('getType')->andReturn($type)->atLeast(1);

        $rawColumns = ['name' => $name, 'added_at' => $addedAt, 'weight' => $weight, 'code' => $code];

        $manager = m::mock(AbstractSchemaManager::class)->makePartial();
        $manager->shouldReceive('listTableColumns')->andReturn($rawColumns)->atLeast(1);

        $columns = [ 'name', 'added_at', 'weight', 'code'];

        $builder = m::mock(Blueprint::class)->makePartial();
        $builder->shouldReceive('hasTable')->andReturn(true)->atLeast(1);
        $builder->shouldReceive('getColumnListing')->andReturn($columns)->atLeast(1);

        $connect = m::mock(Connection::class)->makePartial();
        $connect->shouldReceive('getSchemaBuilder')->andReturn($builder);
        $connect->shouldReceive('getDoctrineSchemaManager')->andReturn($manager)->atLeast(1);

        $foo = m::mock(TestGetterModel::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getConnection')->andReturn($connect);
        $foo->reset();
        $foo->setCasts(['weight' => 'float']);
        $foo->weight = 10;
        $foo->name = 'name';
        $foo->added_at = '2017-10-11T00:00:00';
        $foo->code = 'code';

        $actual = $foo->metadata();
        $this->assertEquals($expected, $actual);
    }
}
