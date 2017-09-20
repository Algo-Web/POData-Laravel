<?php

namespace AlgoWeb\PODataLaravel\Models\ObjectMap\Entities;

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
    private $isNullable;

    /**
     * @var mixed
     */
    private $defaultValue;

    /**
     * @var bool
     */
    private $readOnly;

    /**
     * @var bool
     */
    private $createOnly;

    /**
     * @var bool
     */
    private $isKeyField;

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
    public function setName($name)
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
    public function setFieldType(EntityFieldType $fieldType)
    {
        $this->fieldType = $fieldType;
    }

    /**
     * @return bool
     */
    public function getIsNullable()
    {
        return $this->isNullable;
    }

    /**
     * @param bool $isNullable
     */
    public function setIsNullable($isNullable)
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
    public function setDefaultValue($defaultValue)
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
    public function setReadOnly($readOnly)
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
    public function setCreateOnly($createOnly)
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
    public function setIsKeyField($keyField)
    {
        $this->isKeyField = boolval($keyField);
    }
}
