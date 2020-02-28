<?php declare(strict_types=1);


namespace AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations;

use Illuminate\Support\Str;

abstract class AssociationFactory
{
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
        return self::checkAssociations($stubOne, $stubTwo) ?? self::buildAssociationFromStubs($stubOne, $stubTwo);
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
        $oneFirst = $stubOne->getKeyField()->getIsKeyField();
        $twoFirst = $stubTwo->getKeyField()->getIsKeyField();
        $first = $oneFirst === $twoFirst ? -1 === $stubOne->compare($stubTwo) : $oneFirst;

        $association = new AssociationMonomorphic();
        if ($stubOne->getTargType() == null) {
            $oldName = $stubOne->getRelationName();
            //$stubOne->addAssociation($association);
            $stubOne = clone $stubOne;
            $relPolyTypeName = substr($stubTwo->getBaseType(), strrpos($stubTwo->getBaseType(), '\\')+1);
            $relPolyTypeName = Str::plural($relPolyTypeName, 1);
            $stubOne->setRelationName($stubOne->getRelationName() . '_' . $relPolyTypeName);
            $stubOne->setTargType($stubTwo->getBaseType());
            $stubOne->setForeignFieldName($stubTwo->getKeyFieldName());
            $entity = $stubOne->getEntity();
            $stubs = $entity->getStubs();
            if (array_key_exists($oldName, $stubs)) {
                //    unset($stubs[$oldName]);
            }
            $stubs[$stubOne->getRelationName()] = $stubOne;
            $entity->setStubs($stubs);
        }
        $input[intval(!$first)] = $stubOne;
        $input[intval($first)] = $stubTwo;
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
