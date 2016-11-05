<?php

namespace AlgoWeb\PODataLaravel\Models;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Illuminate\Database\Connection;
use AlgoWeb\PODataLaravel\Models\TestCase as TestCase;

/**
 * Generated Test Class.
 */
class MetadataTraitTest extends TestCase
{
    /**
     * @var \AlgoWeb\PODataLaravel\Models\MetadataTrait
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->object = $this->getMockForTrait('\AlgoWeb\PODataLaravel\Models\MetadataTrait');
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        parent::tearDown();
    }

    /**
     * @covers \AlgoWeb\PODataLaravel\Models\MetadataTrait::metadata
     * @todo   Implement testMetadata().
     */
    public function testMetadata()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers \AlgoWeb\PODataLaravel\Models\MetadataTrait::metadata
     */
    public function testMetadataNotAnEloquentModel()
    {
        $class = get_class($this->object);
        $blewUp = false;
        $expected = 'assert(): '.$class.' failed';
        $actual = null;

        try {
            $result = $this->object->metadata();
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers \AlgoWeb\PODataLaravel\Models\MetadataTrait::metadata
     */
    public function testMetadataTableNotPresent()
    {
        $schemaBuilder = \Mockery::mock(\Illuminate\Database\Schema\Builder::class)->makePartial();
        $schemaBuilder->shouldReceive('hasTable')->andReturn(false);

        $connect = \Mockery::mock(Connection::class)->makePartial();
        $connect->shouldReceive('getSchemaBuilder')->andReturn($schemaBuilder);

        $foo = \Mockery::mock(TestModel::class)->makePartial();
        $foo->shouldReceive('getConnection')->andReturn($connect);

        $expected = 'assert(): '
                    . $foo->getTable()
                    . ' table not present in current db, '
                    .$foo->getConnectionName()
                    . ' failed';
        $actual = null;

        try {
            $result = $foo->metadata();
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }

        $this->assertEquals($expected, $actual);
    }

    public function testMetadataGeneration()
    {
        $intType = \Mockery::mock(\Doctrine\DBAL\Types\IntegerType::class);
        $intType->shouldReceive('getName')->andReturn('integer');

        $strType = \Mockery::mock(\Doctrine\DBAL\Types\StringType::class);
        $strType->shouldReceive('getName')->andReturn('string');

        $id = \Mockery::mock(\Doctrine\DBAL\Schema\Column::class)->makePartial();
        $id->shouldReceive('getNotNull')->andReturn(true);
        $id->shouldReceive('getType')->andReturn($intType);

        $name = \Mockery::mock(\Doctrine\DBAL\Schema\Column::class)->makePartial();
        $name->shouldReceive('getNotNull')->andReturn(true);
        $name->shouldReceive('getType')->andReturn($strType);

        $columns = ['id' => $id, 'name' => $name];

        $schemaBuilder = \Mockery::mock(\Illuminate\Database\Schema\Builder::class)->makePartial();
        $schemaBuilder->shouldReceive('hasTable')->andReturn(true);
        $schemaBuilder->shouldReceive('getColumnListing')->andReturn(['id', 'name', 'added_at', 'weight', 'code']);

        $connect = \Mockery::mock(Connection::class)->makePartial();
        $connect->shouldReceive('getSchemaBuilder')->andReturn($schemaBuilder);
        $connect->shouldReceive('getDoctrineSchemaManager->listTableColumns')->andReturn($columns);


        $foo = \Mockery::mock(TestModel::class)->makePartial();
        $foo->shouldReceive('getConnection')->andReturn($connect);
        $foo->shouldReceive('metadataMask')->andReturn(['id', 'name']);

        $expected = [];
        $expected['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false];
        $expected['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true];

        $result = $foo->metadata();
        $this->assertEquals($expected, $result);
    }


    /**
     * @covers \AlgoWeb\PODataLaravel\Models\MetadataTrait::metadataMask
     * @todo   Implement testMetadataMask().
     */
    public function testMetadataMask()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers \AlgoWeb\PODataLaravel\Models\MetadataTrait::metadataMask
     */
    public function testMetadataMaskNothingHiddenNothingVisible()
    {
        $expected = ['id', 'name', 'added_at', 'weight', 'code'];
        $foo = \Mockery::mock(TestModel::class)->makePartial();
        $foo->shouldReceive('getHidden')->andReturn([]);
        $foo->shouldReceive('getVisible')->andReturn([]);

        $result = $foo->metadataMask();
        $expResDiff = array_diff($expected, $result); // values in expected that are not in result, ignoring order
        $resExpDiff = array_diff($result, $expected); // values in result that are not in expected, ignoring order
        $this->assertEquals(0, count($expResDiff) + count($resExpDiff)); // if all keys are common, arrays are equal
    }

    /**
     * @covers \AlgoWeb\PODataLaravel\Models\MetadataTrait::metadataMask
     */
    public function testMetadataMaskNothingHiddenOverlappingVisible()
    {
        $expected = ['name'];
        $foo = \Mockery::mock(TestModel::class)->makePartial();
        $foo->shouldReceive('getHidden')->andReturn([]);
        $foo->shouldReceive('getVisible')->andReturn(['name', 'height']);

        $result = $foo->metadataMask();
        $expResDiff = array_diff($expected, $result); // values in expected that are not in result, ignoring order
        $resExpDiff = array_diff($result, $expected); // values in result that are not in expected, ignoring order
        $this->assertEquals(0, count($expResDiff) + count($resExpDiff)); // if all keys are common, arrays are equal
    }

    /**
     * @covers \AlgoWeb\PODataLaravel\Models\MetadataTrait::metadataMask
     */
    public function testMetadataMaskOverlappingHiddenNothingVisible()
    {
        $expected = ['id', 'added_at', 'weight', 'code'];
        $foo = \Mockery::mock(TestModel::class)->makePartial();
        $foo->shouldReceive('getHidden')->andReturn(['name', 'height']);
        $foo->shouldReceive('getVisible')->andReturn([]);

        $result = $foo->metadataMask();

        $expResDiff = array_diff($expected, $result); // values in expected that are not in result, ignoring order
        $resExpDiff = array_diff($result, $expected); // values in result that are not in expected, ignoring order
        $this->assertEquals(0, count($expResDiff) + count($resExpDiff)); // if all keys are common, arrays are equal
    }

    /**
     * @covers \AlgoWeb\PODataLaravel\Models\MetadataTrait::metadataMask
     */
    public function testMetadataMaskVisibleTakesPrecedenceOverHidden()
    {
        $expected = ['id', 'name'];
        $foo = \Mockery::mock(TestModel::class)->makePartial();
        $foo->shouldReceive('getHidden')->andReturn(['name', 'id']);
        $foo->shouldReceive('getVisible')->andReturn(['name', 'id']);

        $result = $foo->metadataMask();

        $expResDiff = array_diff($expected, $result); // values in expected that are not in result, ignoring order
        $resExpDiff = array_diff($result, $expected); // values in result that are not in expected, ignoring order
        $this->assertEquals(0, count($expResDiff) + count($resExpDiff)); // if all keys are common, arrays are equal
    }

    /**
     * @covers \AlgoWeb\PODataLaravel\Models\MetadataTrait::getXmlSchema
     * @todo   Implement testGetXmlSchema().
     */
    public function testGetXmlSchema()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers \AlgoWeb\PODataLaravel\Models\MetadataTrait::getXmlSchema
     */
    public function testGetXmlSchemaBlobAndOthers()
    {
        $expectedTypes = [];
        $expectedTypes['integer'] = 'POData\\Providers\\Metadata\\Type\\Int32';
        $expectedTypes['string'] = 'POData\\Providers\\Metadata\\Type\\StringType';

        $meta = [];
        $meta['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false];
        $meta['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true];
        $meta['photo'] = ['type' => 'blob', 'nullable' => true, 'fillable' => true];

        $foo = new TestModel($meta);

        $result = $foo->getXmlSchema();

        $props = $result->getPropertiesDeclaredOnThisType();
        $this->assertEquals(2, count($props));
        foreach ($props as $key => $val) {
            $this->assertTrue(array_key_exists($key, $meta));
            $targType = get_class($val->getInstanceType());
            $refType = $meta[$key]['type'];
            $this->assertEquals($expectedTypes[$refType], $targType);
        }

        $streams = $result->getNamedStreamsDeclaredOnThisType();
        $this->assertEquals(1, count($streams));
        foreach ($streams as $key => $val) {
            $this->assertTrue(array_key_exists($key, $meta));
            $this->assertEquals('blob', $meta[$key]['type']);
        }
    }


    /**
     * @covers \AlgoWeb\PODataLaravel\Models\MetadataTrait::hookUpRelationships
     * @todo   Implement testHookUpRelationships().
     */
    public function testHookUpRelationships()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers \AlgoWeb\PODataLaravel\Models\MetadataTrait::getRelationshipsFromMethods
     */
    public function testGetRelationshipsForMorphTarget()
    {
        $foo = new TestMorphTarget();

        $result = $foo->getRelationshipsFromMethods();
        $this->assertEquals(0, count($result['HasOne']));
        $this->assertEquals(0, count($result['HasMany']));
        $this->assertEquals(0, count($result['KnownPolyMorphSide']));
        $this->assertEquals(1, count($result['UnknownPolyMorphSide']));
        $this->assertTrue(array_key_exists('morph', $result['UnknownPolyMorphSide']));
    }

    /**
     * @covers \AlgoWeb\PODataLaravel\Models\MetadataTrait::getRelationshipsFromMethods
     */
    public function testGetRelationshipsForMorphManySource()
    {
        $foo = new TestMorphManySource();

        $result = $foo->getRelationshipsFromMethods();
        $this->assertEquals(0, count($result['HasOne']));
        $this->assertEquals(1, count($result['HasMany']));
        $this->assertEquals(1, count($result['KnownPolyMorphSide']));
        $this->assertEquals(0, count($result['UnknownPolyMorphSide']));
        $this->assertTrue(array_key_exists('morphTarget', $result['KnownPolyMorphSide']));
        $this->assertTrue(array_key_exists('morphTarget', $result['HasMany']));
    }

    /**
     * @covers \AlgoWeb\PODataLaravel\Models\MetadataTrait::getRelationshipsFromMethods
     */
    public function testGetRelationshipsForMorphOneSource()
    {
        $foo = new TestMorphOneSource();

        $result = $foo->getRelationshipsFromMethods();
        $this->assertEquals(1, count($result['HasOne']));
        $this->assertEquals(0, count($result['HasMany']));
        $this->assertEquals(1, count($result['KnownPolyMorphSide']));
        $this->assertEquals(0, count($result['UnknownPolyMorphSide']));
        $this->assertTrue(array_key_exists('morphTarget', $result['KnownPolyMorphSide']));
        $this->assertTrue(array_key_exists('morphTarget', $result['HasOne']));
    }
}
