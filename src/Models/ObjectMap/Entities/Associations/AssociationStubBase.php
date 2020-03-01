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
     * Any associations this stub is a member of.
     *
     * @var Association[]
     */
    protected $associations = [];

    /**
     * @var EntityGubbins the entity this stub lives on
     */
    protected $entity;

    /**
     * AssociationStubBase constructor.
     * @param string $relationName
     * @param string $keyFieldName
     * @param string[] $throughFieldChain
     * @param AssociationStubRelationType $multiplicity
     */
    public function __construct(
        string $relationName,
        string $keyFieldName,
        array $throughFieldChain,
        AssociationStubRelationType $multiplicity
    ) {
        $this->relationName = $relationName;
        $this->keyFieldName = $keyFieldName;
        $this->throughFieldChain = $throughFieldChain;
        $this->multiplicity = $multiplicity;
    }

    /**
     * Sets the entity owning this AssocationStub.
     *
     * @param EntityGubbins $entity
     */
    public function setEntity(EntityGubbins $entity): void
    {
        $this->entity = $entity;
    }

    /**
     * Gets the entity owning this AssocationStub.
     *
     * @return EntityGubbins
     */
    public function getEntity(): EntityGubbins
    {
        return $this->entity;
    }
    /**
     * Adds this stub as a member of an association.
     *
     * @param Association $newAssociation the new association to be a member of
     */
    public function addAssociation(Association $newAssociation): void
    {
        $this->associations[spl_object_hash($newAssociation)] = $newAssociation;
    }

    /**
     * Gets all associations assigned to this stub.
     *
     * @return Association[] All associations this stub is a member of
     */
    public function getAssociations(): array
    {
        return array_values($this->associations);
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
    public function getKeyFieldName(): string
    {
        return $this->keyFieldName ?? '';
    }

    public function getKeyField() : ?EntityField
    {
        return (null === $this->entity) ? null : $this->entity->getFields()[$this->getKeyFieldName()];
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
        return count($this->getThroughFieldChain()) === count($otherStub->getThroughFieldChain());
    }

    /**
     * Is this AssociationStub sane?
     */
    public function isOk(): bool
    {
        $required = [
            $this->multiplicity,
            $this->relationName,
            $this->keyFieldName,
            $this->baseType,
            $this->throughFieldChain
        ];
        $isOk = true;
        $isOk &= empty(array_filter($required, 'is_null'));
        $isOk &= (null === $this->targType) === (null ===  $this->foreignFieldName);

        return boolval($isOk);
    }

    /**
     * @return string|null
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
        $thisFirst  = null === $this->getKeyField() ? false : $this->getKeyField()->getIsKeyField();
        $otherFirst = null === $other->getKeyField() ? false : $other->getKeyField()->getIsKeyField();
        if (
            ($thisFirst || $otherFirst) &&
            !($thisFirst && $otherFirst)
            ) {
            return $thisFirst ? -1 : 1;
        }
        $cmps = [
            [get_class($this), get_class($other)],
            [$this->getBaseType() ?? '', $other->getBaseType() ?? ''],
            [$this->getRelationName() ?? '', $other->getRelationName() ?? ''],
        ];
        foreach ($cmps as $cmpvals) {
            $cmp = strcmp($cmpvals[0], $cmpvals[1]);
            if (0 !== $cmp) {
                return $cmp / abs($cmp);
            }
        }
        return 0;
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
    protected function checkStringInput($input)
    {
        if (null === $input || !is_string($input) || empty($input)) {
            return false;
        }
        return true;
    }
}
