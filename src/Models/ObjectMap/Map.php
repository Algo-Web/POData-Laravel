<?php

declare(strict_types=1);
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
     * @param EntityGubbins $entity
     */
    public function addEntity(EntityGubbins $entity): void
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

    public function resolveEntity(string $entityClassName): ?EntityGubbins
    {
        return array_key_exists($entityClassName, $this->entities) ? $this->entities[$entityClassName] : null;
    }

    /**
     * @param EntityGubbins[] $entities
     */
    public function setEntities(array $entities): void
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
     * @param  Association[]                            $associations
     * @throws \POData\Common\InvalidOperationException
     */
    public function setAssociations(array $associations): void
    {
        foreach ($associations as $association) {
            $this->addAssociation($association);
        }
    }

    /**
     * @param  Association                              $association
     * @throws \POData\Common\InvalidOperationException
     */
    public function addAssociation(Association $association): void
    {
        if ($association instanceof AssociationMonomorphic) {
            $this->addAssociationMonomorphic($association);
        } else {
            throw new \InvalidArgumentException('Association type not yet handled');
        }
        $this->associations[] = $association;
    }

    /**
     * @return Association[]
     */
    public function getAssociations(): array
    {
        return $this->associations;
    }

    /**
     * @return bool
     */
    public function isOK(): bool
    {
        foreach ($this->entities as $entity) {
            if (!$entity->isOK()) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param  AssociationMonomorphic                   $association
     * @throws \POData\Common\InvalidOperationException
     */
    private function addAssociationMonomorphic(AssociationMonomorphic $association): void
    {
        $firstClass  = $this->entities[$association->getFirst()->getBaseType()];
        $secondClass = $this->entities[$association->getLast()->getBaseType()];
        $firstClass->addAssociation($association);
        $secondClass->addAssociation($association, false);
    }
}
