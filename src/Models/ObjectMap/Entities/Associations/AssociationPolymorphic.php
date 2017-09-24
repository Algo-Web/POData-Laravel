<?php

namespace AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations;

use AlgoWeb\PODataLaravel\Providers\MetadataProvider;

class AssociationPolymorphic extends Association
{
    /**
     * @var AssociationStubBase[]
     */
    protected $last = [];

    /**
     * @return AssociationStubBase[]
     */
    public function getLast()
    {
        return $this->last;
    }

    public function setLast(array $stubs)
    {
        $this->last = $stubs;
    }

    /**
     * @return bool
     */
    public function isOk()
    {
        $first = $this->getFirst();
        $last = $this->getLast();
        if (!$this->isStubOk($first)) {
            return false;
        }
        if (0 == count($this->last)) {
            return false;
        }
        foreach ($last as $stub) {
            if (!$this->isStubOk($stub)) {
                return false;
            }
            if (!$first->isCompatible($stub)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @return AssociationType|AssociationType[]
     */
    public function getAssociationType()
    {
        assert($this->isOk());
        $types = [];
        $first = $this->getFirst();
        $firstMult = $first->getMultiplicity()->getValue();
        $last = $this->getLast();
        foreach ($last as $stub) {
            $types[] = new AssociationType($firstMult | $stub->getMultiplicity()->getValue());
        }

        return $types;
    }

    private function isStubOk($stub)
    {
        return (null !== $stub && $stub instanceof AssociationStubPolymorphic && $stub->isOk());
    }
}
