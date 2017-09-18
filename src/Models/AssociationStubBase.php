<?php

namespace AlgoWeb\PODataLaravel\Models;

abstract class AssociationStubBase
{
    /**
     * @var AssociationStubRelationType
     */
    protected $multiplicity;

    /**
     * Foreign key field of this end of relation
     *
     * @var string
     */
    protected $keyField;

    /**
     * Foreign key field of other end of relation
     *
     * @var string
     */
    protected $foreignField;

    /**
     * @var string
     */
    protected $relationName;

    /**
     * Target type this relation points to, if known.  Is null for known-side polymorphic relations.
     * @var string
     */
    protected $targType;

    /**
     * Base type this relation is attached to
     * @var string
     */
    protected $baseType;

    /**
     * @return mixed
     */
    public function getRelationName()
    {
        return $this->relationName;
    }

    /**
     * @param string $relationName
     */
    public function setRelationName($relationName)
    {
        $this->relationName = $relationName;
    }

    /**
     * @return AssociationStubRelationType
     */
    public function getMultiplicity()
    {
        return $this->multiplicity;
    }

    /**
     * @param AssociationStubRelationType $multiplicity
     */
    public function setMultiplicity(AssociationStubRelationType $multiplicity)
    {
        $this->multiplicity = $multiplicity;
    }

    /**
     * @return string
     */
    public function getKeyField()
    {
        return $this->keyField;
    }

    /**
     * @param string $keyField
     */
    public function setKeyField($keyField)
    {
        $this->keyField = $keyField;
    }

    public function isCompatible(AssociationStubBase $otherStub)
    {
        $thisPoly = $this instanceof AssociationStubPolymorphic;
        $thatPoly = $otherStub instanceof AssociationStubPolymorphic;
        $thisMono = $this instanceof AssociationStubMonomorphic;
        $thatMono = $otherStub instanceof AssociationStubMonomorphic;

        $count = ($thisPoly ? 1 : 0) + ($thatPoly ? 1 : 0) + ($thisMono ? 1 : 0) + ($thatMono ? 1 : 0);
        assert(2 == $count);

        if ($thisPoly && $thatMono) {
            return false;
        }
        if ($thisMono && $thatPoly) {
            return false;
        }
        if (!$this->isOk()) {
            return false;
        }
        if (!$otherStub->isOk()) {
            return false;
        }
        $thisMult = $this->getMultiplicity();
        $thatMult = $otherStub->getMultiplicity();
        if (AssociationStubRelationType::MANY() != $thisMult && $thisMult == $thatMult) {
            return false;
        }

        return true;
    }

    /**
     * Is this AssociationStub sane?
     */
    public function isOk()
    {
        if (null === $this->multiplicity) {
            return false;
        }
        $relName = $this->relationName;
        if (null === $relName || !is_string($relName) || empty($relName)) {
            return false;
        }
        $keyField = $this->keyField;
        if (null === $keyField || !is_string($keyField) || empty($keyField)) {
            return false;
        }
        $baseType = $this->baseType;
        if (null === $baseType || !is_string($baseType) || empty($baseType)) {
            return false;
        }
        $targType = $this->targType;
        if ($this instanceof AssociationStubMonomorphic && null === $targType) {
            return false;
        }
        if (null !== $targType && (!is_string($targType) || empty($targType))) {
            return false;
        }
        $foreignField = $this->foreignField;
        if (null !== $targType && (null === $foreignField || !is_string($foreignField) || empty($foreignField))) {
            return false;
        }
        if (null === $targType && null !== $foreignField) {
            return false;
        }

        return true;
    }

    /**
     * @return string
     */
    public function getTargType()
    {
        return $this->targType;
    }

    /**
     * @param string $targType
     */
    public function setTargType($targType)
    {
        $this->targType = $targType;
    }

    /**
     * @return string
     */
    public function getBaseType()
    {
        return $this->baseType;
    }

    /**
     * @param string $baseType
     */
    public function setBaseType($baseType)
    {
        $this->baseType = $baseType;
    }

    /**
     * @return string
     */
    public function getForeignField()
    {
        return $this->foreignField;
    }

    /**
     * @param string $foreignField
     */
    public function setForeignField($foreignField)
    {
        $this->foreignField = $foreignField;
    }

    /**
     * Supply a canonical sort ordering to determine order in associations
     *
     * @param AssociationStubBase $other
     *
     * @return int
     */
    public function compare(AssociationStubBase $other)
    {
        $thisClass = get_class($this);
        $otherClass = get_class($other);
        if ($thisClass !== $otherClass) {
            return $thisClass < $otherClass ? -1 : 1;
        }
        $thisBase = $this->getBaseType();
        $otherBase = $other->getBaseType();
        if ($thisBase !== $otherBase) {
            return $thisBase < $otherBase ? -1 : 1;
        }
        $thisMethod = $this->getRelationName();
        $otherMethod = $other->getRelationName();
        $methodComp = strcmp($thisMethod, $otherMethod);
        return 0 === $methodComp ? 0 : $methodComp / abs($methodComp);
    }
}
