<?php declare(strict_types=1);

namespace AlgoWeb\PODataLaravel\Models;

use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\Association;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubBase;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\EntityGubbins;
use POData\Common\InvalidOperationException;

interface IMetadataRelationshipContainer
{
    /**
     * Add entity to container.
     *
     * @param  EntityGubbins             $entity
     * @throws InvalidOperationException
     */
    public function addEntity(EntityGubbins $entity): void;

    /**
     * returns all relation stubs that are permitted at the other end.
     *
     * @param string $className
     * @param string $relName
     * @return AssociationStubBase[]
     */
    public function getRelationsByRelationName(string $className, string $relName): array;

    /**
     * gets All Association On a given class.
     *
     * @param  string        $className
     * @return Association[]
     */
    public function getRelationsByClass(string $className): array;

    /**
     * Gets all defined Associations.
     *
     * @return Association[]
     */
    public function getRelations(): array;

    /**
     * Checks if a class is loaded into the relation container.
     *
     * @param  string $className
     * @return bool
     */
    public function hasClass(string $className): bool;
}
