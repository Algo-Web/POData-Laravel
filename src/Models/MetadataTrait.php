<?php
namespace AlgoWeb\PODataLaravel\Models;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
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

        $connName = $this->getConnectionName();

            assert(
                Schema::connection($connName)->hasTable($this->table),
                $this->table.' table not present in current db, '.$this->getConnectionName()
            );
        $columns = Schema::connection($connName)->getColumnListing($this->table);
        $mask = $this->metadataMask();
        $columns = array_intersect($columns, $mask);

        $tableData = [];

        $foo = $this->getConnection()->getDoctrineSchemaManager()->listTableColumns($this->table);

        foreach ($columns as $column) {
            // Doctrine schema manager returns columns with lowercased names
            $rawColumn = $foo[strtolower($column)];
            $nullable = !($rawColumn->getNotNull());
            $fillable = in_array($column, $this->fillable);
            $rawType = $rawColumn->getType();
            $type = $rawType->getName();
            $tableData[$column] = ['type' => $type, 'nullable' => $nullable, 'fillable' => $fillable];
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
        if (0 != count($visible)) {
            $attribs = array_intersect($visible, $attribs);
        } elseif (0 != count($hidden)) {
            $attribs = array_diff($attribs, $hidden);
        }

        return $attribs;
    }

    /*
     * Assemble this model's OData metadata as xml schema
     */
    public function getXmlSchema($MetaNamespace = "Data")
    {
        $raw = $this->metadata();

        $metadata = \App::make('metadata');

        $complex = $metadata->addEntityType(new \ReflectionClass(get_class($this)), $this->table, $MetaNamespace);
        $keyName = $this->getKeyName();
        $metadata->addKeyProperty($complex, $keyName, $this->mapping[$raw[$keyName]['type']]);
        foreach ($raw as $key => $secret) {
            if ($key == $keyName) {
                continue;
            }
            if ($secret['type'] == "blob") {
                $complex->setMediaLinkEntry(true);
                $streamInfo = new ResourceStreamInfo($key);
                $complex->addNamedStream($streamInfo);
                continue;
            }
            $metadata->addPrimitiveProperty($complex, $key, $this->mapping[$secret['type']]); // tag as isBag?
        }

        return $complex;
    }

    public function hookUpRelationships($entityTypes, $resourceSets)
    {
        $metadata = \App::make('metadata');
        $rel = $this->getRelationshipsFromMethods();
        $thisClass = get_class($this);
        foreach ($rel["HasOne"] as $n => $r) {
            if ($r[0] == "\\") {
                $r = substr($r, 1);
            }
            if (array_key_exists($r, $entityTypes)
                && array_key_exists($r, $resourceSets)
                && array_key_exists($thisClass, $entityTypes)
                && array_key_exists($thisClass, $resourceSets)) {
                $resourceType = $entityTypes[$thisClass];
                $targResourceSet = $resourceSets[$r];
                $metadata->addResourceReferenceProperty($resourceType, $n, $targResourceSet);
            }
        }
        foreach ($rel["HasMany"] as $n => $r) {
            if ($r[0] == "\\") {
                $r = substr($r, 1);
            }
            if (array_key_exists($r, $entityTypes)
                 && array_key_exists($r, $resourceSets)
                 && array_key_exists($thisClass, $entityTypes)
                 && array_key_exists($thisClass, $resourceSets)) {
                $resourceType = $entityTypes[$thisClass];
                $targResourceSet = $resourceSets[$r];
                $metadata->addResourceSetReferenceProperty($resourceType, $n, $targResourceSet);
            }
        }

        return $rel;
    }

    protected function getAllAttributes()
    {
        // Adapted from http://stackoverflow.com/a/33514981
        // $columns = $this->getFillable();
        // Another option is to get all columns for the table like so:
        $connName = $this->getConnectionName();
        $columns = Schema::connection($connName)->getColumnListing($this->table);
        // but it's safer to just get the fillable fields

        $attributes = $this->getAttributes();

        foreach ($columns as $column) {
            if (!array_key_exists($column, $attributes)) {
                $attributes[$column] = null;
            }
        }
        return $attributes;
    }

    protected function getRelationshipsFromMethods()
    {
        $model = $this;
        $relationships = array(
            "HasOne" => array(),
            "UnknownPolymorphSide"=>array(),
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
                    $begin = strpos($code, 'function(');
                    $code = substr($code, $begin, strrpos($code, '}')-$begin+1);
                    foreach (array(
                                'hasMany',
                                'hasManyThrough',
                                'belongsToMany',
                                'hasOne',
                                'belongsTo',
                                'morphOne',
                                'morphTo',
                                'morphMany',
                                'morphToMany'
                                ) as $relation) {
                        $search = '$this->'.$relation.'(';
                        if ($pos = stripos($code, $search)) {
                            //Resolve the relation's model to a Relation object.
                            $relationObj = $model->$method();
                            if ($relationObj instanceof Relation) {
                                $relatedModel = '\\'.get_class($relationObj->getRelated());
                                $relations = ['hasManyThrough', 'belongsToMany', 'hasMany', 'morphMany', 'morphToMany'];
                                if (in_array($relation, $relations)) {
                                    //Collection or array of models (because Collection is Arrayable)
                                    $relationships["HasMany"][$method] = $relatedModel;
                                } elseif ($relation === "morphTo") {
                                    // Model isn't specified because relation is polymorphic
                                    $relationships["UnknownPolymorphSide"][$method] = '\Illuminate\Database\Eloquent\Model|\Eloquent';
                                    //Single model is returned
                                    $relationships["HasOne"][$method] = $relatedModel;
                                }
                                if (in_array($relation, ["morphMany", "morphOne"])) {
                                    $relationships["KnownPolyMorphSide"][$method] = $relatedModel;
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
    abstract function getVisible();
    /**
     * Get the hidden attributes for the model.
     *
     * @return array
     */
    abstract function getHidden();
    /**
     * Get the primary key for the model.
     *
     * @return string
     */
    abstract function getKeyName();

    /**
     * Get the current connection name for the model.
     *
     * @return string
     */
    abstract function getConnectionName();
    /**
     * Get all of the current attributes on the model.
     *
     * @return array
     */
    abstract function getAttributes();
}
