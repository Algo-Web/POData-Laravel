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
        'blob' => "stream"
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
            $tableData[$column] = ['type' => $type, 'nullable' => $nullable, 'fillable' => $fillable];
        }

        foreach ($getters as $get) {
            $tableData[$get] = ['type' => 'text', 'nullable' => false, 'fillable' => false];
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
    public function getXmlSchema($MetaNamespace = "Data")
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
            if ($secret['type'] == "blob") {
                $complex->setMediaLinkEntry(true);
                $streamInfo = new ResourceStreamInfo($key);
                assert($complex->isMediaLinkEntry());
                $complex->addNamedStream($streamInfo);
                continue;
            }
            $metadata->addPrimitiveProperty($complex, $key, $this->mapping[$secret['type']]); // tag as isBag?
        }

        return $complex;
    }

    public function hookUpRelationships($entityTypes, $resourceSets)
    {
        assert(is_array($entityTypes) && is_array($resourceSets), "Both entityTypes and resourceSets must be arrays");
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
        foreach ($rel["HasOne"] as $n => $r) {
            $r = trim($r, '\\');
            if (in_array($r, $combinedKeys)) {
                $targResourceSet = $resourceSets[$r];
                $metadata->addResourceReferenceProperty($resourceType, $n, $targResourceSet);
            }
        }
        foreach ($rel["HasMany"] as $n => $r) {
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

        foreach ($rels['UnknownPolyMorphSide'] as $property => $foo) {
            $isMany = $foo instanceof MorphToMany;
            $targ = get_class($foo->getRelated());
            $mult = $isMany ? '*' : '1';

            if ($isMany) {
                $fkMethodName = method_exists($foo, 'getQualifiedForeignKeyName')
                    ? 'getQualifiedForeignKeyName' : 'getQualifiedForeignPivotKeyName';
                $rkMethodName = method_exists($foo, 'getQualifiedRelatedKeyName')
                    ? 'getQualifiedRelatedKeyName' : 'getQualifiedRelatedPivotKeyName';
            }

            $keyRaw = $isMany ? $foo->$fkMethodName() : $foo->getForeignKey();
            $keySegments = explode('.', $keyRaw);
            $keyName = $keySegments[count($keySegments) - 1];
            $localRaw = $isMany ? $foo->$rkMethodName() : $foo->getQualifiedParentKeyName();
            $localSegments = explode('.', $localRaw);
            $localName = $localSegments[count($localSegments) - 1];

            $first = $keyName;
            $last = $localName;
            if (!isset($hooks[$first])) {
                $hooks[$first] = [];
            }
            $hooks[$first][$targ] = [
                'property' => $property,
                'local' => $last,
                'multiplicity' => $mult
            ];
        }

        foreach ($rels['KnownPolyMorphSide'] as $property => $foo) {
            $isMany = $foo instanceof MorphToMany;
            $targ = get_class($foo->getRelated());
            $mult = $isMany ? '*' : $foo instanceof MorphMany ? '*' : '1';
            $mult = $foo instanceof MorphOne ? '0..1' : $mult;

            $keyRaw = $isMany ? $foo->getQualifiedForeignKeyName() : $foo->getForeignKeyName();
            $keySegments = explode('.', $keyRaw);
            $keyName = $keySegments[count($keySegments) - 1];
            $localRaw = $isMany ? $foo->getQualifiedRelatedKeyName() : $foo->getQualifiedParentKeyName();
            $localSegments = explode('.', $localRaw);
            $localName = $localSegments[count($localSegments) - 1];
            $first = $isMany ? $keyName : $localName;
            $last = $isMany ? $localName : $keyName;
            if (!isset($hooks[$first])) {
                $hooks[$first] = [];
            }
            $hooks[$first][$targ] = [
                'property' => $property,
                'local' => $last,
                'multiplicity' => $mult
            ];
        }

        foreach ($rels['HasOne'] as $property => $foo) {
            if ($foo instanceof MorphOne) {
                continue;
            }
            $isBelong = $foo instanceof BelongsTo;
            $mult = $isBelong ? '1' : '0..1';
            $targ = get_class($foo->getRelated());
            $keyName = $isBelong ? $foo->getForeignKey() : $foo->getForeignKeyName();
            $localRaw = $isBelong ? $foo->getOwnerKey() : $foo->getQualifiedParentKeyName();
            $localSegments = explode('.', $localRaw);
            $localName = $localSegments[count($localSegments) - 1];
            $first = $isBelong ? $localName : $keyName;
            $last = $isBelong ? $keyName : $localName;
            if (!isset($hooks[$first])) {
                $hooks[$first] = [];
            }
            $hooks[$first][$targ] = [
                'property' => $property,
                'local' => $last,
                'multiplicity' => $mult
            ];
        }
        foreach ($rels['HasMany'] as $property => $foo) {
            if ($foo instanceof MorphMany || $foo instanceof MorphToMany) {
                continue;
            }
            $isBelong = $foo instanceof BelongsToMany;
            $mult = '*';
            $targ = get_class($foo->getRelated());

            if ($isBelong) {
                $fkMethodName = method_exists($foo, 'getQualifiedForeignKeyName')
                    ? 'getQualifiedForeignKeyName' : 'getQualifiedForeignPivotKeyName';
                $rkMethodName = method_exists($foo, 'getQualifiedRelatedKeyName')
                    ? 'getQualifiedRelatedKeyName' : 'getQualifiedRelatedPivotKeyName';
            }

            $keyRaw = $isBelong ? $foo->$fkMethodName() : $foo->getForeignKeyName();
            $keySegments = explode('.', $keyRaw);
            $keyName = $keySegments[count($keySegments) - 1];
            $localRaw = $isBelong ? $foo->$rkMethodName() : $foo->getQualifiedParentKeyName();
            $localSegments = explode('.', $localRaw);
            $localName = $localSegments[count($localSegments) - 1];
            if (!isset($hooks[$keyName])) {
                $hooks[$keyName] = [];
            }
            $hooks[$keyName][$targ] = [
                'property' => $property,
                'local' => $localName,
                'multiplicity' => $mult
            ];
        }

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
        $relationships = array(
            "HasOne" => array(),
            "UnknownPolyMorphSide"=>array(),
            "HasMany"=>array(),
            "KnownPolyMorphSide"=>array()
        );
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
                    assert("}" == $lastCode, "Final character of function definition must be closing brace");
                    foreach (array(
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
                                ) as $relation) {
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
                                    $relationships["HasMany"][$method] = $biDir ? $relationObj : $relatedModel;
                                } elseif ($relation === "morphTo") {
                                    // Model isn't specified because relation is polymorphic
                                    $relationships["UnknownPolyMorphSide"][$method] =
                                        $biDir ? $relationObj : '\Illuminate\Database\Eloquent\Model|\Eloquent';
                                } else {
                                    //Single model is returned
                                    $relationships["HasOne"][$method] = $biDir ? $relationObj : $relatedModel;
                                }
                                if (in_array($relation, ["morphMany", "morphOne", "morphedByMany"])) {
                                    $relationships["KnownPolyMorphSide"][$method] =
                                        $biDir ? $relationObj : $relatedModel;
                                }
                                if (in_array($relation, ["morphToMany"])) {
                                    $relationships["UnknownPolyMorphSide"][$method] =
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
    public abstract function getVisible();

    /**
     * Get the hidden attributes for the model.
     *
     * @return array
     */
    public abstract function getHidden();

    /**
     * Get the primary key for the model.
     *
     * @return string
     */
    public abstract function getKeyName();

    /**
     * Get the current connection name for the model.
     *
     * @return string
     */
    public abstract function getConnectionName();

    /**
     * Get the database connection for the model.
     *
     * @return \Illuminate\Database\Connection
     */
    public abstract function getConnection();

    /**
     * Get all of the current attributes on the model.
     *
     * @return array
     */
    public abstract function getAttributes();

    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    public abstract function getTable();

    /**
     * Get the fillable attributes for the model.
     *
     * @return array
     */
    public abstract function getFillable();

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
            if (starts_with($method, 'get') && ends_with($method, 'Attribute') && 'getAttribute' != $method) {
                $getterz[] = $method;
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
}
