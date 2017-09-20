<?php

namespace AlgoWeb\PODataLaravel\Models;

use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubPolymorphic;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\EntityField;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\EntityGubbins;

class EntityGubbinsTest extends TestCase
{
    public function testSimpleGettersAndSetters()
    {
        $foo = new EntityGubbins();
        $expectedName = 'name';
        $foo->setName($expectedName);
        $actualName = $foo->getName();
        $this->assertEquals($expectedName, $actualName);
        $expectedClass = 'class';
        $foo->setClassName($expectedClass);
        $actualClass = $foo->getClassName();
        $this->assertEquals($expectedClass, $actualClass);
    }

    public function testSetEmptyFieldsArray()
    {
        $foo = new EntityGubbins();
        $expected = 'Fields array must not be empty';
        $actual = null;

        try {
            $foo->setFields([]);
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testSetBadFieldsArray()
    {
        $foo = new EntityGubbins();
        $expected = 'Fields array must only have EntityField objects';
        $actual = null;

        try {
            $foo->setFields([new \DateTime()]);
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testSetNoKeyFieldsArray()
    {
        $foo = new EntityGubbins();
        $expected = 'No key field supplied in fields array';
        $actual = null;

        $field = new EntityField();
        $this->assertTrue(!$field->getIsKeyField());

        try {
            $foo->setFields([$field]);
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testSetOneKeyFieldArray()
    {
        $foo = new EntityGubbins();
        $field = new EntityField();
        $field->setIsKeyField(true);
        $bar = new EntityField();

        $foo->setFields([$field, $bar]);
        $this->assertEquals(1, count($foo->getKeyFields()));
        $this->assertEquals(2, count($foo->getFields()));
    }

    public function testSetEmptyStubsArray()
    {
        $foo = new EntityGubbins();
        $expected = 'Stubs array must not be empty';
        $actual = null;

        try {
            $foo->setStubs([]);
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testSetBadStubsArray()
    {
        $foo = new EntityGubbins();
        $expected = 'Stubs array must only have AssociationStubBase objects';
        $actual = null;

        try {
            $foo->setStubs([new \DateTime()]);
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testSetGoodStubsArray()
    {
        $foo = new EntityGubbins();
        $stub = new AssociationStubPolymorphic();

        $foo->setStubs([$stub]);
        $this->assertEquals(1, count($foo->getStubs()));
    }
}
