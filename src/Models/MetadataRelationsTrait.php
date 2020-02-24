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
    public function getRelationships()
    {
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
        if (!$isCached) {
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
                $fileName = $reflection->getFileName();

                $file = new \SplFileObject($fileName);
                $file->seek($reflection->getStartLine()-1);
                $code = '';
                while ($file->key() < $reflection->getEndLine()) {
                    $code .= $file->current();
                    $file->next();
                }

                $code = trim(preg_replace('/\s\s+/', '', $code));
                if (false === stripos($code, 'function')) {
                    $msg = 'Function definition must have keyword \'function\'';
                    throw new InvalidOperationException($msg);
                }
                $begin = strpos($code, 'function(');
                $code = substr($code, /* @scrutinizer ignore-type */$begin, strrpos($code, '}')-$begin+1);
                $lastCode = $code[strlen(/* @scrutinizer ignore-type */$code)-1];
                if ('}' != $lastCode) {
                    $msg = 'Final character of function definition must be closing brace';
                    throw new InvalidOperationException($msg);
                }
                foreach (static::$relTypes as $relation) {
                    $search = '$this->' . $relation . '(';
                    $found = stripos(/* @scrutinizer ignore-type */$code, $search);
                    if (!$found) {
                        continue;
                    }
                    //Resolve the relation's model to a Relation object.
                    $relationObj = $model->$method();
                    if (!($relationObj instanceof Relation)) {
                        continue;
                    }
                    $relObject = $relationObj->getRelated();
                    $relatedModel = '\\' . get_class($relObject);
                    if (!in_array(MetadataTrait::class, class_uses($relatedModel))) {
                        continue;
                    }
                    $targObject = $biDir ? $relationObj : $relatedModel;
                    if (in_array($relation, static::$manyRelTypes)) {
                        //Collection or array of models (because Collection is Arrayable)
                        $relationships['HasMany'][$method] = $targObject;
                    } elseif ('morphTo' === $relation) {
                        // Model isn't specified because relation is polymorphic
                        $relationships['UnknownPolyMorphSide'][$method] =
                            $biDir ? $relationObj : '\Illuminate\Database\Eloquent\Model|\Eloquent';
                    } else {
                        //Single model is returned
                        $relationships['HasOne'][$method] = $targObject;
                    }
                    if (in_array($relation, ['morphMany', 'morphOne', 'morphToMany'])) {
                        $relationships['KnownPolyMorphSide'][$method] = $targObject;
                    }
                    if (in_array($relation, ['morphedByMany'])) {
                        $relationships['UnknownPolyMorphSide'][$method] = $targObject;
                    }
                }
            }
            static::$relationCategories[$biDirVal] = $relationships;
        }
        return static::$relationCategories[$biDirVal];
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
            $thruName = $this->polyglotThroughKey($relation);

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
            $mult = $isMany ? '*' : ($foo instanceof MorphMany ? '*' : '1');
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
