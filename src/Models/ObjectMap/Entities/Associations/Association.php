<?php

namespace AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations;

abstract class Association
{
    /**
     * @var AssociationStubBase
     */
    protected $first;

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
     * @return AssociationStubBase|AssociationStubBase[]
     */
    abstract public function getLast();

    /**
     * @return bool
     */
    abstract public function isOk();

    /**
     * @return AssociationType|AssociationType[]
     */
    abstract public function getAssociationType();
}
