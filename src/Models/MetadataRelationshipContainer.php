<?php

declare(strict_types=1);


namespace AlgoWeb\PODataLaravel\Models;

use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\Association;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationFactory;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubBase;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubMonomorphic;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubPolymorphic;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\EntityGubbins;
use POData\Common\InvalidOperationException;

/**
 * Class MetadataRelationshipContainer
 * @package AlgoWeb\PODataLaravel\Models
 */
class MetadataRelationshipContainer implements IMetadataRelationshipContainer
{
    /**
     * @var EntityGubbins[] all entities keyed by class name
     */
    private $entities = [];
    /**
     * @var AssociationStubBase[][][] AssociationStubMonomorphic keyed as [BaseClass][targetClass]
     */
    private $stubs = [
    ];
    /**
     * A complete set of associations, keyed by classname.
     *
     * @var Association[]
     */
    private $associations = [];

    /**
     * Add entity to Container.
     *
     * @param EntityGubbins $entity
     */
    public function addEntity(EntityGubbins $entity): void
    {
        /** @var string $baseType */
        $baseType                  = $entity->getClassName();
        $this->entities[$baseType] = $entity;
        if (array_key_exists($baseType, $this->stubs)) {
            throw new \InvalidArgumentException(sprintf('%s already added', $baseType));
        }
        $this->stubs[$baseType] = [];
        foreach ($entity->getStubs() as $stub) {
            $this->stubs[$baseType][$stub->getTargType()][] = $stub;
        }
    }

    private function buildAssociations(): void
    {
        array_walk_recursive($this->stubs, [$this, 'buildAssociationFromStub']);
    }

    /**
     * @param  string                $baseType
     * @param  string                $targetType
     * @return AssociationStubBase[]
     */
    private function getStubs(?string $baseType, ?string $targetType): array
    {
        if ($baseType === null ||
           !array_key_exists($baseType, $this->stubs) ||
           !array_key_exists($targetType, $this->stubs[$baseType])) {
            return [];
        }
        return $this->stubs[$baseType][$targetType];
    }

    /**
     * @param AssociationStubBase $item
     */
    private function buildAssociationFromStub(AssociationStubBase $item): void
    {
        $baseTypeCheck = ($item instanceof AssociationStubPolymorphic &&
            count($item->getThroughFieldChain()) == 3) ? null : $item->getBaseType();

        $otherCandidates = array_filter($this->getStubs($item->getTargType(), $baseTypeCheck), [$item, 'isCompatible']);
        $associations    = array_reduce(
            $otherCandidates,
            function ($carry, $candidate) use ($item) {
                $newAssociation = AssociationFactory::getAssocationFromStubs($candidate, $item);
                $carry[spl_object_hash($newAssociation)] = $newAssociation;
                return $carry;
            },
            []
        );
        $this->addAssociations($associations);
    }

    /**
     * @param Association[] $additionals
     */
    private function addAssociations(array $additionals): void
    {
        $this->associations = array_merge($this->associations, $additionals);
    }


    /**
     * returns all Relation Stubs that are permitted at the other end.
     *
     * @param string                 $className
     * @param string                 $relName
     * @return AssociationStubBase[]
     */
    public function getRelationsByRelationName(string $className, string $relName): array
    {
        $this->checkClassExists($className);
        if (!array_key_exists($relName, $this->entities[$className]->getStubs())) {
            $msg = 'Relation %s not registered on %s';
            throw new \InvalidArgumentException(sprintf($msg, $relName, $className));
        }

        if (empty($this->associations)) {
            $this->buildAssociations();
        }
        $entities = $this->entities[$className];
        $relation = $entities->getStubs()[$relName];
        return array_reduce($relation->getAssociations(), function ($carry, Association $item) use ($relation) {
            $carry[] = ($item->getFirst() === $relation) ? $item->getLast() : $item->getFirst();
            return $carry;
        }, []);
    }

    /**
     * gets All Association On a given class.
     *
     * @param  string        $className
     * @return Association[]
     */
    public function getRelationsByClass(string $className): array
    {
        if (empty($this->associations)) {
            $this->buildAssociations();
        }

        $this->checkClassExists($className);
        return array_reduce($this->entities[$className]->getStubs(), function ($carry, AssociationStubBase $item) {
            return array_merge($carry, $item->getAssociations());
        }, []);
    }

    /**
     * @param string $className
     */
    protected function checkClassExists(string $className): void
    {
        if (!$this->hasClass($className)) {
            $msg = '%s does not exist in holder';
            throw new \InvalidArgumentException(sprintf($msg, $className));
        }
    }
    /**
     * gets all defined Association.
     *
     * @throws InvalidOperationException
     * @return Association[]
     */
    public function getRelations(): array
    {
        if (empty($this->associations)) {
            $this->buildAssociations();
        }
        return array_values($this->associations);
    }

    /**
     * checks if a class is loaded into the relation container.
     *
     * @param  string $className
     * @return bool
     */
    public function hasClass(string $className): bool
    {
        return array_key_exists($className, $this->entities);
    }
}
