<?php

declare(strict_types=1);

namespace AlgoWeb\PODataLaravel\Models\ObjectMap\Entities;

use POData\Providers\Metadata\Type\EdmPrimitiveType;
use POData\Providers\Metadata\Type\TypeCode;

/**
 * Class EntityField
 * @package AlgoWeb\PODataLaravel\Models\ObjectMap\Entities
 */
class EntityField
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var EntityFieldType;
     */
    private $fieldType;

    /**
     * @var bool
     */
    private $isNullable = false;

    /**
     * @var mixed
     */
    private $defaultValue;

    /**
     * @var bool
     */
    private $readOnly = false;

    /**
     * @var bool
     */
    private $createOnly = false;

    /**
     * @var bool
     */
    private $isKeyField = false;

    /**
     * @var EntityFieldPrimitiveType
     */
    private $primitiveType;
    /**
     * @var EdmPrimitiveType
     */
    private $edmFieldType;

    /**
     * @return EdmPrimitiveType
     */
    public function getEdmFieldType()
    {
        return $this->edmFieldType;
    }

    /**
     * @return \AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\EntityFieldPrimitiveType
     */
    public function getPrimitiveType()
    {
        return $this->primitiveType;
    }

    /**
     * @param \AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\EntityFieldPrimitiveType $primitiveType
     */
    public function setPrimitiveType(EntityFieldPrimitiveType $primitiveType): void
    {
        $this->primitiveType = $primitiveType;
        $rawType             = $this->primitiveTypeToEdmType($primitiveType);
        $this->edmFieldType  = 'stream' === $rawType ? $rawType : new EdmPrimitiveType($rawType);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name): void
    {
        $this->name = $name;
    }

    /**
     * @return EntityFieldType
     */
    public function getFieldType()
    {
        return $this->fieldType;
    }

    /**
     * @param EntityFieldType $fieldType
     */
    public function setFieldType(EntityFieldType $fieldType): void
    {
        $this->fieldType = $fieldType;
    }

    /**
     * @return bool
     */
    public function getIsNullable(): bool
    {
        return $this->isNullable;
    }

    /**
     * @param bool $isNullable
     */
    public function setIsNullable($isNullable): void
    {
        $this->isNullable = boolval($isNullable);
    }

    /**
     * @return mixed
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * @param mixed $defaultValue
     */
    public function setDefaultValue($defaultValue): void
    {
        $this->defaultValue = $defaultValue;
    }

    /**
     * @return bool
     */
    public function getReadOnly()
    {
        return $this->readOnly;
    }

    /**
     * @param bool $readOnly
     */
    public function setReadOnly($readOnly): void
    {
        $this->readOnly = boolval($readOnly);
    }

    /**
     * @return bool
     */
    public function getCreateOnly()
    {
        return $this->createOnly;
    }

    /**
     * @param bool $createOnly
     */
    public function setCreateOnly($createOnly): void
    {
        $this->createOnly = boolval($createOnly);
    }

    /**
     * @return bool
     */
    public function getIsKeyField()
    {
        return $this->isKeyField;
    }

    /**
     * @param bool $keyField
     */
    public function setIsKeyField($keyField): void
    {
        $this->isKeyField = boolval($keyField);
    }

    /**
     * @var array<string, int|string>
     */
    private static $primitiveToEdmMapping = [
        EntityFieldPrimitiveType::BINARY => EdmPrimitiveType::BINARY,
        EntityFieldPrimitiveType::BIGINT => EdmPrimitiveType::INT64,
        EntityFieldPrimitiveType::INTEGER => EdmPrimitiveType::INT32,
        EntityFieldPrimitiveType::STRING => EdmPrimitiveType::STRING,
        EntityFieldPrimitiveType::DATE => EdmPrimitiveType::DATETIME,
        EntityFieldPrimitiveType::DATETIME => EdmPrimitiveType::DATETIME,
        EntityFieldPrimitiveType::DATETIMETZ => EdmPrimitiveType::DATETIME,
        EntityFieldPrimitiveType::FLOAT => EdmPrimitiveType::SINGLE,
        EntityFieldPrimitiveType::DECIMAL => EdmPrimitiveType::DECIMAL,
        EntityFieldPrimitiveType::TEXT => EdmPrimitiveType::STRING,
        EntityFieldPrimitiveType::BOOLEAN => EdmPrimitiveType::BOOLEAN,
        EntityFieldPrimitiveType::BLOB => 'stream'
    ];

    /**
     * @param \AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\EntityFieldPrimitiveType $primitiveType
     *
     * @return int|string
     */
    private function primitiveTypeToEdmType(EntityFieldPrimitiveType $primitiveType)
    {
        $value = $primitiveType->getValue();

        return array_key_exists($value, self::$primitiveToEdmMapping) ?
            self::$primitiveToEdmMapping[$value] : EdmPrimitiveType::STRING;
    }
}
