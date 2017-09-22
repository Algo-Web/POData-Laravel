<?php

namespace AlgoWeb\PODataLaravel\Models;

use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\Association;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationMonomorphic;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationPolymorphic;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubMonomorphic;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubPolymorphic;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\EntityGubbins;

class MetadataGubbinsHolder
{
    protected $relations = [];
    protected $knownSides = [];

    public function addEntity(EntityGubbins $entity)
    {
        $className = $entity->getClassName();
        if (array_key_exists($className, $this->relations)) {
            $msg = $className.' already added';
            throw new \InvalidArgumentException($msg);
        }
        $this->relations[$className] = $entity;
        $this->knownSides[$className] = [];
        foreach ($entity->getStubs() as $relName => $stub) {
            if ($stub instanceof AssociationStubPolymorphic && $stub->isKnownSide()) {
                $this->knownSides[$className][$relName] = $stub;
            }
        }
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
                $assoc = new AssociationMonomorphic();
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

        $unknowns = [];
        foreach ($this->knownSides as $knownType => $knownDeets) {
            $unknowns[$knownType] = [];
            foreach (array_keys($knownDeets) as $key) {
                $unknowns[$knownType][$key] = [];
            }
        }
        $monoAssoc = [];
        $polyAssoc = [];
        foreach ($associations as $assoc) {
            if ($assoc->getFirst() instanceof AssociationStubMonomorphic) {
                $monoAssoc[] = $assoc;
                continue;
            }
            // monomorphic associations are dealt with, now for the polymorphic associations - they're a mite trickier
            $firstKnown = $assoc->getFirst()->isKnownSide();
            $known = $firstKnown ? $assoc->getFirst() : $assoc->getLast();
            $unknown = $firstKnown ? $assoc->getLast() : $assoc->getFirst();
            $className = $known->getBaseType();
            $relName = $known->getRelationName();
            $unknowns[$className][$relName][] = $unknown;
        }

        foreach ($this->knownSides as $knownType => $knownDeets) {
            foreach (array_keys($knownDeets) as $key) {
                $lastCandidates = $unknowns[$knownType][$key];
                if (0 == count($lastCandidates)) {
                    continue;
                }
                $assoc = new AssociationPolymorphic();
                $assoc->setFirst($this->knownSides[$knownType][$key]);
                $assoc->setLast($lastCandidates);
                assert($assoc->isOk());
                $polyAssoc[] = $assoc;
            }
        }
        $result = array_merge($monoAssoc, $polyAssoc);

        return $result;
    }

    public function hasClass($className)
    {
        return array_key_exists($className, $this->relations);
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
