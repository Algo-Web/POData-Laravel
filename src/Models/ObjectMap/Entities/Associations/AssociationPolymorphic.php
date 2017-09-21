<?php

namespace AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations;

use AlgoWeb\PODataLaravel\Providers\MetadataProvider;

class AssociationPolymorphic extends Association
{
    /**
     * @var AssociationStubBase[]
     */
    protected $last = [];

    /**
     * @return AssociationStubBase|AssociationStubBase[]
     */
    public function getLast()
    {
        return $this->last;
    }

    public function setLast(array $stubs)
    {
        $this->last = $stubs;
    }

    /**
     * @return bool
     */
    public function isOk()
    {
        $first = $this->getFirst();
        $last = $this->getLast();
        if (!$this->isStubOk($first)) {
            return false;
        }
        if (0 == count($this->last)) {
            return false;
        }
        foreach ($last as $stub) {
            if (!$this->isStubOk($stub)) {
                return false;
            }
            if (!$first->isCompatible($stub)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @return AssociationType|AssociationType[]
     */
    public function getAssociationType()
    {
        assert($this->isOk());
        $types = [];
        $first = $this->getFirst();
        $firstMult = $first->getMultiplicity()->getValue();
        $last = $this->getLast();
        foreach ($last as $stub) {
            $types[] = new AssociationType($firstMult | $stub->getMultiplicity()->getValue());
        }

        return $types;
    }

    private function isStubOk($stub)
    {
        return (null !== $stub && $stub instanceof AssociationStubPolymorphic && $stub->isOk());
    }

    /**
     * return array[]
     */
    public function getArrayPayload()
    {
        $placeholder = MetadataProvider::POLYMORPHIC;

        $principalType = $this->first->getBaseType();
        $principalProp = $this->first->getRelationName();
        $principalRSet = $principalType;
        $principalMult = $this->multArray[$this->first->getMultiplicity()->getValue()];
        $numLast = count($this->last);

        $dependentRSet = $placeholder;

        $result = [];

        foreach ($this->last as $last) {
            $dependentMult = $this->multArray[$last->getMultiplicity()->getValue()];
            $dependentProp = $last->getRelationName();
            $dependentType = $last->getBaseType();

            $forward = [
                'principalType' => $principalType,
                'principalMult' => $principalMult,
                'principalProp' => $principalProp,
                'principalRSet' => $principalRSet,
                'dependentType' => $dependentType,
                'dependentMult' => $dependentMult,
                'dependentProp' => $dependentProp,
                'dependentRSet' => $dependentRSet
            ];
            $reverse = [
                'principalType' => $dependentType,
                'principalMult' => $dependentMult,
                'principalProp' => $dependentProp,
                'principalRSet' => $dependentRSet,
                'dependentType' => $principalType,
                'dependentMult' => $principalMult,
                'dependentProp' => $principalProp,
                'dependentRSet' => $principalRSet
            ];
            $result[] = $forward;
            $result[] = $reverse;
        }

        assert(count($result) == 2 * $numLast);
        return $result;
    }
}
