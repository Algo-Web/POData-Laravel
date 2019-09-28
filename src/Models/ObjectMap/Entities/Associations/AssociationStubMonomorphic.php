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
        $isNull = null == $this->getThroughField();
        $otherNull = null == $otherStub->getThroughField();

        if ($isNull != $otherNull) {
            return false;
        }

        return ($this->getTargType() === $otherStub->getBaseType())
               && ($this->getBaseType() === $otherStub->getTargType())
               && ($this->getForeignField() === $otherStub->getKeyField())
               && ($this->getKeyField() === $otherStub->getForeignField());
    }

    /**
     * @inheritdoc
     */
    public function morphicType()
    {
        return "monomorphic";
    }
}
