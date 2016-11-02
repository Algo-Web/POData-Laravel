<?php

namespace AlgoWeb\PODataLaravel\Models;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Illuminate\Database\Connection;

/**
 * Generated Test Class.
 */
class MetadataTraitTest extends \PHPUnit_Framework_TestCase
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
        $this->object = $this->getMockForTrait('\AlgoWeb\PODataLaravel\Models\MetadataTrait');
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
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
}
