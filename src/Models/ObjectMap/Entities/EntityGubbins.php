<?php

namespace AlgoWeb\PODataLaravel\Models\ObjectMap\Entities;

use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\Association;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubBase;

class EntityGubbins
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $className;

    /**
     * @var array
     */
    private $keyFields;

    /**
     * @var array
     */
    private $fields;

    /**
     * @var AssociationStubBase[]
     */
    private $stubs;

    /**
     * @var Association[]
     */
    private $associations;

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
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * @param string $className
     */
    public function setClassName($className)
    {
        $this->className = $className;
    }

    /**
     * @return array
     */
    public function getKeyFields()
    {
        return $this->keyFields;
    }

    /**
     * @return mixed
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @param array $fields
     */
    public function setFields(array $fields)
    {
        if (0 == count($fields)) {
            $msg = 'Fields array must not be empty';
            throw new \Exception($msg);
        }
        $keys = [];
        foreach ($fields as $propName => $field) {
            if (!$field instanceof EntityField) {
                $msg = 'Fields array must only have EntityField objects';
                throw new \Exception($msg);
            }
            if ($field->getIsKeyField()) {
                $keys[$propName] = $field;
            }
        }
        if (0 == count($keys)) {
            $msg = 'No key field supplied in fields array';
            throw new \Exception($msg);
        }
        $this->fields = $fields;
        $this->keyFields = $keys;
    }

    /**
     * @return AssociationStubBase[]
     */
    public function getStubs()
    {
        return $this->stubs;
    }

    /**
     * @param AssociationStubBase[] $stubs
     */
    public function setStubs($stubs)
    {
        if (0 == count($stubs)) {
            $msg = 'Stubs array must not be empty';
            throw new \Exception($msg);
        }
        foreach ($stubs as $field) {
            if (!$field instanceof AssociationStubBase) {
                $msg = 'Stubs array must only have AssociationStubBase objects';
                throw new \Exception($msg);
            }
        }
        $this->stubs = $stubs;
    }

    public function addAssociation(Association $association, $isFirst = true)
    {
        $stub = $isFirst ? $association->getFirst() : $association->getLast();
        if (!in_array($stub, $this->stubs)) {
            throw new \InvalidArgumentException('Association cannot be connected to this entity');
        }
        $propertyName = $stub->getRelationName();
        $this->associations[$propertyName] = $association;
    }

}
