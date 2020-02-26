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

abstract class AssociationStubFactory
{
    /**
     * @param Model $parent
     * @param string $name
     * @param Relation $relation
     * @return AssociationStubBase
     * @throws InvalidOperationException
     */
    public static function associationStubFromRelation(Model $parent, string $name): AssociationStubBase
    {
        $relation = $parent->{$name}();
        $handler = self::getHandlerMethod($relation);
        /**
         * @var AssociationStubBase $stub
         */
        $stub = self::{'handle' . $handler}($name, $relation);
        $stub->setBaseType(get_class($parent));
        if (!$stub->isOk()) {
            throw new InvalidOperationException('Generated stub not consistent');
        }
        return $stub;
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
     * @return AssociationStubMonomorphic
     */
    protected static function handleBelongsTo(string $name, Relation $relation, $cacheKey = 'BelongsTo'): AssociationStubMonomorphic
    {
        $stub = new AssociationStubMonomorphic();
        $keyChain = self::getKeyChain($relation, $cacheKey);
        $stub->setRelationName($name);
        $stub->setThroughFieldChain($keyChain);
        $stub->setKeyField($keyChain[0]);
        $stub->setForeignField($keyChain[1]);
        $stub->setTargType(get_class($relation->getRelated()));
        $stub->setMultiplicity(AssociationStubRelationType::ONE());
        return $stub;
    }

    /**
     * @param  string              $name
     * @param  Relation            $relation
     * @param  string              $cacheKey
     * @return AssociationStubPolymorphic
     */
    protected static function handleMorphTo(string $name, Relation $relation, $cacheKey = 'MorphTo'): AssociationStubPolymorphic
    {
        $stub = new AssociationStubPolymorphic();
        $keyChain = self::getKeyChain($relation, $cacheKey);
        $stub->setRelationName($name);
        $stub->setThroughFieldChain($keyChain);
        $stub->setKeyField($keyChain[2] ?: $relation->getRelated()->getKeyName());
        $stub->setForeignField($keyChain[2]);
        $stub->setMultiplicity(AssociationStubRelationType::ONE());
        $stub->setTargType(null);
        $stub->setMorphType($keyChain[1]);
        return $stub;
    }


    /**
     * @param  string              $name
     * @param  Relation            $relation
     * @param  string              $cacheKey
     * @return AssociationStubMonomorphic
     */
    protected static function handleBelongsToMany(string $name, Relation $relation, $cacheKey = 'BelongsToMany'): AssociationStubMonomorphic
    {
        $stub = new AssociationStubMonomorphic();
        $keyChain = self::getKeyChain($relation, $cacheKey);
        $stub->setRelationName($name);
        $stub->setThroughFieldChain($keyChain);
        $stub->setMultiplicity(AssociationStubRelationType::MANY());
        $stub->setTargType(get_class($relation->getRelated()));
        $stub->setKeyField($keyChain[0]);
        $stub->setForeignField($keyChain[3]);
        return $stub;
    }
    /**
     * @param  string              $name
     * @param  Relation            $relation
     * @param  string              $cacheKey
     * @return AssociationStubMonomorphic
     */
    protected static function handleHasManyThrough(string $name, Relation $relation, $cacheKey = 'HasManyThrough'): AssociationStubMonomorphic
    {
        $keyChain = self::getKeyChain($relation, $cacheKey);
        $stub = new AssociationStubMonomorphic();
        $stub->setRelationName($name);
        $stub->setThroughFieldChain($keyChain);
        $stub->setMultiplicity(AssociationStubRelationType::MANY());
        $stub->setTargType(get_class($relation->getRelated()));
        $stub->setKeyField($keyChain[0]);
        $stub->setForeignField($keyChain[3]);
        return $stub;
    }

    /**
     * @param  string              $name
     * @param  Relation            $relation
     * @param  string              $cacheKey
     * @return AssociationStubPolymorphic
     */
    protected static function handleMorphToMany(string $name, Relation $relation, $cacheKey = 'MorphToMany'): AssociationStubBase
    {
        //return self::handleBelongsToMany($name,$relation);
        //TODO: investigate if this could be treated as a BelongsToMany Or more importantly a Monomorphic as we know both sides
        $inverse = self::getKeyChain($relation, "inverse")[0];
        $stub = new AssociationStubPolymorphic();
        $keyChain = self::getKeyChain($relation, $cacheKey);
        $stub->setRelationName($name);
        $stub->setThroughFieldChain($keyChain);
        $stub->setKeyField($keyChain[3]);
        $stub->setForeignField($inverse ? null : $keyChain[1]);
        $stub->setMultiplicity(AssociationStubRelationType::MANY());
        $stub->setMorphType($keyChain[2]);
        $stub->setTargType($inverse ? null : get_class($relation->getRelated()));
        return $stub;
    }

    /**
     * @param string $name
     * @param Relation $relation
     * @param string $cacheKey
     * @return AssociationStubMonomorphic
     */
    protected static function handleHasOne(string $name, Relation $relation, $cacheKey = 'HasOneOrMany'): AssociationStubMonomorphic
    {
        $stub = new AssociationStubMonomorphic();
        $keyChain = self::getKeyChain($relation, $cacheKey);
        $stub->setRelationName($name);
        $stub->setThroughFieldChain($keyChain);
        $stub->setKeyField($keyChain[0]);
        $stub->setForeignField($keyChain[1]);
        $stub->setTargType(get_class($relation->getRelated()));
        $stub->setMultiplicity(AssociationStubRelationType::NULL_ONE());
        return $stub;
    }

    /**
     * @param string $name
     * @param Relation $relation
     * @param string $cacheKey
     * @return AssociationStubMonomorphic
     */
    protected static function handleHasMany(string $name, Relation $relation, $cacheKey = 'HasOneOrMany'): AssociationStubMonomorphic
    {
        $stub = new AssociationStubMonomorphic();
        $keyChain = self::getKeyChain($relation, $cacheKey);
        $stub->setRelationName($name);
        $stub->setThroughFieldChain($keyChain);
        $stub->setKeyField($keyChain[0]);
        $stub->setForeignField($keyChain[1]);
        $stub->setTargType(get_class($relation->getRelated()));
        $stub->setMultiplicity(AssociationStubRelationType::MANY());
        return $stub;
    }

    /**
     * @param  string                     $name
     * @param  Relation                   $relation
     * @param  string                     $cacheKey
     * @return AssociationStubPolymorphic
     */
    protected static function handleMorphOne(string $name, Relation $relation, $cacheKey = 'MorphOneOrMany'):AssociationStubPolymorphic
    {
        $stub = new AssociationStubPolymorphic();
        $keyChain = self::getKeyChain($relation, $cacheKey);
        $stub->setRelationName($name);
        $stub->setThroughFieldChain($keyChain);
        $stub->setKeyField($keyChain[0]);
        $stub->setForeignField($keyChain[2]);
        $stub->setTargType(get_class($relation->getRelated()));
        $stub->setMorphType($keyChain[1]);
        $stub->setMultiplicity(AssociationStubRelationType::NULL_ONE());
        return $stub;
    }

    /**
     * @param  string                     $name
     * @param  Relation                   $relation
     * @param  string                     $cacheKey
     * @return AssociationStubPolymorphic
     */
    protected static function handleMorphMany(string $name, Relation $relation, $cacheKey = 'MorphOneOrMany'): AssociationStubPolymorphic
    {
        $stub = new AssociationStubPolymorphic();
        $keyChain = self::getKeyChain($relation, $cacheKey);
        $stub->setRelationName($name);
        $stub->setThroughFieldChain($keyChain);
        $stub->setKeyField($keyChain[0]);
        $stub->setMorphType($keyChain[1]);
        $stub->setForeignField($keyChain[2]);
        $stub->setMultiplicity(AssociationStubRelationType::MANY());
        $stub->setTargType(get_class($relation->getRelated()));
        return $stub;
    }

    /**
     * @param Relation $relation
     * @param string $cacheKey
     * @return array
     */
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
                //TODO: investigate if this is needed can we use quailifed keys?
                $segments = explode('.', strval($this->{$item}));
                $carry[] = end($segments);
            }
            return $carry;
        };
        return call_user_func($getter->bindTo($relation, Relation::class));
    }

    private static $fieldOrderCache = [
        'BelongsTo' => ['foreignKey', 'ownerKey'],
        'BelongsToMany' => ['parentKey','foreignPivotKey','relatedPivotKey','relatedKey'],
        'HasOneOrMany' => ['localKey', 'foreignKey' ],
        'HasManyThrough' => ['localKey', 'firstKey', 'secondLocalKey', 'secondKey'],
        'MorphToMany' => ['parentKey','foreignPivotKey','morphType', 'relatedPivotKey','relatedKey'],
        'MorphTo' => ['foreignKey', 'morphType', 'ownerKey'],
        'MorphOneOrMany' => ['foreignKey', 'morphType', 'localKey'],
        'inverse' => ['inverse'], // TODO: currently used to get inverse, should be removed when morphtomany is fixed
    ];
}
