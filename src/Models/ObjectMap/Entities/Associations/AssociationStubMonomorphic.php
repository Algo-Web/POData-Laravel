<?php

namespace AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations;

class AssociationStubMonomorphic extends AssociationStubBase
{
    public function isCompatible(AssociationStubBase $otherStub)
    {
        if (!parent::isCompatible($otherStub)) {
            return false;
        }
        return $this->getForeignField() === $otherStub->getKeyField();
    }
}