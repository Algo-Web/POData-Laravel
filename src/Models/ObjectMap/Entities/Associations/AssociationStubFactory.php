<?php

declare(strict_types=1);

namespace AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use POData\Common\InvalidOperationException;

/**
 * Class AssociationStubFactory
 * @package AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations
 */
abstract class AssociationStubFactory
{
    /**
     * @param  Model                     $parent
     * @param  string                    $name
     * @throws InvalidOperationException
     * @return AssociationStubBase
     */
    public static function associationStubFromRelation(Model $parent, string $name): AssociationStubBase
    {
        $relation = $parent->{$name}();
        $handler  = self::getHandlerMethod($relation);
        /**
         * @var AssociationStubBase $stub
         */
        $stub = self::{'handle' . $handler}($name, $relation);
        $stub->setBaseType(get_class($parent));
        return $stub;
    }

    /**
     * @param Relation $relation
     * @return string
     */
    private static function getHandlerMethod(Relation $relation): string
    {
        $methods                                      = [];
        $methods[$relation instanceof BelongsTo]      = 'BelongsTo'; //DONE
        $methods[$relation instanceof MorphTo]        = 'MorphTo'; //DONE
        $methods[$relation instanceof BelongsToMany]  = 'BelongsToMany'; //DONE
        $methods[$relation instanceof MorphToMany]    = 'MorphToMany'; // DONE
        $methods[$relation instanceof HasOne]         = 'HasOne';
        $methods[$relation instanceof HasMany]        = 'HasMany';
        $methods[$relation instanceof HasManyThrough] = 'HasManyThrough'; //DONE
        $methods[$relation instanceof MorphMany]      = 'MorphMany';
        $methods[$relation instanceof MorphOne]       = 'MorphOne';

        return $methods[true];
    }

    /**
     * @param  string                     $name
     * @param  Relation                   $relation
     * @param  string                     $cacheKey
     * @return AssociationStubMonomorphic
     */
    protected static function handleBelongsTo(
        string $name,
        Relation $relation,
        $cacheKey = 'BelongsTo'
    ): AssociationStubMonomorphic {
        /** @var string[] $keyChain */
        $keyChain = self::getKeyChain($relation, $cacheKey);
        $stub     = new AssociationStubMonomorphic($name, $keyChain[0], $keyChain, AssociationStubRelationType::ONE());
        $stub->setForeignFieldName($keyChain[1]);
        $stub->setTargType(get_class($relation->getRelated()));
        return $stub;
    }

    /**
     * @param  string                     $name
     * @param  Relation                   $relation
     * @param  string                     $cacheKey
     * @return AssociationStubPolymorphic
     */
    protected static function handleMorphTo(
        string $name,
        Relation $relation,
        $cacheKey = 'MorphTo'
    ): AssociationStubPolymorphic {
        /** @var string[] $keyChain */
        $keyChain = self::getKeyChain($relation, $cacheKey);
        $stub     = new AssociationStubPolymorphic($name, $keyChain[0], $keyChain, AssociationStubRelationType::ONE());
        $stub->setForeignFieldName($keyChain[2]);
        $stub->setTargType(null);
        $stub->setMorphType($keyChain[1]);
        return $stub;
    }


    /**
     * @param  string                     $name
     * @param  Relation                   $relation
     * @param  string                     $cacheKey
     * @return AssociationStubMonomorphic
     */
    protected static function handleBelongsToMany(
        string $name,
        Relation $relation,
        $cacheKey = 'BelongsToMany'
    ): AssociationStubMonomorphic {
        /** @var string[] $keyChain */
        $keyChain = self::getKeyChain($relation, $cacheKey);
        $stub     = new AssociationStubMonomorphic($name, $keyChain[0], $keyChain, AssociationStubRelationType::MANY());
        $stub->setTargType(get_class($relation->getRelated()));
        $stub->setForeignFieldName($keyChain[3]);
        return $stub;
    }
    /**
     * @param  string                     $name
     * @param  Relation                   $relation
     * @param  string                     $cacheKey
     * @return AssociationStubMonomorphic
     */
    protected static function handleHasManyThrough(
        string $name,
        Relation $relation,
        $cacheKey = 'HasManyThrough'
    ): AssociationStubMonomorphic {
        /** @var string[] $keyChain */
        $keyChain = self::getKeyChain($relation, $cacheKey);
        $stub     = new AssociationStubMonomorphic($name, $keyChain[0], $keyChain, AssociationStubRelationType::MANY());
        $stub->setTargType(get_class($relation->getRelated()));
        $stub->setForeignFieldName($keyChain[3]);
        return $stub;
    }

    /**
     * @param  string                     $name
     * @param  Relation                   $relation
     * @param  string                     $cacheKey
     * @return AssociationStubPolymorphic
     */
    protected static function handleMorphToMany(
        string $name,
        Relation $relation,
        $cacheKey = 'MorphToMany'
    ): AssociationStubPolymorphic {
        //return self::handleBelongsToMany($name,$relation);
        //TODO: investigate if this could be treated as a BelongsToMany,
        // or more importantly a Monomorphic as we know both sides
        /** @var string[] $keyChain */
        $keyChain = self::getKeyChain($relation, $cacheKey);
        $stub     = new AssociationStubPolymorphic($name, $keyChain[0], $keyChain, AssociationStubRelationType::MANY());
        //$stub->setRelationName($name);
        //$stub->setThroughFieldChain($keyChain);
        //$stub->setKeyFieldName($keyChain[0]);
        $stub->setForeignFieldName($keyChain[4]);
        //$stub->setMultiplicity(AssociationStubRelationType::MANY());
        $stub->setMorphType($keyChain[2]);
        $stub->setTargType(get_class($relation->getRelated()));
        return $stub;
    }

    /**
     * @param  string                     $name
     * @param  Relation                   $relation
     * @param  string                     $cacheKey
     * @return AssociationStubMonomorphic
     */
    protected static function handleHasOne(
        string $name,
        Relation $relation,
        $cacheKey = 'HasOneOrMany'
    ): AssociationStubMonomorphic {
        /** @var string[] $keyChain */
        $keyChain = self::getKeyChain($relation, $cacheKey);
        $stub     = new AssociationStubMonomorphic(
            $name,
            $keyChain[0],
            $keyChain,
            AssociationStubRelationType::NULL_ONE()
        );
        $stub->setForeignFieldName($keyChain[1]);
        $stub->setTargType(get_class($relation->getRelated()));
        return $stub;
    }

    /**
     * @param  string                     $name
     * @param  Relation                   $relation
     * @param  string                     $cacheKey
     * @return AssociationStubMonomorphic
     */
    protected static function handleHasMany(
        string $name,
        Relation $relation,
        $cacheKey = 'HasOneOrMany'
    ): AssociationStubMonomorphic {
        /** @var string[] $keyChain */
        $keyChain = self::getKeyChain($relation, $cacheKey);
        $stub     = new AssociationStubMonomorphic($name, $keyChain[0], $keyChain, AssociationStubRelationType::MANY());
        $stub->setForeignFieldName($keyChain[1]);
        $stub->setTargType(get_class($relation->getRelated()));
        return $stub;
    }

    /**
     * @param  string                     $name
     * @param  Relation                   $relation
     * @param  string                     $cacheKey
     * @return AssociationStubPolymorphic
     */
    protected static function handleMorphOne(
        string $name,
        Relation $relation,
        $cacheKey = 'MorphOneOrMany'
    ): AssociationStubPolymorphic {
        /** @var string[] $keyChain */
        $keyChain = self::getKeyChain($relation, $cacheKey);
        $stub     = new AssociationStubPolymorphic(
            $name,
            $keyChain[0],
            $keyChain,
            AssociationStubRelationType::NULL_ONE()
        );
        $stub->setForeignFieldName($keyChain[2]);
        $stub->setTargType(get_class($relation->getRelated()));
        $stub->setMorphType($keyChain[1]);
        return $stub;
    }

    /**
     * @param  string                     $name
     * @param  Relation                   $relation
     * @param  string                     $cacheKey
     * @return AssociationStubPolymorphic
     */
    protected static function handleMorphMany(
        string $name,
        Relation $relation,
        $cacheKey = 'MorphOneOrMany'
    ): AssociationStubPolymorphic {
        /** @var string[] $keyChain */
        $keyChain = self::getKeyChain($relation, $cacheKey);
        $stub     = new AssociationStubPolymorphic($name, $keyChain[0], $keyChain, AssociationStubRelationType::MANY());
        $stub->setMorphType($keyChain[1]);
        $stub->setForeignFieldName($keyChain[2]);
        $stub->setTargType(get_class($relation->getRelated()));
        return $stub;
    }

    /**
     * @param  Relation $relation
     * @param  string   $cacheKey
     * @return array<array|string>
     */
    private static function getKeyChain(Relation $relation, string $cacheKey): array
    {
        $fields = self::$fieldOrderCache[$cacheKey];
        $getter = function () use ($fields) {
            $carry = [];
            foreach ($fields as $item) {
                $v = $this->{$item};
                if (null == $v && 'ownerKey' == $item) {
                    $carry[] = null;
                    continue;
                }
                //TODO: investigate if this is needed can we use quailifed keys?
                $segments = explode('.', strval($this->{$item}));
                $carry[]  = end($segments);
            }
            return $carry;
        };
        return call_user_func($getter->bindTo($relation, Relation::class));
    }

    /** @var array<string, array> */
    private static $fieldOrderCache = [
        'BelongsTo' => ['foreignKey', 'ownerKey'],
        'BelongsToMany' => ['parentKey','foreignPivotKey','relatedPivotKey','relatedKey'],
        'HasOneOrMany' => ['localKey', 'foreignKey' ],
        'HasManyThrough' => ['localKey', 'firstKey', 'secondLocalKey', 'secondKey'],
        'MorphToMany' => ['parentKey','foreignPivotKey','morphType', 'relatedPivotKey','relatedKey'],
        'MorphTo' => ['foreignKey', 'morphType', 'ownerKey'],
        'MorphOneOrMany' => ['localKey', 'morphType', 'foreignKey'],
    ];
}
