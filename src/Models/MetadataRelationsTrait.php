<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 13/02/20
 * Time: 1:08 PM.
 */
namespace AlgoWeb\PODataLaravel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use POData\Common\InvalidOperationException;

trait MetadataRelationsTrait
{
    use MetadataKeyMethodNamesTrait;

    protected static $relationHooks = [];
    protected static $relationCategories = [];


    /**
     * Get model's relationships.
     *
     * @throws InvalidOperationException
     * @throws \ReflectionException
     * @return array
     */
    public function getRelationships($temping = false)
    {
        if($temping) {
            $rels = $this->getRelationshipsFromMethods(true);
            return array_unique(array_merge(
                array_keys($rels['UnknownPolyMorphSide']),
                array_keys($rels['KnownPolyMorphSide']),
                array_keys($rels['HasOne']),
                array_keys($rels['HasMany'])
            ));
        }
        if (empty(static::$relationHooks)) {
            $hooks = [];

            $rels = $this->getRelationshipsFromMethods(true);

            $this->getRelationshipsUnknownPolyMorph($rels, $hooks);

            $this->getRelationshipsKnownPolyMorph($rels, $hooks);

            $this->getRelationshipsHasOne($rels, $hooks);

            $this->getRelationshipsHasMany($rels, $hooks);

            static::$relationHooks = $hooks;
        }

        return static::$relationHooks;
    }

    /**
     * Is this model the known side of at least one polymorphic relation?
     *
     * @throws InvalidOperationException
     * @throws \ReflectionException
     */
    public function isKnownPolymorphSide()
    {
        // isKnownPolymorph needs to be checking KnownPolymorphSide results - if you're checking UnknownPolymorphSide,
        // you're turned around
        $rels = $this->getRelationshipsFromMethods();
        return !empty($rels['KnownPolyMorphSide']);
    }

    /**
     * Is this model on the unknown side of at least one polymorphic relation?
     *
     * @throws InvalidOperationException
     * @throws \ReflectionException
     */
    public function isUnknownPolymorphSide()
    {
        // isUnknownPolymorph needs to be checking UnknownPolymorphSide results - if you're checking KnownPolymorphSide,
        // you're turned around
        $rels = $this->getRelationshipsFromMethods();
        return !empty($rels['UnknownPolyMorphSide']);
    }
    /**
     * @param \ReflectionMethod $method
     * @return string
     * @throws InvalidOperationException
     */
    protected function getCodeForMethod(\ReflectionMethod $method) : string
    {
        $fileName = $method->getFileName();

        $file = new \SplFileObject($fileName);
        $file->seek($method->getStartLine() - 1);
        $code = '';
        while ($file->key() < $method->getEndLine()) {
            $code .= $file->current();
            $file->next();
        }

        $code = trim(preg_replace('/\s\s+/', '', $code));
        if (false === stripos($code, 'function')) {
            $msg = 'Function definition must have keyword \'function\'';
            throw new InvalidOperationException($msg);
        }
        $begin = strpos($code, 'function(');
        $code = substr($code, $begin, strrpos($code, '}') - $begin + 1);
        $lastCode = $code[strlen($code) - 1];
        if ('}' != $lastCode) {
            $msg = 'Final character of function definition must be closing brace';
            throw new InvalidOperationException($msg);
        }
        return $code;
    }

    /**
     * @param bool $biDir
     *
     * @throws InvalidOperationException
     * @throws \ReflectionException
     * @return array
     */
    protected function getRelationshipsFromMethods(bool $biDir = false)
    {
        $biDirVal = intval($biDir);
        $isCached = isset(static::$relationCategories[$biDirVal]) && !empty(static::$relationCategories[$biDirVal]);
        if ($isCached) {
            return static::$relationCategories[$biDirVal];
        }
        /** @var Model $model */
        $model = $this;
        $relationships = [
            'HasOne' => [],
            'UnknownPolyMorphSide' => [],
            'HasMany' => [],
            'KnownPolyMorphSide' => []
        ];
        $methods = $this->getModelClassMethods($model);
        foreach ($methods as $method) {
            //Use reflection to inspect the code, based on Illuminate/Support/SerializableClosure.php
            $reflection = new \ReflectionMethod($model, $method);
            $code = $this->getCodeForMethod($reflection);
            foreach (static::$relTypes as $relation) {
                //Resolve the relation's model to a Relation object.
                if (
                    !stripos($code, sprintf('$this->%s(', $relation)) ||
                    !(($relationObj = $model->$method()) instanceof Relation) ||
                    !in_array(MetadataTrait::class, class_uses($relObject = $relationObj->getRelated()))
                ) {
                    continue;
                }
                $targetClass = $relation == 'morphTo' ?
                    '\Illuminate\Database\Eloquent\Model|\Eloquent' :
                    '\\' . get_class($relObject);
                $targObject = $biDir ? $relationObj : $targetClass;
                $relToKeyMap = [
                    'morphedByMany' => ['UnknownPolyMorphSide','HasMany'],
                    'morphToMany' => ['KnownPolyMorphSide', 'HasMany'],
                    'morphMany' => ['KnownPolyMorphSide', 'HasMany'],
                    'hasMany' => ['HasMany'],
                    'hasManyThrough' => ['HasMany'],
                    'belongsToMany' => ['HasMany'],
                    'morphOne' => ['KnownPolyMorphSide', 'HasOne'],
                    'hasOne' => ['HasOne'],
                    'belongsTo' => ['HasOne'],
                    'morphTo' => ['UnknownPolyMorphSide'],
                ];
                foreach ($relToKeyMap[$relation] as $key) {
                    $relationships[$key][$method] = $targObject;
                }
            }
        }
        return static::$relationCategories[$biDirVal] = $relationships;
    }

    /**
     * @param  array                     $rels
     * @param  array                     $hooks
     * @throws InvalidOperationException
     */
    protected function getRelationshipsHasMany(array $rels, array &$hooks)
    {
        /**
         * @var string   $property
         * @var Relation $relation
         */
        foreach ($rels['HasMany'] as $property => $relation) {
            if ($relation instanceof MorphMany || $relation instanceof MorphToMany) {
                continue;
            }
            $mult = '*';
            $targ = get_class($relation->getRelated());
            $keyName = $this->polyglotFkKey($relation);
            $localName = $this->polyglotRkKey($relation);
            $thruName = $relation instanceof HasManyThrough ?
                $this->polyglotThroughKey($relation) :
                null;

            $first = $keyName;
            $last = $localName;
            $this->addRelationsHook($hooks, $first, $property, $last, $mult, $targ, null, $thruName);
        }
    }

    /**
     * @param  array                     $rels
     * @param  array                     $hooks
     * @throws InvalidOperationException
     */
    protected function getRelationshipsHasOne(array $rels, array &$hooks)
    {
        /**
         * @var string   $property
         * @var Relation $foo
         */
        foreach ($rels['HasOne'] as $property => $foo) {
            if ($foo instanceof MorphOne) {
                continue;
            }
            $isBelong = $foo instanceof BelongsTo;
            $mult = $isBelong ? '1' : '0..1';
            $targ = get_class($foo->getRelated());

            $keyName = $this->polyglotFkKey($foo);
            $localName = $this->polyglotRkKey($foo);
            $first = $isBelong ? $localName : $keyName;
            $last = $isBelong ? $keyName : $localName;
            $this->addRelationsHook($hooks, $first, $property, $last, $mult, $targ);
        }
    }

    /**
     * @param  array                     $rels
     * @param  array                     $hooks
     * @throws InvalidOperationException
     */
    protected function getRelationshipsKnownPolyMorph(array $rels, array &$hooks)
    {
        /**
         * @var string   $property
         * @var Relation $foo
         */
        foreach ($rels['KnownPolyMorphSide'] as $property => $foo) {
            $isMany = $foo instanceof MorphToMany;
            $targ = get_class($foo->getRelated());
            $mult = $isMany || $foo instanceof MorphMany ? '*' : '1';
            $mult = $foo instanceof MorphOne ? '0..1' : $mult;

            $keyName = $this->polyglotFkKey($foo);
            $localName = $this->polyglotRkKey($foo);
            $first = $isMany ? $keyName : $localName;
            $last = $isMany ? $localName : $keyName;
            $this->addRelationsHook($hooks, $first, $property, $last, $mult, $targ, 'unknown');
        }
    }

    /**
     * @param  array                     $rels
     * @param  array                     $hooks
     * @throws InvalidOperationException
     */
    protected function getRelationshipsUnknownPolyMorph(array $rels, array &$hooks)
    {
        /**
         * @var string   $property
         * @var Relation $foo
         */
        foreach ($rels['UnknownPolyMorphSide'] as $property => $foo) {
            $isMany = $foo instanceof MorphToMany;
            $targ = get_class($foo->getRelated());
            $mult = $isMany ? '*' : '1';

            $keyName = $this->polyglotFkKey($foo);
            $localName = $this->polyglotRkKey($foo);

            $first = $keyName;
            $last = (isset($localName) && '' != $localName) ? $localName : $foo->getRelated()->getKeyName();
            $this->addRelationsHook($hooks, $first, $property, $last, $mult, $targ, 'known');
        }
    }

    /**
     * @param             $hooks
     * @param             $foreignField
     * @param             $RelationName
     * @param             $localKey
     * @param             $mult
     * @param string|null $relatedObject
     * @param mixed|null  $type
     * @param mixed|null  $through
     */
    protected function addRelationsHook(
        array &$hooks,
        $foreignField,
        $RelationName,
        $localKey,
        $mult,
        $relatedObject,
        $type = null,
        $through = null
    ) {
        if (!isset($hooks[$foreignField])) {
            $hooks[$foreignField] = [];
        }
        if (!isset($hooks[$foreignField][$relatedObject])) {
            $hooks[$foreignField][$relatedObject] = [];
        }
        $hooks[$foreignField][$relatedObject][$RelationName] = [
            'property' => $RelationName,
            'local' => $localKey,
            'through' => $through,
            'multiplicity' => $mult,
            'type' => $type
        ];
    }
}
