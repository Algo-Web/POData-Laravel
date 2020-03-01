<?php declare(strict_types=1);

namespace AlgoWeb\PODataLaravel\Models\ObjectMap\Entities;

use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\Association;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationMonomorphic;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubBase;
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
     * @return ResourceEntityType
     */
    public function getOdataResourceType() : ?ResourceEntityType
    {
        return $this->odataResourceType;
    }

    /**
     * @param ResourceEntityType $odataType
     */
    public function setOdataResourceType(ResourceEntityType $odataType) : void
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
    public function getName() : ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name) : void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getClassName() : ?string
    {
        return $this->className;
    }

    /**
     * @param string $className
     */
    public function setClassName($className) : void
    {
        $this->className = $className;
    }

    /**
     * @return EntityField[]
     */
    public function getKeyFields() : array
    {
        return $this->keyFields;
    }

    /**
     * @return EntityField[]
     */
    public function getFields() : array
    {
        return $this->fields;
    }

    /**
     * @param  EntityField[] $fields
     * @throws \Exception
     */
    public function setFields(array $fields) : void
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
        $this->fields    = $fields;
        $this->keyFields = $keys;
    }

    /**
     * @return AssociationStubBase[]
     */
    public function getStubs() : array
    {
        return $this->stubs;
    }

    /**
     * @param  AssociationStubBase[] $stubs
     * @throws \Exception
     */
    public function setStubs(array $stubs) : void
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
     * @param  Association               $association
     * @param  bool                      $isFirst
     * @throws InvalidOperationException
     */
    public function addAssociation(Association $association, $isFirst = true) : void
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
     * @return String[]
     */
    protected function getFieldNames() : array
    {
        $fieldNames = [];
        foreach ($this->fields as $field) {
            $fieldNames[] = $field->getName();
        }
        return $fieldNames;
    }

    /**
     * @return String[]
     */
    protected function getAssociationNames() : array
    {
        $associationNames = [];
        foreach ($this->stubs as $stub) {
            $associationNames[] = $stub->getRelationName();
        }
        return $associationNames;
    }

    /**
     * @return Associations\Association[]
     */
    public function getAssociations() : array
    {
        return $this->associations;
    }

    /**
     * @param $relName
     * @return Association|null
     */
    public function resolveAssociation($relName) : ?Association
    {
        return array_key_exists($relName, $this->associations) ? $this->associations[$relName] : null;
    }

    /**
     * @return bool
     */
    public function isOK() : bool
    {
        $fieldNames       = $this->getFieldNames();
        $associationNames = $this->getAssociationNames();
        $intersection     = array_intersect($fieldNames, $associationNames);
        return 0 === count($intersection);
    }
}
