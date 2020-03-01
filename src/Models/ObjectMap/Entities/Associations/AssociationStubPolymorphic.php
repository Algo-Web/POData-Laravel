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
    public function getMorphType() : ?string
    {
        return $this->morphType;
    }

    /**
     * @param string $morphType
     */
    public function setMorphType(string $morphType) : void
    {
        $this->morphType = $morphType;
    }

    /**
     * @param \AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubBase $otherStub
     *
     * @return bool
     */
    public function isCompatible(AssociationStubBase $otherStub) : bool
    {
        if (!parent::isCompatible($otherStub)) {
            return false;
        }

        if (null === $this->getTargType() && null === $otherStub->getTargType()) {
            return false;
        }

        $thisBase = $this->getBaseType();
        $thatBase = $otherStub->getBaseType();
        $thatTarg = $otherStub->getTargType() ?? $thisBase;
        $thisTarg = $this->getTargType() ?? $thatBase;
        if ($thatTarg != $thisBase) {
            return false;
        }
        if ($thisTarg != $thatBase) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function morphicType() : string
    {
        return 'polymorphic';
    }
}
