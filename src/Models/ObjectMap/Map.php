<?php
namespace AlgoWeb\PODataLaravel\Models\ObjectMap;

use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\Association;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationMonomorphic;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationPolymorphic;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\EntityGubbins;

class Map
{
    /**
     * @var EntityGubbins[]
     */
    private $entities = [];

    /**
     * @var Association[]
     */
    private $associations;

    /**
     * @param EntityGubbins $entity
     */
    public function addEntity(EntityGubbins $entity)
    {
        $this->entities[$entity->getClassName()] = $entity;
    }

    /**
     * @return EntityGubbins[]
     */
    public function getEntities()
    {
        return $this->entities;
    }

    public function resolveEntity($entityClassName)
    {
        return array_key_exists($entityClassName, $this->entities) ? $this->entities[$entityClassName] : null;
    }

    /**
     * @param EntityGubbins[] $entities
     */
    public function setEntities(array $entities)
    {
        $this->entities = [];
        foreach ($entities as $entity) {
            if (!$entity instanceof EntityGubbins) {
                throw new \InvalidArgumentException('Entities array must contain only EntityGubbins objects');
            }
        }
        foreach ($entities as $entity) {
            $this->entities[$entity->getClassName()] = $entity;
        }
    }

    /**
     * @param Association[] $associations
     */
    public function setAssociations(array $associations)
    {
        foreach ($associations as $association) {
            $this->addAssociation($association);
        }
    }

    /**
     * @param Association $association
     */
    public function addAssociation(Association $association)
    {
        if (!is_array($this->associations)) {
            $this->associations = [];
        }
        if ($association instanceof AssociationMonomorphic) {
            $this->addAssociationMonomorphic($association);
        } elseif ($association instanceof AssociationPolymorphic) {
            $this->addAssociationPolymorphic($association);
        } else {
            throw new \InvalidArgumentException('Association type not yet handled');
        }
        $this->associations[] = $association;
    }

    /**
     * @return Association[]
     */
    public function getAssociations()
    {
        return $this->associations;
    }

    /**
     * @return bool
     */
    public function isOK()
    {
        foreach ($this->entities as $entity) {
            if (!$entity->isOK()) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param AssociationMonomorphic $association
     */
    private function addAssociationMonomorphic(AssociationMonomorphic $association)
    {
        $firstClass = $this->entities[$association->getFirst()->getBaseType()];
        $secondClass = $this->entities[$association->getLast()->getBaseType()];
        $firstClass->addAssociation($association);
        $secondClass->addAssociation($association, false);
    }

    /**
     * @param AssociationPolymorphic $association
     */
    private function addAssociationPolymorphic(AssociationPolymorphic $association)
    {
        $firstClass = $this->entities[$association->getFirst()->getBaseType()];
        $firstClass->addAssociation($association);
        foreach ($association->getLast() as $last) {
            $secondClass = $this->entities[$last->getBaseType()];
            $secondClass->addAssociation($association, false);
        }
    }
}
