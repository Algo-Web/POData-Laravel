<?php

namespace AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations;

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

        if (AssociationStubRelationType::MANY() == $this->getMultiplicity()
            && AssociationStubRelationType::MANY() == $otherStub->getMultiplicity()) {

            if ($thisNull && ($otherStub->getForeignField() != $this->getKeyField())) {
                return false;
            }

            if ($thatNull && ($this->getForeignField() != $otherStub->getKeyField())) {
                return false;
            }
        }

        return true;
    }

    public function isKnownSide()
    {
        assert($this->isOk(), 'Polymorphic stub not OK so known-side determination is meaningless');
        return null === $this->targType;
    }
}
