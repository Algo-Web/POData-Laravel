<?php

namespace AlgoWeb\PODataLaravel\Models;

class AssociationStubPolymorphic extends AssociationStubBase
{
    /**
     * @var string
     */
    private $morphType;

    /**
     * @return string
     */
    public function getMorphType()
    {
        return $this->morphType;
    }

    /**
     * @param string $morphType
     */
    public function setMorphType($morphType)
    {
        $this->morphType = $morphType;
    }

    public function isCompatible(AssociationStubBase $otherStub)
    {
        if (!parent::isCompatible($otherStub)) {
            return false;
        }
        $thisTarg = $this->getTargType();
        $thatTarg = $otherStub->getTargType();
        $thisNull = null === $thisTarg;
        $thatNull = null === $thatTarg;
        if ($thisNull == $thatNull) {
            return false;
        }
        if ($thisNull && ($thatTarg != $this->getBaseType())) {
            return false;
        }
        if ($thatNull && ($thisTarg != $otherStub->getBaseType())) {
            return false;
        }

        return true;
    }
}
