<?php declare(strict_types=1);


namespace AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations;

use Illuminate\Support\Str;

abstract class AssociationFactory
{
    public static function getAssocationFromStubs(
        AssociationStubBase $stubOne,
        AssociationStubBase $stubTwo
    ): Association {
        return self::checkAssociations($stubOne, $stubTwo) ?? self::buildAssociationFromStubs($stubOne, $stubTwo);
    }

    private static function buildAssociationFromStubs(
        AssociationStubBase $stubOne,
        AssociationStubBase $stubTwo
    ): Association {
        if ($stubOne->getTargType() == null) {
            $stubOne = clone $stubOne;
            $relPolyTypeName = substr($stubTwo->getBaseType(), strrpos($stubTwo->getBaseType(), '\\')+1);
            $relPolyTypeName = Str::plural($relPolyTypeName, 1);
            $stubOne->setRelationName($stubOne->getRelationName() . '_' . $relPolyTypeName);
        }
        $oneFirst = $stubOne->getKeyField()->getIsKeyField();
        $twoFirst = $stubTwo->getKeyField()->getIsKeyField();
        $first = $oneFirst === $twoFirst ? -1 === $stubOne->compare($stubTwo) : $oneFirst;
        $input[intval(!$first)] = $stubOne;
        $input[intval($first)] = $stubTwo;
        $association = new AssociationMonomorphic();
        $association->setFirst($input[0]);
        $association->setLast($input[1]);
        return $association;
    }

    private static function checkAssociations(
        AssociationStubBase $stubOne,
        AssociationStubBase $stubTwo
    ): ?Association {
        $assocOne = $stubOne->getAssocations();
        foreach ($assocOne as $association) {
            $isFirst = $association->getFirst() === $stubOne;
            if ($association->{$isFirst ? 'getLast' : 'getFirst'}() == $stubTwo) {
                return $association;
            }
        }
        return null;
    }
}
