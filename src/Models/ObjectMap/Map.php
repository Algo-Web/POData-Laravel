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
    private $Entities;

    /**
     * @var Association[]
     */
    private $associations;

    /**
     * @param EntityGubbins $entity
     */
    public function addEntity(EntityGubbins $entity)
    {
        if (!is_array($this->Entities)) {
            $this->Entities = [];
        }
        $this->Entities[$entity->getClassName()] = $entity;
    }

    /**
     * @return EntityGubbins[]
     */
    public function getEntities()
    {
        return $this->Entities;
    }

    /**
     * @param EntityGubbins[] $entities
     */
    public function setEntities(array $entities)
    {
        $this->Entities = [];
        foreach ($entities as $entity) {
            if (!$entity instanceof EntityGubbins) {
                throw new \InvalidArgumentException('Entities array must contain only EntityGubbins objects');
            }
        }
        foreach ($entities as $entity) {
            $this->Entities[$entity->getClassName()] = $entity;
        }
    }

    /**
     * @param Association[] $assocations
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
        foreach ($this->Entities as $entity) {
            $entity->isOK();
        }
    }

    /**
     * @param AssociationMonomorphic $association
     */
    private function addAssociationMonomorphic(AssociationMonomorphic $association)
    {
        $firstClass = $this->Entities[$association->getFirst()->getBaseType()];
        $secondClass = $this->Entities[$association->getLast()->getBaseType()];
        $firstClass->addAssociation($association);
        $secondClass->addAssociation($association, false);
    }

    /**
     * @param AssociationPolymorphic $association
     */
    private function addAssociationPolymorphic(AssociationPolymorphic $association)
    {
        $firstClass = $this->Entities[$association->getFirst()->getBaseType()];
        $firstClass->addAssociation($association);
        foreach ($association->getLast() as $last) {
            $secondClass = $this->Entities[$last->getBaseType()];
            $secondClass->addAssociation($association, false);
        }
    }
}
