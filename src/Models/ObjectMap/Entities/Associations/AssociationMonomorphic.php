<?php declare(strict_types=1);

namespace AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations;

class AssociationMonomorphic extends Association
{

    /**
     * @var AssociationStubBase
     */
    protected $last;

    /**
     * @return AssociationStubBase
     */
    public function getLast()
    {
        return $this->last;
    }

    /**
     * @param AssociationStubBase $last
     */
    public function setLast(AssociationStubBase $last)
    {
        $this->last = $last;
        $last->addAssociation($this);
    }

    /**
     * @return bool
     */
    public function isOk()
    {
        $first = $this->getFirst();
        $last  = $this->getLast();
        if (null === $first || !$first->isOk()) {
            return false;
        }
        if (null === $last || !$last->isOk()) {
            return false;
        }
        if (!$first->isCompatible($last)) {
            return false;
        }
        return true;
    }

    /**
     * @return \AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationType
     */
    public function getAssociationType()
    {
        return new AssociationType($this->first->getMultiplicity()->getValue()
                                    | $this->last->getMultiplicity()->getValue());
    }
}
