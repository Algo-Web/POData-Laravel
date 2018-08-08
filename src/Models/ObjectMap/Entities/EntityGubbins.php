<?php

namespace AlgoWeb\PODataLaravel\Models\ObjectMap\Entities;

use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\Association;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationMonomorphic;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubBase;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubMonomorphic;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubPolymorphic;
use POData\Common\InvalidOperationException;
use POData\Providers\Metadata\ResourceEntityType;

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
     * @var EntityField[]
     */
    private $keyFields = [];

    /**
     * @var EntityField[]
     */
    private $fields = [];

    /**
     * @var AssociationStubBase[]
     */
    private $stubs = [];

    /**
     * @var Association[]
     */
    private $associations = [];
    /**
     * @var ResourceEntityType
     */
    private $odataResourceType;

    /**
     * @return POData\Providers\Metadata\ResourceEntityType
     */
    public function getOdataResourceType()
    {
        return $this->odataResourceType;
    }

    /**
     * @param Mockery_13_POData_Providers_Metadata_ResourceEntityType|POData\Providers\Metadata\ResourceEntityType|ResourceEntityType $odataType
     *
     * @return void
     */
    public function setOdataResourceType(ResourceEntityType $odataType)
    {
        if ($odataType->isAbstract()) {
            $msg = 'OData resource entity type must be concrete';
            throw new \InvalidArgumentException($msg);
        }
        $this->odataResourceType = $odataType;
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
     *
     * @return void
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string|null
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * @param string $className
     *
     * @return void
     */
    public function setClassName($className)
    {
        $this->className = $className;
    }

    /**
     * @return AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\EntityField[]|array
     */
    public function getKeyFields()
    {
        return $this->keyFields;
    }

    /**
     * @return AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\EntityField[]|array
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @param AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\EntityField[]|DateTime[]|array $fields
     *
     * @return void
     */
    public function setFields(array $fields)
    {
        if (0 == count($fields)) {
            $msg = 'Fields array must not be empty for '.$this->getClassName();
            throw new \Exception($msg);
        }
        $keys = [];
        foreach ($fields as $propName => $field) {
            if (!$field instanceof EntityField) {
                $msg = 'Fields array must only have EntityField objects for '.$this->getClassName();
                throw new \Exception($msg);
            }
            if ($field->getIsKeyField()) {
                $keys[$propName] = $field;
            }
        }
        if (0 == count($keys)) {
            $msg = 'No key field supplied in fields array for '.$this->getClassName();
            throw new \Exception($msg);
        }
        $this->fields = $fields;
        $this->keyFields = $keys;
    }

    /**
     * @return AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubMonomorphic[]|AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubPolymorphic[]|array
     */
    public function getStubs()
    {
        return $this->stubs;
    }

    /**
     * @param AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubMonomorphic[]|AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubPolymorphic[]|DateTime[]|array $stubs
     *
     * @return void
     */
    public function setStubs(array $stubs)
    {
        foreach ($stubs as $field) {
            if (!$field instanceof AssociationStubBase) {
                $msg = 'Stubs array must only have AssociationStubBase objects';
                throw new \Exception($msg);
            }
        }
        $this->stubs = $stubs;
    }

    /**
     * @param AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationMonomorphic|Association|Mockery_15_AlgoWeb_PODataLaravel_Models_ObjectMap_Entities_Associations_AssociationMonomorphic $association
     * @param bool                                                                                                                                                                                           $isFirst
     *
     * @return void
     */
    public function addAssociation(Association $association, $isFirst = true)
    {
        if ($association instanceof AssociationMonomorphic) {
            $stub = $isFirst ? $association->getFirst() : $association->getLast();
            if (null === $stub || (!in_array($stub, $this->stubs) && !($stub instanceof AssociationStubPolymorphic))) {
                throw new \InvalidArgumentException('Association cannot be connected to this entity');
            }
            $propertyName = $stub->getRelationName();
        }
        if (!isset($propertyName)) {
            throw new InvalidOperationException('');
        }
        $this->associations[$propertyName] = $association;
    }

    /**
     * @return string[]
     */
    protected function getFieldNames()
    {
        $fieldNames = [];
        foreach ($this->fields as $field) {
            $fieldNames[] = $field->getName();
        }
        return $fieldNames;
    }

    /**
     * @return array|string[]
     */
    protected function getAssociationNames()
    {
        if (empty($this->stubs)) {
            return [];
        }
        $assocationNames = [];
        foreach ($this->stubs as $stub) {
            $assocationNames[] = $stub->getRelationName();
        }
        return $assocationNames;
    }

    /**
     * @return AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationMonomorphic[]|array
     */
    public function getAssociations()
    {
        return $this->associations;
    }

    /**
     * @param string $relName
     *
     * @return AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationMonomorphic|null
     */
    public function resolveAssociation($relName)
    {
        return array_key_exists($relName, $this->associations) ? $this->associations[$relName] : null;
    }

    /**
     * @return bool
     */
    public function isOK()
    {
        $fieldNames = $this->getFieldNames();
        $associationNames = $this->getAssociationNames();
        $intersection = array_intersect($fieldNames, $associationNames);
        return 0 === count($intersection);
    }
}
