<?php

declare(strict_types=1);

namespace AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations;

class AssociationMonomorphic extends Association
{

    /**
     * @var AssociationStubBase|null
     */
    protected $last;

    /**
     * @return AssociationStubBase|null
     */
    public function getLast(): ?AssociationStubBase
    {
        return $this->last;
    }

    /**
     * @param AssociationStubBase $last
     */
    public function setLast(AssociationStubBase $last): void
    {
        $this->last = $last;
        $last->addAssociation($this);
    }

    /**
     * @return bool
     */
    public function isOk(): bool
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
        return -1 === $first->compare($last);
    }

    /**
     * @return \AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationType
     */
    public function getAssociationType(): AssociationType
    {
        return new AssociationType($this->first->getMultiplicity()->getValue()
                                    | $this->last->getMultiplicity()->getValue());
    }
}
