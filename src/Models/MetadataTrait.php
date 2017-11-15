<?php
namespace AlgoWeb\PODataLaravel\Models;

use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubMonomorphic;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubPolymorphic;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubRelationType;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\EntityField;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\EntityFieldPrimitiveType;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\EntityFieldType;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\EntityGubbins;
use AlgoWeb\PODataLaravel\Query\LaravelReadQuery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\App;

trait MetadataTrait
{
    protected static $relationHooks = [];
    protected static $relationCategories = [];
    protected static $methodPrimary = [];
    protected static $methodAlternate = [];
    protected $loadEagerRelations = [];
    protected static $tableColumns = [];
    protected static $tableColumnsDoctrine = [];
    protected static $tableData = [];
    protected static $dontCastTypes = ['object', 'array', 'collection', 'int'];

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
        if (0 !== count(self::$tableData)) {
            return self::$tableData;
        }

        $columns = $this->getTableColumns();
        $mask = $this->metadataMask();
        $columns = array_intersect($columns, $mask);

        $tableData = [];

        $rawFoo = $this->getTableDoctrineColumns();
        $foo = [];
        $getters = $this->collectGetters();
        $getters = array_intersect($getters, $mask);
        $casts = $this->getCasts();

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

        // now, after everything's gathered up, apply Eloquent model's $cast array
        foreach ($casts as $key => $type) {
            $type = strtolower($type);
            if (array_key_exists($key, $tableData) && !in_array($type, self::$dontCastTypes)) {
                $tableData[$key]['type'] = $type;
            }
        }

        self::$tableData = $tableData;
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
            return ($name);
        }
        return ($endpoint);
    }

    /**
     * Get model's relationships.
     *
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

    protected function getAllAttributes()
    {
        // Adapted from http://stackoverflow.com/a/33514981
        // $columns = $this->getFillable();
        // Another option is to get all columns for the table like so:
        $columns = $this->getTableColumns();
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

    /**
     * @param bool $biDir
     *
     * @return array
     */
    protected function getRelationshipsFromMethods($biDir = false)
    {
        $biDirVal = intval($biDir);
        $isCached = isset(static::$relationCategories[$biDirVal]) && !empty(static::$relationCategories[$biDirVal]);
        if (!$isCached) {
            $model = $this;
            $relationships = [
                'HasOne' => [],
                'UnknownPolyMorphSide' => [],
                'HasMany' => [],
                'KnownPolyMorphSide' => []
            ];
            $methods = get_class_methods($model);
            if (!empty($methods)) {
                foreach ($methods as $method) {
                    if (!method_exists('Illuminate\Database\Eloquent\Model', $method)
                        && !method_exists(Mock::class, $method)
                        && !method_exists(MetadataTrait::class, $method)
                    ) {
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
                        assert(
                            false !== stripos($code, 'function'),
                            'Function definition must have keyword \'function\''
                        );
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
                            $search = '$this->' . $relation . '(';
                            if ($pos = stripos($code, $search)) {
                                //Resolve the relation's model to a Relation object.
                                $relationObj = $model->$method();
                                if ($relationObj instanceof Relation) {
                                    $relObject = $relationObj->getRelated();
                                    $relatedModel = '\\' . get_class($relObject);
                                    if (in_array(MetadataTrait::class, class_uses($relatedModel))) {
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
                                        if (in_array($relation, ['morphMany', 'morphOne', 'morphToMany'])) {
                                            $relationships['KnownPolyMorphSide'][$method] =
                                                $biDir ? $relationObj : $relatedModel;
                                        }
                                        if (in_array($relation, ['morphedByMany'])) {
                                            $relationships['UnknownPolyMorphSide'][$method] =
                                                $biDir ? $relationObj : $relatedModel;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            static::$relationCategories[$biDirVal] = $relationships;
        }
        return static::$relationCategories[$biDirVal];
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
     * Dig up all defined getters on the model.
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
     * @param       $foo
     * @param mixed $condition
     *
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
                assert(
                    in_array($fkMethodName, $methodList),
                    'Selected method, ' . $fkMethodName . ', not in method list'
                );
                $rkMethodName = 'getQualifiedRelatedPivotKeyName';
                foreach ($rkList as $option) {
                    if (in_array($option, $methodList)) {
                        $rkMethodName = $option;
                        break;
                    }
                }
                assert(
                    in_array($rkMethodName, $methodList),
                    'Selected method, ' . $rkMethodName . ', not in method list'
                );
                $line = ['fk' => $fkMethodName, 'rk' => $rkMethodName];
                static::$methodPrimary[get_class($foo)] = $line;
            }
        }
        return [$fkMethodName, $rkMethodName];
    }

    private function polyglotKeyMethodBackupNames($foo, $condition = false)
    {
        $fkList = ['getForeignKey', 'getForeignKeyName'];
        $rkList = ['getOtherKey', 'getQualifiedParentKeyName'];

        $fkMethodName = null;
        $rkMethodName = null;
        if ($condition) {
            if (array_key_exists(get_class($foo), static::$methodAlternate)) {
                $line = static::$methodAlternate[get_class($foo)];
                $fkMethodName = $line['fk'];
                $rkMethodName = $line['rk'];
            } else {
                $methodList = get_class_methods(get_class($foo));
                $fkCombo = array_values(array_intersect($fkList, $methodList));
                assert(1 <= count($fkCombo), 'Expected at least 1 element in foreign-key list, got ' . count($fkCombo));
                $fkMethodName = $fkCombo[0];
                assert(
                    in_array($fkMethodName, $methodList),
                    'Selected method, ' . $fkMethodName . ', not in method list'
                );
                $rkCombo = array_values(array_intersect($rkList, $methodList));
                assert(1 <= count($rkCombo), 'Expected at least 1 element in related-key list, got ' . count($rkCombo));
                $rkMethodName = $rkCombo[0];
                assert(
                    in_array($rkMethodName, $methodList),
                    'Selected method, ' . $rkMethodName . ', not in method list'
                );
                $line = ['fk' => $fkMethodName, 'rk' => $rkMethodName];
                static::$methodAlternate[get_class($foo)] = $line;
            }
        }
        return [$fkMethodName, $rkMethodName];
    }

    /**
     * @param             $hooks
     * @param             $first
     * @param             $property
     * @param             $last
     * @param             $mult
     * @param             $targ
     * @param string|null $targ
     * @param null|mixed  $type
     */
    private function addRelationsHook(&$hooks, $first, $property, $last, $mult, $targ, $type = null)
    {
        if (!isset($hooks[$first])) {
            $hooks[$first] = [];
        }
        if (!isset($hooks[$first][$targ])) {
            $hooks[$first][$targ] = [];
        }
        $hooks[$first][$targ][$property] = [
            'property' => $property,
            'local' => $last,
            'multiplicity' => $mult,
            'type' => $type
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
            $keyName = $keySegments[count($keySegments)-1];
            $localRaw = $isBelong ? $foo->$rkMethodName() : $foo->$rkMethodAlternate();
            $localSegments = explode('.', $localRaw);
            $localName = $localSegments[count($localSegments)-1];
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
            $keyName = $keySegments[count($keySegments)-1];
            $localRaw = $isBelong ? $foo->$rkMethodName() : $foo->$rkMethodAlternate();
            $localSegments = explode('.', $localRaw);
            $localName = $localSegments[count($localSegments)-1];
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
            $keyName = $keySegments[count($keySegments)-1];
            $localRaw = $isMany ? $foo->$rkMethodName() : $foo->$rkMethodAlternate();
            $localSegments = explode('.', $localRaw);
            $localName = $localSegments[count($localSegments)-1];
            $first = $isMany ? $keyName : $localName;
            $last = $isMany ? $localName : $keyName;
            $this->addRelationsHook($hooks, $first, $property, $last, $mult, $targ, 'unknown');
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
            $keyName = $keySegments[count($keySegments)-1];
            $localRaw = $isMany ? $foo->$rkMethodName() : $foo->$rkMethodAlternate();
            $localSegments = explode('.', $localRaw);
            $localName = $localSegments[count($localSegments)-1];

            $first = $keyName;
            $last = (isset($localName) && '' != $localName) ? $localName : $foo->getRelated()->getKeyName();
            $this->addRelationsHook($hooks, $first, $property, $last, $mult, $targ, 'known');
        }
    }

    /**
     * SUpplemental function to retrieve cast array for Laravel versions that do not supply hasCasts.
     *
     * @return array
     */
    public function retrieveCasts()
    {
        return $this->casts;
    }

    /**
     * Return list of relations to be eager-loaded by Laravel query provider.
     *
     * @return array
     */
    public function getEagerLoad()
    {
        assert(is_array($this->loadEagerRelations));
        return $this->loadEagerRelations;
    }

    /**
     * Set list of relations to be eager-loaded.
     *
     * @param array $relations
     */
    public function setEagerLoad(array $relations)
    {
        $check = array_map('strval', $relations);
        assert($relations == $check, 'All supplied relations must be resolvable to strings');
        $this->loadEagerRelations = $relations;
    }

    /*
     * Is this model the known side of at least one polymorphic relation?
     */
    public function isKnownPolymorphSide()
    {
        // isKnownPolymorph needs to be checking KnownPolymorphSide results - if you're checking UnknownPolymorphSide,
        // you're turned around
        $rels = $this->getRelationshipsFromMethods();
        return !empty($rels['KnownPolyMorphSide']);
    }

    /*
     * Is this model on the unknown side of at least one polymorphic relation?
     */
    public function isUnknownPolymorphSide()
    {
        // isUnknownPolymorph needs to be checking UnknownPolymorphSide results - if you're checking KnownPolymorphSide,
        // you're turned around
        $rels = $this->getRelationshipsFromMethods();
        return !empty($rels['UnknownPolyMorphSide']);
    }

    /**
     * Extract entity gubbins detail for later downstream use.
     *
     * @return EntityGubbins
     */
    public function extractGubbins()
    {
        $multArray = [
            '*' => AssociationStubRelationType::MANY(),
            '1' => AssociationStubRelationType::ONE(),
            '0..1' => AssociationStubRelationType::NULL_ONE()
        ];

        $gubbins = new EntityGubbins();
        $gubbins->setName($this->getEndpointName());
        $gubbins->setClassName(get_class($this));

        $lowerNames = [];

        $fields = $this->metadata();
        $entityFields = [];
        foreach ($fields as $name => $field) {
            if (in_array(strtolower($name), $lowerNames)) {
                $msg = 'Property names must be unique, without regard to case';
                throw new \Exception($msg);
            }
            $lowerNames[] = strtolower($name);
            $nuField = new EntityField();
            $nuField->setName($name);
            $nuField->setIsNullable($field['nullable']);
            $nuField->setReadOnly(false);
            $nuField->setCreateOnly(false);
            $nuField->setDefaultValue($field['default']);
            $nuField->setIsKeyField($this->getKeyName() == $name);
            $nuField->setFieldType(EntityFieldType::PRIMITIVE());
            $nuField->setPrimitiveType(new EntityFieldPrimitiveType($field['type']));
            $entityFields[$name] = $nuField;
        }
        $isEmpty = (0 === count($entityFields));
        if (!($isEmpty && $this->isRunningInArtisan())) {
            $gubbins->setFields($entityFields);
        }

        $rawRels = $this->getRelationships();
        $stubs = [];
        foreach ($rawRels as $key => $rel) {
            foreach ($rel as $rawName => $deets) {
                foreach ($deets as $relName => $relGubbins) {
                    if (in_array(strtolower($relName), $lowerNames)) {
                        $msg = 'Property names must be unique, without regard to case';
                        throw new \Exception($msg);
                    }
                    $lowerNames[] = strtolower($relName);
                    $gubbinsType = $relGubbins['type'];
                    $property = $relGubbins['property'];
                    $isPoly = isset($gubbinsType);
                    $targType = 'known' != $gubbinsType ? $rawName : null;
                    $stub = $isPoly ? new AssociationStubPolymorphic() : new AssociationStubMonomorphic();
                    $stub->setBaseType(get_class($this));
                    $stub->setRelationName($property);
                    $stub->setKeyField($relGubbins['local']);
                    $stub->setForeignField($targType ? $key : null);
                    $stub->setMultiplicity($multArray[$relGubbins['multiplicity']]);
                    $stub->setTargType($targType);
                    assert($stub->isOk());
                    $stubs[$property] = $stub;
                }
            }
        }
        $gubbins->setStubs($stubs);

        return $gubbins;
    }

    public function synthLiteralPK()
    {
        if (!$this->isKnownPolymorphSide()) {
            return;
        }
        $fieldName = LaravelReadQuery::PK;
        $this->$fieldName = $this->getKey();
    }

    public function isRunningInArtisan()
    {
        return App::runningInConsole() && !App::runningUnitTests();
    }

    protected function getTableColumns()
    {
        if (0 === count(self::$tableColumns)) {
            $table = $this->getTable();
            $connect = $this->getConnection();
            $builder = $connect->getSchemaBuilder();
            $columns = $builder->getColumnListing($table);

            self::$tableColumns = $columns;
        }
        return self::$tableColumns;
    }

    protected function getTableDoctrineColumns()
    {
        if (0 === count(self::$tableColumnsDoctrine)) {
            $table = $this->getTable();
            $connect = $this->getConnection();
            $columns = $connect->getDoctrineSchemaManager()->listTableColumns($table);

            self::$tableColumnsDoctrine = $columns;
        }
        return self::$tableColumnsDoctrine;
    }

    public function reset()
    {
        self::$tableData = [];
        self::$tableColumnsDoctrine = [];
        self::$tableColumns = [];
    }
}
