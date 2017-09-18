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
