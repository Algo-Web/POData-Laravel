<?php

namespace AlgoWeb\PODataLaravel\Models;

class Association
{
    /**
     * @var AssociationStubBase
     */
    protected $first;

    /**
     * @var AssociationStubBase
     */
    protected $last;

    /**
     * @return AssociationStubBase
     */
    public function getFirst()
    {
        return $this->first;
    }

    /**
     * @param AssociationStubBase $first
     */
    public function setFirst(AssociationStubBase $first)
    {
        $this->first = $first;
    }

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
    }

    public function isOk()
    {
        $first = $this->getFirst();
        $last = $this->getLast();
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
}
