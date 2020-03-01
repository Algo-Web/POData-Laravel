<?php declare(strict_types=1);

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
        $thisBase = $this->getBaseType();
        $thatBase = $otherStub->getBaseType();
        $thisTarg = $this->getTargType();
        $thatTarg = $otherStub->getTargType();
        if (null === $thisTarg && null === $thatTarg) {
            return false;
        }

        $thatTarg = $otherStub->getTargType() ?? $this->getBaseType();
        $thisTarg = $this->getTargType() ?? $otherStub->getBaseType();
        if (($thatTarg != $thisBase)) {
            return false;
        }
        if (($thisTarg != $thatBase)) {
            return false;
        }

        return true;
    }

    /**
     * @throws InvalidOperationException
     * @return bool
     */
    public function isKnownSide()
    {
        if (!($this->isOk())) {
            throw new InvalidOperationException('Polymorphic stub not OK so known-side determination is meaningless');
        }
        return null === $this->targType;
    }

    /**
     * {@inheritdoc}
     */
    public function morphicType()
    {
        return 'polymorphic';
    }
}
