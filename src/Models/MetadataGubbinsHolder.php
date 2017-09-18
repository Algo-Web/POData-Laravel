<?php

namespace AlgoWeb\PODataLaravel\Models;

class MetadataGubbinsHolder
{
    protected $relations = [];

    public function addEntity(EntityGubbins $entity)
    {
        $className = $entity->getClassName();
        if (array_key_exists($className, $this->relations)) {
            $msg = $className.' already added';
            throw new \InvalidArgumentException($msg);
        }
        $this->relations[$className] = $entity;
    }

    public function getRelationsByRelationName($className, $relName)
    {
        $this->checkClassExists($className);

        $rels = $this->relations[$className];

        if (!array_key_exists($relName, $rels->getStubs())) {
            $msg = 'Relation ' . $relName . ' not registered on ' . $className;
            throw new \InvalidArgumentException($msg);
        }
        $stub = $rels->getStubs()[$relName];
        $targType = $stub->getTargType();
        if (!array_key_exists($targType, $this->relations)) {
            return [];
        }
        $targRel = $this->relations[$targType];
        // now dig out compatible stubs on target type
        $targStubs = $targRel->getStubs();
        $relStubs = [];
        foreach ($targStubs as $targStub) {
            if ($stub->isCompatible($targStub)) {
                $relStubs[] = $targStub;
            }
        }
        return $relStubs;
    }

    public function getRelationsByClass($className)
    {
        $this->checkClassExists($className);

        $rels = $this->relations[$className];
        $stubs = $rels->getStubs();

        $associations = [];
        foreach ($stubs as $relName => $stub) {
            $others = $this->getRelationsByRelationName($className, $relName);
            if (1 === count($others)) {
                $others = $others[0];
                $assoc = new Association();
                $first = -1 === $stub->compare($others);
                $assoc->setFirst($first ? $stub : $others);
                $assoc->setLast($first ? $others : $stub);
                assert($assoc->isOk());
                $associations[] = $assoc;
            }
        }
        return $associations;
    }

    public function getRelations()
    {
        $classNames = array_keys($this->relations);

        $associations = [];

        foreach ($classNames as $class) {
            $rawAssoc = $this->getRelationsByClass($class);
            foreach ($rawAssoc as $raw) {
                if (!in_array($raw, $associations)) {
                    $associations[] = $raw;
                }
            }
        }

        return $associations;
    }

    /**
     * @param $className
     */
    protected function checkClassExists($className)
    {
        if (!array_key_exists($className, $this->relations)) {
            $msg = $className . ' does not exist in holder';
            throw new \InvalidArgumentException($msg);
        }
    }
}
