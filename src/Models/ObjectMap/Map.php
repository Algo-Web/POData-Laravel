<?php
namespace AlgoWeb\PODataLaravel\Models\ObjectMap;


use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\Association;
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
    private $assocations;

    public function addEntity(EntityGubbins $entity)
    {
        if (!is_array($this->Entities)) {
            $this->Entities = [];
        }
        $this->Entities[$entity->getClassName()] = $entity;
    }

    public function getEntities()
    {
        return $this->Entities;
    }

    public function setEntities(array $entities)
    {
        $this->Entities = [];
        foreach ($entities as $entity) {
            $this->Entities[$entity->getClassName()] = $entity;
        }
    }

    public function setAssociations(array $assocations)
    {
        foreach ($assocations as $assocation) {
            $this->addAssociation($assocation);
        }
    }

    public function addAssociation(Association $assocations)
    {
        if (!is_array($this->assocations)) {
            $this->assocations = [];
        }
        $firstClass = $this->Entities[$assocations->getFirst()->getBaseType()];
        $secondClass = $this->Entities[$assocations->getLast()->getBaseType()];
        $firstClass->addAssociation($assocations);
        $secondClass->addAssociation($assocations, false);
        $this->assocations[] = $assocations;
    }

    public function getAssociations()
    {
        return $this->assocations;
    }

    public function isOK(&$msg)
    {
        foreach ($this->Entities as $entity) {
            $entity->isOK($msg);
        }
    }
}