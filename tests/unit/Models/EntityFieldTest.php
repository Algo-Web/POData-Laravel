<?php

namespace AlgoWeb\PODataLaravel\Models;

use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\EntityField;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\EntityFieldPrimitiveType;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\EntityFieldType;
use POData\Providers\Metadata\Type\EdmPrimitiveType;

class EntityFieldTest extends TestCase
{
    public function testGettersSetters()
    {
        $foo = new EntityField();
        $expectedName = 'name';
        $foo->setName($expectedName);
        $actualName = $foo->getName();
        $this->assertEquals($expectedName, $actualName);
        $expectedFieldType = EntityFieldType::PRIMITIVE();
        $foo->setFieldType($expectedFieldType);
        $actualFieldType = $foo->getFieldType();
        $this->assertEquals($expectedFieldType, $actualFieldType);
        $expectedNullable = true;
        $foo->setIsNullable($expectedName);
        $actualNullable = $foo->getIsNullable();
        $this->assertEquals($expectedNullable, $actualNullable);
        $expectedReadOnly = true;
        $foo->setReadOnly($expectedName);
        $actualReadOnly = $foo->getReadOnly();
        $this->assertEquals($expectedReadOnly, $actualReadOnly);
        $expectedCreateOnly = true;
        $foo->setCreateOnly($expectedName);
        $actualCreateOnly = $foo->getCreateOnly();
        $this->assertEquals($expectedCreateOnly, $actualCreateOnly);

        $expectedDefault = new \DateTime();
        $foo->setDefaultValue($expectedDefault);
        $actualDefault = $foo->getDefaultValue();
        $this->assertEquals($expectedDefault, $actualDefault);

        $expectedIsKeyField = true;
        $foo->setIsKeyField($expectedName);
        $actualIsKeyField = $foo->getIsKeyField();
        $this->assertEquals($expectedIsKeyField, $actualIsKeyField);
    }

    public function testSetKnownPrimitiveType()
    {
        $type = EntityFieldPrimitiveType::INTEGER();
        $foo = new EntityField();
        $foo->setPrimitiveType($type);
        $result = $foo->getEdmFieldType();
        $this->assertEquals(EdmPrimitiveType::INT32, $result);
    }
}
