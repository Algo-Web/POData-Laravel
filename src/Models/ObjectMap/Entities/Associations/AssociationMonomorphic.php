<?php

namespace AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations;

class AssociationMonomorphic extends Association
{

    /**
     * @var AssociationStubBase
     */
    protected $last;

    /**
     * @return AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubMonomorphic|AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubPolymorphic|Mockery_12_AlgoWeb_PODataLaravel_Models_ObjectMap_Entities_Associations_AssociationStubBase|null
     */
    public function getLast()
    {
        return $this->last;
    }

    /**
     * @param AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubMonomorphic|AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubPolymorphic|AssociationStubBase|Mockery_12_AlgoWeb_PODataLaravel_Models_ObjectMap_Entities_Associations_AssociationStubBase $last
     *
     * @return void
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
     * @return AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationType
     */
    public function getAssociationType()
    {
        return new AssociationType($this->first->getMultiplicity()->getValue()
                                    | $this->last->getMultiplicity()->getValue());
    }
}
