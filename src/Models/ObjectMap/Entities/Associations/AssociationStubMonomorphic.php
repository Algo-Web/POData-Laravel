<?php

namespace AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations;

class AssociationStubMonomorphic extends AssociationStubBase
{
    /**
     * @param \AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubBase $otherStub
     *
     * @return bool
     */
    public function isCompatible(AssociationStubBase $otherStub)
    {
        if (!parent::isCompatible($otherStub)) {
            return false;
        }
        if (null == $this->getThroughField()) {
            return ($this->getTargType() === $otherStub->getBaseType())
                   && ($this->getBaseType() === $otherStub->getTargType())
                   && ($this->getForeignField() === $otherStub->getKeyField())
                   && ($this->getKeyField() === $otherStub->getForeignField());
        }
        return ($this->getTargType() === $otherStub->getBaseType())
               && ($this->getBaseType() === $otherStub->getTargType())
               && ($this->getForeignField() === $otherStub->getThroughField())
               && ($this->getThroughField() === $otherStub->getForeignField());
    }
}
