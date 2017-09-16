<?php

namespace AlgoWeb\PODataLaravel\Models;

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
     * @return boolean
     */
    public function getIsNullable()
    {
        return $this->isNullable;
    }

    /**
     * @param boolean $isNullable
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
     * @return boolean
     */
    public function getReadOnly()
    {
        return $this->readOnly;
    }

    /**
     * @param boolean $readOnly
     */
    public function setReadOnly($readOnly)
    {
        $this->readOnly = boolval($readOnly);
    }

    /**
     * @return boolean
     */
    public function getCreateOnly()
    {
        return $this->createOnly;
    }

    /**
     * @param boolean $createOnly
     */
    public function setCreateOnly($createOnly)
    {
        $this->createOnly = boolval($createOnly);
    }

    /**
     * @return boolean
     */
    public function getIsKeyField()
    {
        return $this->isKeyField;
    }

    /**
     * @param boolean $createOnly
     */
    public function setIsKeyField($keyField)
    {
        $this->isKeyField = boolval($keyField);
    }
}
