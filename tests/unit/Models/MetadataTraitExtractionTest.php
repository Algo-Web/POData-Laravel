<?php

namespace AlgoWeb\PODataLaravel\Models;

use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubPolymorphic;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubRelationType as RelType;

class MetadataTraitExtractionTest extends TestCase
{
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

        $expected = "Property names must be unique, without regard to case";
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

        $expected = "Property names must be unique, without regard to case";
        $actual = null;

        try {
            $result = $foo->extractGubbins();
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }
}
