<?php declare(strict_types=1);

namespace AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations;

use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\EntityField;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\EntityGubbins;

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
    protected $keyFieldName;

    /**
     * A list of fields to Traverse between Keyfield and foreignField.
     *
     * @var string[]
     */
    protected $throughFieldChain;

    /**
     * Foreign key field of other end of relation.
     *
     * @var string|null
     */
    protected $foreignFieldName;

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
     * Any assocations this stub is a member of.
     *
     * @var Association[]
     */
    protected $associations = [];

    /**
     * @var EntityGubbins the entity this stub lives on.
     */
    protected $entity;

    /**
     * Sets the entity owning this AssocationStub
     *
     * @param EntityGubbins $entity
     */
    public function setEntity(EntityGubbins $entity): void
    {
        $this->entity = $entity;
    }

    /**
     * Gets the entity owning this AssocationStub
     *
     * @return EntityGubbins
     */
    public function getEntity(): EntityGubbins
    {
        return $this->entity;
    }
    /**
     * Adds this stub as a member of an assocation
     *
     * @param Association $newAssocation the new assocation to be a member of
     */
    public function addAssociation(Association $newAssocation): void {
        $this->associations[] = $newAssocation;
    }

    /**
     * Gets all assocations assigned to this stub.
     *
     * @return Association[] All assocations this stub is a member of
     */
    public function getAssocations(): array
    {
       return $this->associations;
    }

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
    public function getKeyFieldName()
    {
        return $this->keyFieldName;
    }

    /**
     * @param string $keyFieldName
     */
    public function setKeyFieldName($keyFieldName)
    {
        $this->keyFieldName = $this->checkStringInput($keyFieldName) ? $keyFieldName : $this->keyFieldName;
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
        return (AssociationStubRelationType::MANY() == $thisMult
                || $thisMult != $thatMult);
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
        if (null === $this->keyFieldName) {
            return false;
        }
        if (null === $this->baseType) {
            return false;
        }
        $targType = $this->targType;
        if ($this instanceof AssociationStubMonomorphic && null === $targType) {
            return false;
        }
        $foreignField = $this->foreignFieldName;
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
    public function getForeignFieldName()
    {
        return $this->foreignFieldName;
    }

    /**
     * @param string $foreignFieldName
     */
    public function setForeignFieldName($foreignFieldName)
    {
        $this->foreignFieldName = $foreignFieldName;
    }

    /**
     * @return string[]|null
     */
    public function getThroughFieldChain(): array
    {
        return $this->throughFieldChain;
    }

    /**
     * @param string[]|null $keyChain
     */
    public function setThroughFieldChain(?array $keyChain)
    {
        $this->throughFieldChain = $keyChain;
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
        $thisClass  = get_class($this);
        $otherClass = get_class($other);
        $classComp  = strcmp($thisClass, $otherClass);
        if (0 !== $classComp) {
            return $classComp / abs($classComp);
        }
        $thisBase  = $this->getBaseType() ?? '';
        $otherBase = $other->getBaseType() ?? '';
        $baseComp  = strcmp($thisBase, $otherBase);
        if (0 !== $baseComp) {
            return $baseComp / abs($baseComp);
        }
        $thisMethod  = $this->getRelationName() ?? '';
        $otherMethod = $other->getRelationName() ?? '';
        $methodComp  = strcmp($thisMethod, $otherMethod);
        return 0 === $methodComp ? 0 : $methodComp / abs($methodComp);
    }

    /**
     * Return what type of stub this is - polymorphic, monomorphic, or something else.
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
