<?php

namespace AlgoWeb\PODataLaravel\Models;

use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationMonomorphic;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationPolymorphic;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubBase;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubPolymorphic;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\EntityField;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\EntityGubbins;
use Mockery as m;
use POData\Providers\Metadata\ResourceEntityType;

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
        $foo->setStubs([]);
        $this->assertEquals(0, count($foo->getStubs()));
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

    public function testSetAbstractODataType()
    {
        $foo = new EntityGubbins();
        $rType = m::mock(ResourceEntityType::class);
        $rType->shouldReceive('isAbstract')->andReturn(true)->once();

        $expected = 'OData resource entity type must be concrete';
        $actual = null;

        try {
            $foo->setOdataResourceType($rType);
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testFieldAndAssociationNamesIntersectNotOk()
    {
        $foo = m::mock(EntityGubbins::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getFieldNames')->andReturn(['field', 'overlap'])->once();
        $foo->shouldReceive('getAssociationNames')->andReturn(['overlap', 'relation'])->once();
        $this->assertFalse($foo->isOk());
    }

    public function testAddDisconnectedEmptyMonomorphicAssociation()
    {
        $foo = new EntityGubbins();
        $assoc = new AssociationMonomorphic();

        $expected = 'Association cannot be connected to this entity';
        $actual = null;

        try {
            $foo->addAssociation($assoc);
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testAddDisconnectedMonomorphicAssociation()
    {
        $foo = new EntityGubbins();
        $foo->setStubs([]);
        $assoc = m::mock(AssociationMonomorphic::class);
        $stub = m::mock(AssociationStubBase::class);
        $assoc->shouldReceive('getFirst')->andReturn($stub)->once();

        $expected = 'Association cannot be connected to this entity';
        $actual = null;

        try {
            $foo->addAssociation($assoc);
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testEmptyAssociationSet()
    {
        $foo = new EntityGubbins();
        $result = $foo->getAssociations();
        $this->assertTrue(is_array($result));
        $this->assertEquals(0, count($result));
    }

    public function testEmptyStubSet()
    {
        $foo = new EntityGubbins();
        $result = $foo->getStubs();
        $this->assertTrue(is_array($result));
        $this->assertEquals(0, count($result));
    }

    public function testEmptyKeyFieldsSet()
    {
        $foo = new EntityGubbins();
        $result = $foo->getKeyFields();
        $this->assertTrue(is_array($result));
        $this->assertEquals(0, count($result));
    }
}
