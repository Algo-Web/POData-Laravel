<?php

namespace AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations;

abstract class AssociationStubBase
{
    /**
     * @var AssociationStubRelationType
     */
    protected $multiplicity;

    /**
     * Foreign key field of this end of relation.
     *
     * @var string|null
     */
    protected $keyField;

    /**
     * Foreign key field of imtermate relation.
     *
     * @var string|null
     */
    protected $throughField;

    /**
     * Foreign key field of other end of relation.
     *
     * @var string|null
     */
    protected $foreignField;

    /**
     * @var string|null
     */
    protected $relationName;

    /**
     * Target type this relation points to, if known.  Is null for known-side polymorphic relations.
     *
     * @var string|null
     */
    protected $targType;

    /**
     * Base type this relation is attached to.
     *
     * @var string|null
     */
    protected $baseType;

    /**
     * @return string
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
        $this->relationName = $this->checkStringInput($relationName) ? $relationName : $this->relationName;
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
        $this->keyField = $this->checkStringInput($keyField) ? $keyField : $this->keyField;
    }

    public function isCompatible(AssociationStubBase $otherStub)
    {
        if ($this->morphicType() != $otherStub->morphicType()) {
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
        return (AssociationStubRelationType::MANY()->getValue() == $thisMult->getValue()
                || $thisMult->getValue() != $thatMult->getValue());
    }

    /**
     * Is this AssociationStub sane?
     */
    public function isOk()
    {
        if (null === $this->multiplicity) {
            return false;
        }
        if (null === $this->relationName) {
            return false;
        }
        if (null === $this->keyField) {
            return false;
        }
        if (null === $this->baseType) {
            return false;
        }
        $targType = $this->targType;
        if ($this instanceof AssociationStubMonomorphic && null === $targType) {
            return false;
        }
        $foreignField = $this->foreignField;
        if (null !== $targType) {
            if (!$this->checkStringInput($targType)) {
                return false;
            }
            if (!$this->checkStringInput($foreignField)) {
                return false;
            }
        }
        return (null === $targType) === (null === $foreignField);
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
        $this->baseType = $this->checkStringInput($baseType) ? $baseType : $this->baseType;
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
     * @return string
     */
    public function getThroughField()
    {
        return $this->throughField;
    }

    /**
     * @param string $foreignField
     */
    public function setThroughField($foreignField)
    {
        $this->throughField = $foreignField;
    }

    /**
     * Supply a canonical sort ordering to determine order in associations.
     *
     * @param AssociationStubBase $other
     *
     * @return int
     */
    public function compare(AssociationStubBase $other)
    {
        $thisClass = get_class($this);
        $otherClass = get_class($other);
        $classComp = strcmp($thisClass, $otherClass);
        if (0 !== $classComp) {
            return $classComp / abs($classComp);
        }
        $thisBase = $this->getBaseType();
        $otherBase = $other->getBaseType();
        $baseComp = strcmp($thisBase, $otherBase);
        if (0 !== $baseComp) {
            return $baseComp / abs($baseComp);
        }
        $thisMethod = $this->getRelationName();
        $otherMethod = $other->getRelationName();
        $methodComp = strcmp($thisMethod, $otherMethod);
        return 0 === $methodComp ? 0 : $methodComp / abs($methodComp);
    }

    /**
     * Return what type of stub this is - polymorphic, monomorphic, or something else
     *
     * @return string
     */
    abstract public function morphicType();

    /**
     * @param $input
     * @return bool
     */
    private function checkStringInput($input)
    {
        if (null === $input || !is_string($input) || empty($input)) {
            return false;
        }
        return true;
    }
}
