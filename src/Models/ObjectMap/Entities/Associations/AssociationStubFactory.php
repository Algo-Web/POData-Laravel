<?php
declare(strict_types=1);

namespace AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations;

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

abstract class AssociationStubFactory
{
    public static function associationStubFromRelation(string $name, Relation $relation): AssociationStubBase
    {
        $handler = self::getHandlerMethod($relation);
        return self::{'handle' . $handler}($name, $relation);
    }

    private static function getHandlerMethod(Relation $relation):string
    {
        $methods = [];
        $methods[$relation instanceof BelongsTo] = 'BelongsTo'; //DONE
        $methods[$relation instanceof MorphTo] = 'MorphTo'; //DONE
        $methods[$relation instanceof BelongsToMany] = 'BelongsToMany'; //DONE
        $methods[$relation instanceof MorphToMany] = 'MorphToMany'; // DONE
        $methods[$relation instanceof HasOne] = 'HasOne';
        $methods[$relation instanceof HasMany] = 'HasMany';
        $methods[$relation instanceof HasManyThrough] = 'HasManyThrough'; //DONE
        $methods[$relation instanceof MorphMany] = 'MorphMany';
        $methods[$relation instanceof MorphOne] = 'MorphOne';

        return $methods[true];
    }

    /**
     * @param  string              $name
     * @param  Relation            $relation
     * @param  string              $cacheKey
     * @return AssociationStubBase
     */
    protected static function handleBelongsTo(string $name, Relation $relation, $cacheKey = 'BelongsTo'): AssociationStubBase
    {
        $stub = new AssociationStubMonomorphic();
        $keyChain = self::getKeyChain($relation, $cacheKey);
        $stub->setBaseType(get_class(self::getParent($relation)));
        $stub->setRelationName($name);
        $stub->setThroughFieldChain($keyChain);
        $stub->setKeyField($keyChain[1]);
        $stub->setForeignField($keyChain[0]);
        $stub->setTargType(get_class($relation->getRelated()));
        $stub->setMultiplicity(AssociationStubRelationType::ONE());
        return $stub;
    }

    /**
     * @param  string              $name
     * @param  Relation            $relation
     * @param  string              $cacheKey
     * @return AssociationStubBase
     */
    protected static function handleMorphTo(string $name, Relation $relation, $cacheKey = 'BelongsTo'): AssociationStubBase
    {
        $stub = new AssociationStubPolymorphic();
        $keyChain = self::getKeyChain($relation, $cacheKey);
        $stub->setBaseType(get_class(self::getParent($relation)));
        $stub->setRelationName($name);
        $stub->setThroughFieldChain($keyChain);
        $stub->setKeyField($keyChain[0] ?: $relation->getRelated()->getKeyName());
        $stub->setForeignField($keyChain[0]);
        $stub->setMultiplicity(AssociationStubRelationType::ONE());
        $stub->setTargType(null);
        return $stub;
    }


    /**
     * @param  string              $name
     * @param  Relation            $relation
     * @param  string              $cacheKey
     * @return AssociationStubBase
     */
    protected static function handleBelongsToMany(string $name, Relation $relation, $cacheKey = 'BelongsToMany'): AssociationStubBase
    {
        $stub = new AssociationStubMonomorphic();
        $keyChain = self::getKeyChain($relation, $cacheKey);
        $stub->setBaseType(get_class(self::getParent($relation)));
        $stub->setRelationName($name);
        $stub->setThroughFieldChain($keyChain);
        $stub->setMultiplicity(AssociationStubRelationType::MANY());
        $stub->setTargType(get_class($relation->getRelated()));
        $stub->setKeyField($keyChain[2]);
        $stub->setForeignField($keyChain[1]);
        return $stub;
    }
    /**
     * @param  string              $name
     * @param  Relation            $relation
     * @param  string              $cacheKey
     * @return AssociationStubBase
     */
    protected static function handleHasManyThrough(string $name, Relation $relation, $cacheKey = 'HasManyThrough'): AssociationStubBase
    {
        $farParentGetter = function () {
            return $this->farParent;
        };
        $farParent = call_user_func($farParentGetter->bindTo($relation, HasManyThrough::class));
        $keyChain = self::getKeyChain($relation, $cacheKey);
        $stub = new AssociationStubMonomorphic();
        $stub->setBaseType(get_class($farParent));
        $stub->setRelationName($name);
        $stub->setThroughFieldChain($keyChain);
        $stub->setMultiplicity(AssociationStubRelationType::MANY());
        $stub->setTargType(get_class($relation->getRelated()));
        $stub->setThroughField($keyChain[1]);
        $stub->setKeyField($keyChain[3]);
        $stub->setForeignField($keyChain[2]);
        return $stub;
    }

    /**
     * @param  string              $name
     * @param  Relation            $relation
     * @param  string              $cacheKey
     * @return AssociationStubBase
     */
    protected static function handleMorphToMany(string $name, Relation $relation, $cacheKey = 'BelongsToMany'): AssociationStubBase
    {
        $inverseGetter = function () {
            return $this->inverse;
        };
        $inverse = call_user_func($inverseGetter->bindTo($relation, MorphToMany::class));
        $stub = new AssociationStubPolymorphic();
        $keyChain = self::getKeyChain($relation, $cacheKey);
        $stub->setBaseType(get_class(self::getParent($relation)));
        $stub->setRelationName($name);
        $stub->setThroughFieldChain($keyChain);
        $stub->setKeyField($keyChain[2]);
        $stub->setForeignField($inverse ? null : $keyChain[1]);
        $stub->setMultiplicity(AssociationStubRelationType::MANY());
        $stub->setTargType($inverse ? null : get_class($relation->getRelated()));
        return $stub;
    }

    protected static function handleHasOne(string $name, Relation $relation, $cacheKey = 'HasOneOrMany')
    {
        $stub = new AssociationStubMonomorphic();
        $keyChain = self::getKeyChain($relation, $cacheKey);
        $stub->setRelationName($name);
        $stub->setThroughFieldChain($keyChain);
        $stub->setBaseType(get_class(self::getParent($relation)));
        $stub->setKeyField($keyChain[0]);
        $stub->setForeignField($keyChain[1]);
        $stub->setTargType(get_class($relation->getRelated()));
        $stub->setMultiplicity(AssociationStubRelationType::NULL_ONE());
        return $stub;
    }


    protected static function handleHasMany(string $name, Relation $relation, $cacheKey = 'HasOneOrMany')
    {
        $stub = new AssociationStubMonomorphic();
        $keyChain = self::getKeyChain($relation, $cacheKey);
        $stub->setBaseType(get_class(self::getParent($relation)));
        $stub->setRelationName($name);
        $stub->setThroughFieldChain($keyChain);
        $stub->setKeyField($keyChain[0]);
        $stub->setForeignField($keyChain[1]);
        $stub->setTargType(get_class($relation->getRelated()));
        $stub->setMultiplicity(AssociationStubRelationType::MANY());
        $stub->setBaseType(get_class(self::getParent($relation)));
        return $stub;
    }

    /**
     * @param  string                     $name
     * @param  MorphOne                   $relation
     * @param  string                     $cacheKey
     * @return AssociationStubPolymorphic
     */
    protected static function handleMorphOne(string $name, Relation $relation, $cacheKey = 'HasOneOrMany')
    {
        $stub = new AssociationStubPolymorphic();
        $keyChain = self::getKeyChain($relation, $cacheKey);
        $stub->setBaseType(get_class(self::getParent($relation)));
        $stub->setRelationName($name);
        $stub->setThroughFieldChain($keyChain);
        $stub->setKeyField($keyChain[1]);
        $stub->setForeignField($keyChain[0]);
        $stub->setTargType(get_class($relation->getRelated()));
        $stub->setMultiplicity(AssociationStubRelationType::NULL_ONE());
        return $stub;
    }

    /**
     * @param  string                     $name
     * @param  MorphMany                  $relation
     * @param  string                     $cacheKey
     * @return AssociationStubPolymorphic
     */
    protected static function handleMorphMany(string $name, Relation $relation, $cacheKey = 'HasOneOrMany')
    {
        $stub = new AssociationStubPolymorphic();
        $keyChain = self::getKeyChain($relation, $cacheKey);
        $stub->setBaseType(get_class(self::getParent($relation)));
        $stub->setRelationName($name);
        $stub->setThroughFieldChain($keyChain);
        $stub->setKeyField($keyChain[1]);
        $stub->setForeignField($keyChain[0]);
        $stub->setMultiplicity(AssociationStubRelationType::MANY());
        $stub->setTargType(get_class($relation->getRelated()));
        return $stub;
    }

    private static function getParent(Relation $relation)
    {
        $getter = function () {
            return $this->parent;
        };
        return call_user_func($getter->bindTo($relation, Relation::class));
    }

    private static function getKeyChain(Relation $relation, string $cacheKey) : array
    {
        $fields = self::$fieldOrderCache[$cacheKey];
        $getter = function () use ($fields) {
            $carry = [];
            foreach ($fields as $item) {
                $v = $this->{$item};
                if ($v == null && $item == 'ownerKey') {
                    $carry[] = null;
                    continue;
                }
                $segments = explode('.', $this->{$item});
                $carry[] = end($segments);
            }
            return $carry;
        };
        return call_user_func($getter->bindTo($relation, Relation::class));
    }

    private static $fieldOrderCache = [
        'BelongsTo' => ['ownerKey', 'foreignKey'],
        'BelongsToMany' => ['parentKey','foreignPivotKey','relatedPivotKey','relatedKey'],
        'HasOneOrMany' => ['localKey', 'foreignKey' ],
        'HasManyThrough' => ['localKey', 'firstKey', 'secondLocalKey', 'secondKey'],
    ];
}
