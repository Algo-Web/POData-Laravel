<?php

namespace AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations;

use POData\Common\InvalidOperationException;

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

    /**
     * @return bool
     * @throws InvalidOperationException
     */
    public function isKnownSide()
    {
        if (!($this->isOk())) {
            throw new InvalidOperationException('Polymorphic stub not OK so known-side determination is meaningless');
        }
        return null === $this->targType;
    }
}
