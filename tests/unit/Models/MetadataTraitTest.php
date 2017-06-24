<?php

namespace AlgoWeb\PODataLaravel\Models;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Illuminate\Database\Connection;
use AlgoWeb\PODataLaravel\Models\TestCase as TestCase;
use Mockery as m;
use POData\Providers\Metadata\ResourceEntityType;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\SimpleMetadataProvider;

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
    public function setUp()
    {
        parent::setUp();
        $this->object = $this->getMockForTrait('\AlgoWeb\PODataLaravel\Models\MetadataTrait');
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    public function tearDown()
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

        $result = $foo->metadata();
        $this->assertTrue(is_array($result));
        $this->assertEquals(0, count($result));
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
        $expected['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $expected['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];

        $result = $foo->metadata();
        $this->assertEquals($expected, $result);
    }

    public function testMetadataGenerationWithGetter()
    {
        $expected = [];
        $expected['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $expected['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $expected['added_at'] = ['type' => 'integer', 'nullable' => false, 'fillable' => true, 'default' => null];
        $expected['weight'] = ['type' => 'integer', 'nullable' => false, 'fillable' => true, 'default' => null];
        $expected['code'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $expected['WeightCode'] = ['type' => 'text', 'nullable' => true, 'fillable' => false, 'default' => ""];

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

        $columns = ['id' => $id, 'name' => $name, 'added_at' => $id, 'weight' => $id, 'code' => $name];

        $schemaBuilder = \Mockery::mock(\Illuminate\Database\Schema\Builder::class)->makePartial();
        $schemaBuilder->shouldReceive('hasTable')->andReturn(true);
        $schemaBuilder->shouldReceive('getColumnListing')->andReturn(['id', 'name', 'added_at', 'weight', 'code']);

        $connect = \Mockery::mock(Connection::class)->makePartial();
        $connect->shouldReceive('getSchemaBuilder')->andReturn($schemaBuilder);
        $connect->shouldReceive('getDoctrineSchemaManager->listTableColumns')->andReturn($columns);

        $foo = \Mockery::mock(TestGetterModel::class)->makePartial();
        $foo->shouldReceive('getweightAttribute')->andReturn(null);
        $foo->shouldReceive('getConnection')->andReturn($connect);
        $result = $foo->metadata();
        $this->assertEquals(count($expected), count($result));
        foreach ($expected as $key => $val) {
            $this->assertTrue($val === $result[$key]);
        }
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
    public function testMetadataMaskNothingHiddenOverlappingSingleVisible()
    {
        $expected = ['name'];
        $foo = \Mockery::mock(TestModel::class)->makePartial();
        $foo->shouldReceive('getHidden')->andReturn([]);
        $foo->shouldReceive('getVisible')->andReturn(['name']);

        $result = $foo->metadataMask();
        $expResDiff = array_diff($expected, $result); // values in expected that are not in result, ignoring order
        $resExpDiff = array_diff($result, $expected); // values in result that are not in expected, ignoring order
        $this->assertEquals(0, count($expResDiff) + count($resExpDiff)); // if all keys are common, arrays are equal
    }

    /**
     * @covers \AlgoWeb\PODataLaravel\Models\MetadataTrait::metadataMask
     */
    public function testMetadataMaskNothingVisibleOverlappingSingleHidden()
    {
        $expected = ['id', 'added_at', 'weight', 'code'];
        $foo = \Mockery::mock(TestModel::class)->makePartial();
        $foo->shouldReceive('getHidden')->andReturn(['name']);
        $foo->shouldReceive('getVisible')->andReturn([]);

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

    public function testGetXmlSchemaBlobAndOthers()
    {
        $expectedTypes = [];
        $expectedTypes['integer'] = 'POData\\Providers\\Metadata\\Type\\Int32';
        $expectedTypes['string'] = 'POData\\Providers\\Metadata\\Type\\StringType';

        $meta = [];
        $meta['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $meta['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $meta['photo'] = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];

        $foo = new TestModel($meta);

        $result = $foo->getXmlSchema();
        $this->assertEquals('TestModel', $result->getName());

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

    public function testGetXmlSchemaOnEmptyMetadata()
    {
        $meta = [];

        $foo = new TestModel($meta);

        $result = $foo->getXmlSchema();
        $this->assertNull($result);
    }


    /**
     * @covers \AlgoWeb\PODataLaravel\Models\MetadataTrait::hookUpRelationships
     */
    public function testHookUpRelationshipsBadInputFormat()
    {
        $meta = [];

        $foo = new TestModel($meta);
        $types = 'types';
        $sets = 'sets';

        $expected = 'assert(): Both entityTypes and resourceSets must be arrays failed';
        $actual = null;

        try {
            $result = $foo->hookUpRelationships($types, $sets);
        } catch (\ErrorException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testHookUpRelationshipsOneInputNotArray()
    {
        $foo = m::mock(TestModel::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getRelationshipsFromMethods')->andReturn([])->never();

        $types = [];
        $sets = 'scoobie oobie doobie oobie doobie melodie';

        $expected = 'assert(): Both entityTypes and resourceSets must be arrays failed';
        $actual = null;

        try {
            $result = $foo->hookUpRelationships($types, $sets);
        } catch (\ErrorException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
        try {
            $result = $foo->hookUpRelationships($sets, $types);
        } catch (\ErrorException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testHookUpRelationshipsBothEmptyArrays()
    {
        $foo = m::mock(TestModel::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getRelationshipsFromMethods')->andReturn([])->once();

        $types = [];
        $sets = [];

        $result = $foo->hookUpRelationships($types, $sets);
        $this->assertTrue(is_array($result));
        $this->assertEquals(0, count($result));
    }

    public function testHookUpRelationshipsExistsInOnlyOneArray()
    {
        $foo = m::mock(TestModel::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getRelationshipsFromMethods')->andReturn([])->twice();

        $types = [get_class($foo) => 'bar'];
        $sets = [];

        $result = $foo->hookUpRelationships($types, $sets);
        $this->assertTrue(is_array($result));
        $this->assertEquals(0, count($result));

        $result = $foo->hookUpRelationships($sets, $types);
        $this->assertTrue(is_array($result));
        $this->assertEquals(0, count($result));
    }

    public function testHookUpRelationshipsHasOnlyHasOneRelations()
    {
        $meta = m::mock(SimpleMetadataProvider::class);
        $meta->shouldReceive('addResourceReferenceProperty')->withAnyArgs()->andReturnNull()->once();
        $meta->shouldReceive('addResourceSetReferenceProperty')->withAnyArgs()->andReturnNull()->never();
        App::instance('metadata', $meta);

        $foo = m::mock(TestModel::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $fooName = get_class($foo);
        $barName = TestMorphTarget::class;

        $rel = [];
        $rel['HasOne'] = ['b' => $barName];
        $rel['HasMany'] = [];
        $foo->shouldReceive('getRelationshipsFromMethods')->andReturn($rel)->once();

        $type1 = m::mock(ResourceType::class);
        $type2 = m::mock(ResourceType::class);
        $set1 = m::mock(ResourceSet::class);
        $set2 = m::mock(ResourceSet::class);
        $types = [$fooName => $type1, $barName => $type2];
        $sets = [$fooName => $set1, $barName => $set2];

        $result = $foo->hookUpRelationships($types, $sets);
        $this->assertTrue(is_array($result));
        $this->assertEquals(2, count($result));
    }

    public function testHookUpRelationshipsHasOnlyHasManyRelations()
    {
        $meta = m::mock(SimpleMetadataProvider::class);
        $meta->shouldReceive('addResourceReferenceProperty')->withAnyArgs()->andReturnNull()->never();
        $meta->shouldReceive('addResourceSetReferenceProperty')->withAnyArgs()->andReturnNull()->once();
        App::instance('metadata', $meta);

        $foo = m::mock(TestModel::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $fooName = get_class($foo);
        $barName = TestMorphTarget::class;

        $rel = [];
        $rel['HasMany'] = ['b' => $barName];
        $rel['HasOne'] = [];
        $foo->shouldReceive('getRelationshipsFromMethods')->andReturn($rel)->once();

        $type1 = m::mock(ResourceEntityType::class);
        $type2 = m::mock(ResourceEntityType::class);
        $set1 = m::mock(ResourceSet::class);
        $set2 = m::mock(ResourceSet::class);
        $types = [$fooName => $type1, $barName => $type2];
        $sets = [$fooName => $set1, $barName => $set2];

        $result = $foo->hookUpRelationships($types, $sets);
        $this->assertTrue(is_array($result));
        $this->assertEquals(2, count($result));
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

    /**
     * @covers \AlgoWeb\PODataLaravel\Models\MetadataTrait::getRelationshipsFromMethods
     */
    public function testGetRelationshipsForMorphManyToManySource()
    {
        $foo = new TestMorphManyToManySource();
        $result = $foo->getRelationshipsFromMethods();
        $this->assertEquals(0, count($result['HasOne']));
        $this->assertEquals(1, count($result['HasMany']));
        $this->assertEquals(0, count($result['KnownPolyMorphSide']));
        $this->assertEquals(1, count($result['UnknownPolyMorphSide']));
        $this->assertTrue(array_key_exists('manySource', $result['UnknownPolyMorphSide']));
        $this->assertTrue(array_key_exists('manySource', $result['HasMany']));
    }

    /**
     * @covers \AlgoWeb\PODataLaravel\Models\MetadataTrait::getRelationshipsFromMethods
     */
    public function testGetRelationshipsForMorphManyToManyTarget()
    {
        $foo = new TestMorphManyToManyTarget();
        $result = $foo->getRelationshipsFromMethods();
        $this->assertEquals(0, count($result['HasOne']));
        $this->assertEquals(1, count($result['HasMany']));
        $this->assertEquals(1, count($result['KnownPolyMorphSide']));
        $this->assertEquals(0, count($result['UnknownPolyMorphSide']));
        $this->assertTrue(array_key_exists('manyTarget', $result['KnownPolyMorphSide']));
        $this->assertTrue(array_key_exists('manyTarget', $result['HasMany']));
    }

    public function testGetDefaultEndpointName()
    {
        $foo = new TestModel();

        $expected = 'testmodel';
        $actual = $foo->getEndpointName();
        $this->assertEquals($expected, $actual);
    }

    public function testGetEndpointSpecifiedName()
    {
        $foo = new TestModel(null, 'EndPoint');
        $expected = 'endpoint';
        $actual = $foo->getEndpointName();
        $this->assertEquals($expected, $actual);
    }

    public function testGetMetadataMaskWithGetterSet()
    {
        $columns = ['name', 'added_at', 'weight', 'code'];
        $expected = ['name', 'added_at', 'weight', 'code', 'WeightCode'];

        $mockBuilder = \Mockery::mock(\Illuminate\Database\Schema\MySqlBuilder::class)->makePartial();
        $mockBuilder->shouldReceive('getColumnListing')->andReturn($columns);

        $foo = \Mockery::mock(TestGetterModel::class)->makePartial();
        $foo->shouldReceive('getConnection->getSchemaBuilder')->andReturn($mockBuilder);

        $result = $foo->metadataMask();
        $this->assertEquals(count($expected), count($result));
        for ($i = 0; $i < count($result); $i++) {
            $this->assertEquals($expected[$i], $result[$i]);
        }
    }

    public function testGetSetEagerLoadGoodData()
    {
        $foo = new TestMonomorphicSource();
        $relations = ['manySource', 'oneSource'];
        $foo->setEagerLoad($relations);
        $result = $foo->getEagerLoad();
        $this->assertEquals($relations, $result);
    }

    public function testSetEagerLoadBadDataIsObject()
    {
        $foo = new TestMonomorphicSource();
        $relations = [new \DateTime()];

        $expected = "Object of class DateTime could not be converted to string";
        $actual = null;

        try {
            $foo->setEagerLoad($relations);
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testSetEagerLoadBadDataIsArray()
    {
        $foo = new TestMonomorphicSource();
        $relations = [[]];

        $expected = "Array to string conversion";
        $actual = null;

        try {
            $foo->setEagerLoad($relations);
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }
}
