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
        $thisChain = $this->getThroughFieldChain();
        $otherChain = $otherStub->getThroughFieldChain();
        $thisThroughCount = count($thisChain) - 1;
        $otherThroughCount = count($otherChain) - 1;
        if ($thisThroughCount !== $otherThroughCount) {
            return false;
        }
        for ($i=0; $i <= $thisThroughCount;++$i) {
            if ($thisChain[$i] !== $otherChain[$otherThroughCount -$i]) {
                return false;
            }
        }

        return ($this->getTargType() === $otherStub->getBaseType())
            && ($this->getBaseType() === $otherStub->getTargType())
            && ($this->getForeignField() === $otherStub->getKeyField())
            && ($this->getKeyField() === $otherStub->getForeignField());
    }

    /**
     * {@inheritdoc}
     */
    public function morphicType()
    {
        return 'monomorphic';
    }
}
