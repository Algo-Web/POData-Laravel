<?php
namespace AlgoWeb\PODataLaravel\Models\ObjectMap;

use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\Association;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationMonomorphic;
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
    private $associations = [];

    /**
     * @param AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\EntityGubbins|EntityGubbins $entity
     *
     * @return void
     */
    public function addEntity(EntityGubbins $entity)
    {
        $this->entities[$entity->getClassName()] = $entity;
    }

    /**
     * @return AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\EntityGubbins[]|array
     */
    public function getEntities()
    {
        return $this->entities;
    }

    /**
     * @param string $entityClassName
     *
     * @return AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\EntityGubbins
     */
    public function resolveEntity($entityClassName)
    {
        return array_key_exists($entityClassName, $this->entities) ? $this->entities[$entityClassName] : null;
    }

    /**
     * @param AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\EntityGubbins[]|array|string[] $entities
     *
     * @return void
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
     * @param AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationMonomorphic[]|array $associations
     *
     * @return void
     */
    public function setAssociations(array $associations)
    {
        foreach ($associations as $association) {
            $this->addAssociation($association);
        }
    }

    /**
     * @param AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationMonomorphic|Association|Mockery_28_AlgoWeb_PODataLaravel_Models_ObjectMap_Entities_Associations_Association $association
     *
     * @return void
     */
    public function addAssociation(Association $association)
    {
        if ($association instanceof AssociationMonomorphic) {
            $this->addAssociationMonomorphic($association);
        } else {
            throw new \InvalidArgumentException('Association type not yet handled');
        }
        $this->associations[] = $association;
    }

    /**
     * @return AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationMonomorphic[]|array
     */
    public function getAssociations()
    {
        return $this->associations;
    }

    /**
     * @return void
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
     * @param AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationMonomorphic|AssociationMonomorphic $association
     *
     * @return void
     */
    private function addAssociationMonomorphic(AssociationMonomorphic $association)
    {
        $firstClass = $this->entities[$association->getFirst()->getBaseType()];
        $secondClass = $this->entities[$association->getLast()->getBaseType()];
        $firstClass->addAssociation($association);
        $secondClass->addAssociation($association, false);
    }
}
