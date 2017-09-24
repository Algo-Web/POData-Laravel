<?php

namespace AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations;

class AssociationMonomorphic extends Association
{

    /**
     * @var AssociationStubBase
     */
    protected $last;

    /**
     * @return AssociationStubBase
     */
    public function getLast()
    {
        return $this->last;
    }

    /**
     * @param AssociationStubBase $last
     */
    public function setLast(AssociationStubBase $last)
    {
        $this->last = $last;
    }

    /**
     * @return bool
     */
    public function isOk()
    {
        $first = $this->getFirst();
        $last = $this->getLast();
        if (null === $first || !$first->isOk()) {
            return false;
        }
        if (null === $last || !$last->isOk()) {
            return false;
        }
        if (!$first->isCompatible($last)) {
            return false;
        }
        return -1 === $first->compare($last);
    }

    /**
     * @return \AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationType
     */
    public function getAssociationType()
    {
        return new AssociationType($this->first->getMultiplicity()->getValue()
                                    | $this->last->getMultiplicity()->getValue());
    }

    /**
     * return array[].
     */
    public function getArrayPayload()
    {
        $principalType = $this->first->getBaseType();
        $principalProp = $this->first->getRelationName();
        $principalRSet = $principalType;
        $principalMult = $this->multArray[$this->first->getMultiplicity()->getValue()];

        $dependentType = $this->last->getBaseType();
        $dependentProp = $this->last->getRelationName();
        $dependentRSet = $dependentType;
        $dependentMult = $this->multArray[$this->last->getMultiplicity()->getValue()];

        $forward = [
            'principalType' => $principalType,
            'principalMult' => $principalMult,
            'principalProp' => $principalProp,
            'principalRSet' => $principalType,
            'dependentType' => $dependentType,
            'dependentMult' => $dependentMult,
            'dependentProp' => $dependentProp,
            'dependentRSet' => $dependentType
        ];
        $reverse = [
            'principalType' => $dependentType,
            'principalMult' => $dependentMult,
            'principalProp' => $dependentProp,
            'principalRSet' => $dependentType,
            'dependentType' => $principalType,
            'dependentMult' => $principalMult,
            'dependentProp' => $principalProp,
            'dependentRSet' => $principalType
        ];
        $payload = [$forward, $reverse];

        return $payload;
    }
}
