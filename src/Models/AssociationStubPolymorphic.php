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
}
