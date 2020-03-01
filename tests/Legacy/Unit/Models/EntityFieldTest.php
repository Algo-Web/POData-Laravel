<?php

declare(strict_types=1);

namespace Tests\Legacy\AlgoWeb\PODataLaravel\Unit\Models;

use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\EntityField;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\EntityFieldPrimitiveType;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\EntityFieldType;
use POData\Providers\Metadata\Type\EdmPrimitiveType;
use Tests\Legacy\AlgoWeb\PODataLaravel\TestCase;

class EntityFieldTest extends TestCase
{
    public function testGettersSetters()
    {
        $foo          = new EntityField();
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
        $foo  = new EntityField();
        $foo->setPrimitiveType($type);
        $result = $foo->getEdmFieldType();
        $this->assertEquals(EdmPrimitiveType::INT32(), $result);
    }

    public function testSetDatePrimitiveType()
    {
        $type = EntityFieldPrimitiveType::DATE();
        $foo  = new EntityField();
        $foo->setPrimitiveType($type);
        $result = $foo->getEdmFieldType();
        $this->assertEquals(EdmPrimitiveType::DATETIME(), $result);
    }

    public function testSetDateTimeTzPrimitiveType()
    {
        $type = EntityFieldPrimitiveType::DATETIMETZ();
        $foo  = new EntityField();
        $foo->setPrimitiveType($type);
        $result = $foo->getEdmFieldType();
        $this->assertEquals(EdmPrimitiveType::DATETIME(), $result);
    }

    public function testSetBigIntPrimitiveType()
    {
        $type = EntityFieldPrimitiveType::BIGINT();
        $foo  = new EntityField();
        $foo->setPrimitiveType($type);
        $result = $foo->getEdmFieldType();
        $this->assertEquals(EdmPrimitiveType::INT64(), $result);
    }

    public function testSetBinaryPrimitiveType()
    {
        $type = EntityFieldPrimitiveType::BINARY();
        $foo  = new EntityField();
        $foo->setPrimitiveType($type);
        $result = $foo->getEdmFieldType();
        $this->assertEquals(EdmPrimitiveType::BINARY(), $result);
    }

    public function testSetTextPrimitiveType()
    {
        $type = EntityFieldPrimitiveType::TEXT();
        $foo  = new EntityField();
        $foo->setPrimitiveType($type);
        $result = $foo->getEdmFieldType();
        $this->assertEquals(EdmPrimitiveType::STRING(), $result);
    }
}
