<?php
namespace AlgoWeb\PODataLaravel\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\App as App;
use Illuminate\Database\Eloquent\Relations\Relation;
use POData\Providers\Metadata\ResourceStreamInfo;
use POData\Providers\Metadata\Type\EdmPrimitiveType;
use Illuminate\Database\Eloquent\Model;

trait MetadataTrait
{
    protected static $methodPrimary = [];
    protected static $methodAlternate = [];
    protected $loadEagerRelations = [];

    /*
     * Array to record mapping between doctrine types and OData types
     */
    protected $mapping = [
        'integer' => EdmPrimitiveType::INT32,
        'string' => EdmPrimitiveType::STRING,
        'datetime' => EdmPrimitiveType::DATETIME,
        'float' => EdmPrimitiveType::SINGLE,
        'decimal' => EdmPrimitiveType::DECIMAL,
        'text' => EdmPrimitiveType::STRING,
        'boolean' => EdmPrimitiveType::BOOLEAN,
        'blob' => 'stream'
    ];

    /*
     * Retrieve and assemble this model's metadata for OData packaging
     */
    public function metadata()
    {
        assert($this instanceof Model, get_class($this));

        // Break these out separately to enable separate reuse
        $connect = $this->getConnection();
        $builder = $connect->getSchemaBuilder();

        $table = $this->getTable();

        if (!$builder->hasTable($table)) {
            return [];
        }

        $columns = $builder->getColumnListing($table);
        $mask = $this->metadataMask();
        $columns = array_intersect($columns, $mask);

        $tableData = [];

        $rawFoo = $connect->getDoctrineSchemaManager()->listTableColumns($table);
        $foo = [];
        $getters = $this->collectGetters();

        foreach ($rawFoo as $key => $val) {
            // Work around glitch in Doctrine when reading from MariaDB which added ` characters to root key value
            $key = trim($key, '`');
            $foo[$key] = $val;
        }

        foreach ($columns as $column) {
            // Doctrine schema manager returns columns with lowercased names
            $rawColumn = $foo[strtolower($column)];
            $nullable = !($rawColumn->getNotNull());
            $fillable = in_array($column, $this->getFillable());
            $rawType = $rawColumn->getType();
            $type = $rawType->getName();
            $default = $this->$column;
            $tableData[$column] = ['type' => $type,
                'nullable' => $nullable,
                'fillable' => $fillable,
                'default' => $default
            ];
        }

        foreach ($getters as $get) {
            if (isset($tableData[$get])) {
                continue;
            }
            $default = $this->$get;
            $tableData[$get] = ['type' => 'text', 'nullable' => true, 'fillable' => false, 'default' => $default];
        }

        return $tableData;
    }

    /*
     * Return the set of fields that are permitted to be in metadata
     * - following same visible-trumps-hidden guideline as Laravel
     */
    public function metadataMask()
    {
        $attribs = array_keys($this->getAllAttributes());

        $visible = $this->getVisible();
        $hidden = $this->getHidden();
        if (0 < count($visible)) {
            assert(!empty($visible));
            $attribs = array_intersect($visible, $attribs);
        } elseif (0 < count($hidden)) {
            assert(!empty($hidden));
            $attribs = array_diff($attribs, $hidden);
        }

        return $attribs;
    }

    /*
     * Get the endpoint name being exposed
     *
     */
    public function getEndpointName()
    {
        $endpoint = isset($this->endpoint) ? $this->endpoint : null;

        if (!isset($endpoint)) {
            $bitter = get_class();
            $name = substr($bitter, strrpos($bitter, '\\')+1);
            return strtolower($name);
        }
        return strtolower($endpoint);
    }

    /*
     * Assemble this model's OData metadata as xml schema
     */
    public function getXmlSchema($MetaNamespace = 'Data')
    {
        $raw = $this->metadata();
        if ([] == $raw) {
            return null;
        }

        $metadata = App::make('metadata');

        $rf = new \ReflectionClass(get_class($this));
        $complex = $metadata->addEntityType($rf, $rf->getShortName(), $MetaNamespace);
        $keyName = $this->getKeyName();
        if (null != $keyName) {
            $metadata->addKeyProperty($complex, $keyName, $this->mapping[$raw[$keyName]['type']]);
        }

        foreach ($raw as $key => $secret) {
            if ($key == $keyName) {
                continue;
            }
            if ($secret['type'] == 'blob') {
                $complex->setMediaLinkEntry(true);
                $streamInfo = new ResourceStreamInfo($key);
                assert($complex->isMediaLinkEntry());
                $complex->addNamedStream($streamInfo);
                continue;
            }
            $nullable = $secret['nullable'];
            $default = $secret['default'];
            // tag as isBag?
            $metadata->addPrimitiveProperty(
                $complex,
                $key,
                $this->mapping[$secret['type']],
                false,
                $default,
                $nullable
            );
        }

        return $complex;
    }

    public function hookUpRelationships($entityTypes, $resourceSets)
    {
        assert(is_array($entityTypes) && is_array($resourceSets), 'Both entityTypes and resourceSets must be arrays');
        $metadata = App::make('metadata');
        $rel = $this->getRelationshipsFromMethods();
        $thisClass = get_class($this);
        $thisInTypes = array_key_exists($thisClass, $entityTypes);
        $thisInSets = array_key_exists($thisClass, $resourceSets);

        if (!($thisInSets && $thisInTypes)) {
            return $rel;
        }

        $resourceType = $entityTypes[$thisClass];
        // if $r is in $combined keys, then its in keyspaces of both $entityTypes and $resourceSets
        $combinedKeys = array_intersect(array_keys($entityTypes), array_keys($resourceSets));
        foreach ($rel['HasOne'] as $n => $r) {
            $r = trim($r, '\\');
            if (in_array($r, $combinedKeys)) {
                $targResourceSet = $resourceSets[$r];
                $metadata->addResourceReferenceProperty($resourceType, $n, $targResourceSet);
            }
        }
        foreach ($rel['HasMany'] as $n => $r) {
            $r = trim($r, '\\');
            if (in_array($r, $combinedKeys)) {
                $targResourceSet = $resourceSets[$r];
                $metadata->addResourceSetReferenceProperty($resourceType, $n, $targResourceSet);
            }
        }
        return $rel;
    }

    public function getRelationships()
    {
        $hooks = [];

        $rels = $this->getRelationshipsFromMethods(true);

        $this->getRelationshipsUnknownPolyMorph($rels, $hooks);

        $this->getRelationshipsKnownPolyMorph($rels, $hooks);

        $this->getRelationshipsHasOne($rels, $hooks);

        $this->getRelationshipsHasMany($rels, $hooks);

        return $hooks;
    }

    protected function getAllAttributes()
    {
        // Adapted from http://stackoverflow.com/a/33514981
        // $columns = $this->getFillable();
        // Another option is to get all columns for the table like so:
        $builder = $this->getConnection()->getSchemaBuilder();
        $columns = $builder->getColumnListing($this->getTable());
        // but it's safer to just get the fillable fields

        $attributes = $this->getAttributes();

        foreach ($columns as $column) {
            if (!array_key_exists($column, $attributes)) {
                $attributes[$column] = null;
            }
        }

        $methods = $this->collectGetters();

        foreach ($methods as $method) {
            $attributes[$method] = null;
        }

        return $attributes;
    }

    protected function getRelationshipsFromMethods($biDir = false)
    {
        $model = $this;
        $relationships = [
            'HasOne' => [],
            'UnknownPolyMorphSide'=> [],
            'HasMany'=> [],
            'KnownPolyMorphSide'=> []
        ];
        $methods = get_class_methods($model);
        if (!empty($methods)) {
            foreach ($methods as $method) {
                if (!method_exists('Illuminate\Database\Eloquent\Model', $method)
                ) {
                    //Use reflection to inspect the code, based on Illuminate/Support/SerializableClosure.php
                    $reflection = new \ReflectionMethod($model, $method);

                    $file = new \SplFileObject($reflection->getFileName());
                    $file->seek($reflection->getStartLine()-1);
                    $code = '';
                    while ($file->key() < $reflection->getEndLine()) {
                        $code .= $file->current();
                        $file->next();
                    }

                    $code = trim(preg_replace('/\s\s+/', '', $code));
                    assert(false !== stripos($code, 'function'), 'Function definition must have keyword \'function\'');
                    $begin = strpos($code, 'function(');
                    $code = substr($code, $begin, strrpos($code, '}')-$begin+1);
                    $lastCode = $code[strlen($code)-1];
                    assert('}' == $lastCode, 'Final character of function definition must be closing brace');
                    foreach ([
                                'hasMany',
                                'hasManyThrough',
                                'belongsToMany',
                                'hasOne',
                                'belongsTo',
                                'morphOne',
                                'morphTo',
                                'morphMany',
                                'morphToMany',
                                'morphedByMany'
                                ] as $relation) {
                        $search = '$this->'.$relation.'(';
                        if ($pos = stripos($code, $search)) {
                            //Resolve the relation's model to a Relation object.
                            $relationObj = $model->$method();
                            if ($relationObj instanceof Relation) {
                                $relatedModel = '\\'.get_class($relationObj->getRelated());
                                $relations = [
                                    'hasManyThrough',
                                    'belongsToMany',
                                    'hasMany',
                                    'morphMany',
                                    'morphToMany',
                                    'morphedByMany'
                                ];
                                if (in_array($relation, $relations)) {
                                    //Collection or array of models (because Collection is Arrayable)
                                    $relationships['HasMany'][$method] = $biDir ? $relationObj : $relatedModel;
                                } elseif ('morphTo' === $relation) {
                                    // Model isn't specified because relation is polymorphic
                                    $relationships['UnknownPolyMorphSide'][$method] =
                                        $biDir ? $relationObj : '\Illuminate\Database\Eloquent\Model|\Eloquent';
                                } else {
                                    //Single model is returned
                                    $relationships['HasOne'][$method] = $biDir ? $relationObj : $relatedModel;
                                }
                                if (in_array($relation, ['morphMany', 'morphOne', 'morphedByMany'])) {
                                    $relationships['KnownPolyMorphSide'][$method] =
                                        $biDir ? $relationObj : $relatedModel;
                                }
                                if (in_array($relation, ['morphToMany'])) {
                                    $relationships['UnknownPolyMorphSide'][$method] =
                                        $biDir ? $relationObj : $relatedModel;
                                }
                            }
                        }
                    }
                }
            }
        }
        return $relationships;
    }

    /**
     * Get the visible attributes for the model.
     *
     * @return array
     */
    abstract public function getVisible();

    /**
     * Get the hidden attributes for the model.
     *
     * @return array
     */
    abstract public function getHidden();

    /**
     * Get the primary key for the model.
     *
     * @return string
     */
    abstract public function getKeyName();

    /**
     * Get the current connection name for the model.
     *
     * @return string
     */
    abstract public function getConnectionName();

    /**
     * Get the database connection for the model.
     *
     * @return \Illuminate\Database\Connection
     */
    abstract public function getConnection();

    /**
     * Get all of the current attributes on the model.
     *
     * @return array
     */
    abstract public function getAttributes();

    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    abstract public function getTable();

    /**
     * Get the fillable attributes for the model.
     *
     * @return array
     */
    abstract public function getFillable();

    /**
     * Dig up all defined getters on the model
     *
     * @return array
     */
    protected function collectGetters()
    {
        $getterz = [];
        $methods = get_class_methods($this);
        foreach ($methods as $method) {
            if (12 < strlen($method) && 'get' == substr($method, 0, 3)) {
                if ('Attribute' == substr($method, -9)) {
                    $getterz[] = $method;
                }
            }
        }
        $methods = [];

        foreach ($getterz as $getter) {
            $residual = substr($getter, 3);
            $residual = substr($residual, 0, -9);
            $methods[] = $residual;
        }
        return $methods;
    }

    /**
     * @param $foo
     * @return array
     */
    private function polyglotKeyMethodNames($foo, $condition = false)
    {
        $fkList = ['getQualifiedForeignKeyName', 'getForeignKey'];
        $rkList = ['getQualifiedRelatedKeyName', 'getOtherKey', 'getOwnerKey'];

        $fkMethodName = null;
        $rkMethodName = null;
        if ($condition) {
            if (array_key_exists(get_class($foo), static::$methodPrimary)) {
                $line = static::$methodPrimary[get_class($foo)];
                $fkMethodName = $line['fk'];
                $rkMethodName = $line['rk'];
            } else {
                $methodList = get_class_methods(get_class($foo));
                $fkMethodName = 'getQualifiedForeignPivotKeyName';
                foreach ($fkList as $option) {
                    if (in_array($option, $methodList)) {
                        $fkMethodName = $option;
                        break;
                    }
                }
                assert(in_array($fkMethodName, $methodList), 'Selected method, '.$fkMethodName.', not in method list');
                $rkMethodName = 'getQualifiedRelatedPivotKeyName';
                foreach ($rkList as $option) {
                    if (in_array($option, $methodList)) {
                        $rkMethodName = $option;
                        break;
                    }
                }
                assert(in_array($rkMethodName, $methodList), 'Selected method, '.$rkMethodName.', not in method list');
                $line = ['fk' => $fkMethodName, 'rk' => $rkMethodName];
                static::$methodPrimary[get_class($foo)] = $line;
            }
        }
        return [$fkMethodName, $rkMethodName];
    }

    private function polyglotKeyMethodBackupNames($foo, $condition = false)
    {
        $fkList = ['getForeignKey'];
        $rkList = ['getOtherKey'];

        $fkMethodName = null;
        $rkMethodName = null;
        if ($condition) {
            if (array_key_exists(get_class($foo), static::$methodAlternate)) {
                $line = static::$methodAlternate[get_class($foo)];
                $fkMethodName = $line['fk'];
                $rkMethodName = $line['rk'];
            } else {
                $methodList = get_class_methods(get_class($foo));
                $fkMethodName = 'getForeignKeyName';
                foreach ($fkList as $option) {
                    if (in_array($option, $methodList)) {
                        $fkMethodName = $option;
                        break;
                    }
                }
                assert(in_array($fkMethodName, $methodList), 'Selected method, '.$fkMethodName.', not in method list');
                $rkMethodName = 'getQualifiedParentKeyName';
                foreach ($rkList as $option) {
                    if (in_array($option, $methodList)) {
                        $rkMethodName = $option;
                        break;
                    }
                }
                assert(in_array($rkMethodName, $methodList), 'Selected method, '.$rkMethodName.', not in method list');
                $line = ['fk' => $fkMethodName, 'rk' => $rkMethodName];
                static::$methodAlternate[get_class($foo)] = $line;
            }
        }
        return [$fkMethodName, $rkMethodName];
    }

    /**
     * @param $hooks
     * @param $first
     * @param $property
     * @param $last
     * @param $mult
     * @param $targ
     */
    private function addRelationsHook(&$hooks, $first, $property, $last, $mult, $targ)
    {
        if (!isset($hooks[$first])) {
            $hooks[$first] = [];
        }
        $hooks[$first][$targ] = [
            'property' => $property,
            'local' => $last,
            'multiplicity' => $mult
        ];
    }

    /**
     * @param $rels
     * @param $hooks
     */
    private function getRelationshipsHasMany($rels, &$hooks)
    {
        foreach ($rels['HasMany'] as $property => $foo) {
            if ($foo instanceof MorphMany || $foo instanceof MorphToMany) {
                continue;
            }
            $isBelong = $foo instanceof BelongsToMany;
            $mult = '*';
            $targ = get_class($foo->getRelated());

            list($fkMethodName, $rkMethodName) = $this->polyglotKeyMethodNames($foo, $isBelong);
            list($fkMethodAlternate, $rkMethodAlternate) = $this->polyglotKeyMethodBackupNames($foo, !$isBelong);

            $keyRaw = $isBelong ? $foo->$fkMethodName() : $foo->$fkMethodAlternate();
            $keySegments = explode('.', $keyRaw);
            $keyName = $keySegments[count($keySegments) - 1];
            $localRaw = $isBelong ? $foo->$rkMethodName() : $foo->$rkMethodAlternate();
            $localSegments = explode('.', $localRaw);
            $localName = $localSegments[count($localSegments) - 1];
            $first = $keyName;
            $last = $localName;
            $this->addRelationsHook($hooks, $first, $property, $last, $mult, $targ);
        }
    }

    /**
     * @param $rels
     * @param $hooks
     */
    private function getRelationshipsHasOne($rels, &$hooks)
    {
        foreach ($rels['HasOne'] as $property => $foo) {
            if ($foo instanceof MorphOne) {
                continue;
            }
            $isBelong = $foo instanceof BelongsTo;
            $mult = $isBelong ? '1' : '0..1';
            $targ = get_class($foo->getRelated());

            list($fkMethodName, $rkMethodName) = $this->polyglotKeyMethodNames($foo, $isBelong);
            list($fkMethodAlternate, $rkMethodAlternate) = $this->polyglotKeyMethodBackupNames($foo, !$isBelong);

            $keyName = $isBelong ? $foo->$fkMethodName() : $foo->$fkMethodAlternate();
            $keySegments = explode('.', $keyName);
            $keyName = $keySegments[count($keySegments) - 1];
            $localRaw = $isBelong ? $foo->$rkMethodName() : $foo->$rkMethodAlternate();
            $localSegments = explode('.', $localRaw);
            $localName = $localSegments[count($localSegments) - 1];
            $first = $isBelong ? $localName : $keyName;
            $last = $isBelong ? $keyName : $localName;
            $this->addRelationsHook($hooks, $first, $property, $last, $mult, $targ);
        }
    }

    /**
     * @param $rels
     * @param $hooks
     */
    private function getRelationshipsKnownPolyMorph($rels, &$hooks)
    {
        foreach ($rels['KnownPolyMorphSide'] as $property => $foo) {
            $isMany = $foo instanceof MorphToMany;
            $targ = get_class($foo->getRelated());
            $mult = $isMany ? '*' : $foo instanceof MorphMany ? '*' : '1';
            $mult = $foo instanceof MorphOne ? '0..1' : $mult;

            list($fkMethodName, $rkMethodName) = $this->polyglotKeyMethodNames($foo, $isMany);
            list($fkMethodAlternate, $rkMethodAlternate) = $this->polyglotKeyMethodBackupNames($foo, !$isMany);

            $keyRaw = $isMany ? $foo->$fkMethodName() : $foo->$fkMethodAlternate();
            $keySegments = explode('.', $keyRaw);
            $keyName = $keySegments[count($keySegments) - 1];
            $localRaw = $isMany ? $foo->$rkMethodName() : $foo->$rkMethodAlternate();
            $localSegments = explode('.', $localRaw);
            $localName = $localSegments[count($localSegments) - 1];
            $first = $isMany ? $keyName : $localName;
            $last = $isMany ? $localName : $keyName;
            $this->addRelationsHook($hooks, $first, $property, $last, $mult, $targ);
        }
    }

    /**
     * @param $rels
     * @param $hooks
     */
    private function getRelationshipsUnknownPolyMorph($rels, &$hooks)
    {
        foreach ($rels['UnknownPolyMorphSide'] as $property => $foo) {
            $isMany = $foo instanceof MorphToMany;
            $targ = get_class($foo->getRelated());
            $mult = $isMany ? '*' : '1';

            list($fkMethodName, $rkMethodName) = $this->polyglotKeyMethodNames($foo, $isMany);
            list($fkMethodAlternate, $rkMethodAlternate) = $this->polyglotKeyMethodBackupNames($foo, !$isMany);

            $keyRaw = $isMany ? $foo->$fkMethodName() : $foo->$fkMethodAlternate();
            $keySegments = explode('.', $keyRaw);
            $keyName = $keySegments[count($keySegments) - 1];
            $localRaw = $isMany ? $foo->$rkMethodName() : $foo->$rkMethodAlternate();
            $localSegments = explode('.', $localRaw);
            $localName = $localSegments[count($localSegments) - 1];

            $first = $keyName;
            $last = (isset($localName) && "" != $localName) ? $localName : $foo->getRelated()->getKeyName();
            $this->addRelationsHook($hooks, $first, $property, $last, $mult, $targ);
        }
    }

    /**
     * SUpplemental function to retrieve cast array for Laravel versions that do not supply hasCasts
     *
     * @return array
     */
    public function retrieveCasts()
    {
        return $this->casts;
    }

    /**
     * Return list of relations to be eager-loaded by Laravel query provider
     *
     * @return array
     */
    public function getEagerLoad()
    {
        assert(is_array($this->loadEagerRelations));
        return $this->loadEagerRelations;
    }

    /**
     * Set list of relations to be eager-loaded
     *
     * @param array $relations
     */
    public function setEagerLoad(array $relations)
    {
        $check = array_map('strval', $relations);
        assert($relations == $check, 'All supplied relations must be resolvable to strings');
        $this->loadEagerRelations = $relations;
    }
}
