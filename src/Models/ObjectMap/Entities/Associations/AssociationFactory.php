<?php declare(strict_types=1);


namespace AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations;

use Illuminate\Support\Str;

abstract class AssociationFactory
{
    public static $marshalPolymorphics = true;

    /**
     * @param AssociationStubBase $stubOne
     * @param AssociationStubBase $stubTwo
     * @return Association
     * @throws \Exception
     */
    public static function getAssocationFromStubs(
        AssociationStubBase $stubOne,
        AssociationStubBase $stubTwo
    ): Association {
        $checkAssociation = self::checkAssociations($stubOne, $stubTwo);
        return null === $checkAssociation ? self::buildAssociationFromStubs($stubOne, $stubTwo) : $checkAssociation;
    }

    /**
     * @param AssociationStubBase $stubOne
     * @param AssociationStubBase $stubTwo
     * @return Association
     * @throws \Exception
     */
    private static function buildAssociationFromStubs(
        AssociationStubBase $stubOne,
        AssociationStubBase $stubTwo
    ): Association {
        $first = -1 === $stubOne->compare($stubTwo);

        $association = new AssociationMonomorphic();
        if ($stubOne->getTargType() == null && self::$marshalPolymorphics) {
            $stubOne->addAssociation($association);
            $stubOne = self::marshalPolyToMono($stubOne, $stubTwo);
        }

        $input = [intval(!$first) => $stubOne, intval($first) => $stubTwo];
        $association->setFirst($input[0]);
        $association->setLast($input[1]);
        return $association;
    }

    private static function marshalPolyToMono(
        AssociationStubBase $stub,
        AssociationStubBase $stubTwo
    ): AssociationStubBase {
        $stubNew = clone $stub;
        $relPolyTypeName = substr($stubTwo->getBaseType(), strrpos($stubTwo->getBaseType(), '\\')+1);
        $relPolyTypeName = Str::plural($relPolyTypeName, 1);
        $stubNew->setRelationName($stub->getRelationName() . '_' . $relPolyTypeName);
        $stubNew->setTargType($stubTwo->getBaseType());
        $stubNew->setForeignFieldName($stubTwo->getKeyFieldName());
        $entity = $stub->getEntity();
        $stubs = $entity->getStubs();

        $stubs[$stubNew->getRelationName()] = $stubNew;
        $entity->setStubs($stubs);
        return $stubNew;
    }

    private static function checkAssociations(
        AssociationStubBase $stubOne,
        AssociationStubBase $stubTwo
    ): ?Association {
        $assocOne = $stubOne->getAssociations();
        foreach ($assocOne as $association) {
            $isFirst = $association->getFirst() === $stubOne;
            if ($association->{$isFirst ? 'getLast' : 'getFirst'}() == $stubTwo) {
                return $association;
            }
        }
        return null;
    }
}
