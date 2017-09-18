<?php

namespace AlgoWeb\PODataLaravel\Models;

class AssociationStubMonomorphic extends AssociationStubBase
{
    public function isCompatible(AssociationStubBase $otherStub)
    {
        if (!parent::isCompatible($otherStub)) {
            return false;
        }
        if ($this->getForeignField() !== $otherStub->getKeyField()) {
            return false;
        }
        return true;
    }
}
