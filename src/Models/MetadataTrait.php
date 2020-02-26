<?php
namespace AlgoWeb\PODataLaravel\Models;

use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubFactory;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubMonomorphic;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubPolymorphic;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubRelationType;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\EntityField;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\EntityFieldPrimitiveType;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\EntityFieldType;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\EntityGubbins;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\App;
use Mockery\Mock;
use POData\Common\InvalidOperationException;
use POData\Providers\Metadata\Type\IType;

/**
 * Trait MetadataTrait
 * @package AlgoWeb\PODataLaravel\Models
 * @mixin Model
 */
trait MetadataTrait
{
    use MetadataRelationsTrait;

    protected $loadEagerRelations = [];
    protected static $tableColumns = [];
    protected static $tableColumnsDoctrine = [];
    protected static $tableData = [];
    protected static $dontCastTypes = ['object', 'array', 'collection', 'int'];

    protected static $relTypes = [
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
    ];

    /**
     * Retrieve and assemble this model's metadata for OData packaging.
     * @throws InvalidOperationException
     * @throws \Doctrine\DBAL\DBALException
     * @return array
     */
    public function metadata()
    {
        if (!$this instanceof Model) {
            throw new InvalidOperationException(get_class($this));
        }

        if (0 !== count(self::$tableData)) {
            return self::$tableData;
        } elseif (isset($this->odata)) {
            return self::$tableData = $this->odata;
        }

        // Break these out separately to enable separate reuse
        $connect = $this->getConnection();
        $builder = $connect->getSchemaBuilder();

        $table = $this->getTable();

        if (!$builder->hasTable($table)) {
            return self::$tableData = [];
        }

        /** @var array $columns */
        $columns = $this->getTableColumns();
        /** @var array $mask */
        $mask = $this->metadataMask();
        $columns = array_intersect($columns, $mask);

        $tableData = [];

        $rawFoo = $this->getTableDoctrineColumns();
        $foo = [];
        /** @var array $getters */
        $getters = $this->collectGetters();
        $getters = array_intersect($getters, $mask);
        $casts = $this->retrieveCasts();

        foreach ($rawFoo as $key => $val) {
            // Work around glitch in Doctrine when reading from MariaDB which added ` characters to root key value
            $key = trim($key, '`"');
            $foo[$key] = $val;
        }

        foreach ($columns as $column) {
            // Doctrine schema manager returns columns with lowercased names
            $rawColumn = $foo[strtolower($column)];
            /** @var IType $rawType */
            $rawType = $rawColumn->getType();
            $type = $rawType->getName();
            $default = $this->$column;
            $tableData[$column] = ['type' => $type,
                'nullable' => !($rawColumn->getNotNull()),
                'fillable' => in_array($column, $this->getFillable()),
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

    /**
     * Return the set of fields that are permitted to be in metadata
     * - following same visible-trumps-hidden guideline as Laravel.
     *
     * @return array
     */
    public function metadataMask()
    {
        $attribs = array_keys($this->getAllAttributes());

        $visible = $this->getVisible();
        $hidden = $this->getHidden();
        if (0 < count($visible)) {
            $attribs = array_intersect($visible, $attribs);
        } elseif (0 < count($hidden)) {
            $attribs = array_diff($attribs, $hidden);
        }

        return $attribs;
    }

    /*
     * Get the endpoint name being exposed
     *
     * @return null|string;
     */
    public function getEndpointName()
    {
        $endpoint = isset($this->endpoint) ? $this->endpoint : null;

        if (!isset($endpoint)) {
            $bitter = get_class($this);
            $name = substr($bitter, strrpos($bitter, '\\')+1);
            return ($name);
        }
        return ($endpoint);
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
            $residual = substr(/* @scrutinizer ignore-type */$residual, 0, -9);
            $methods[] = $residual;
        }
        return $methods;
    }

    /**
     * Supplemental function to retrieve cast array for Laravel versions that do not supply hasCasts.
     *
     * @return array
     */
    public function retrieveCasts()
    {
        $exists = method_exists($this, 'getCasts');
        return $exists ? (array)$this->getCasts() : (array)$this->casts;
    }

    /**
     * Return list of relations to be eager-loaded by Laravel query provider.
     *
     * @return array
     */
    public function getEagerLoad()
    {
        return $this->loadEagerRelations;
    }

    /**
     * Set list of relations to be eager-loaded.
     *
     * @param array $relations
     */
    public function setEagerLoad(array $relations)
    {
        $this->loadEagerRelations = array_map('strval', $relations);
    }

    /**
     * Extract entity gubbins detail for later downstream use.
     *
     * @throws InvalidOperationException
     * @throws \ReflectionException
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     * @return EntityGubbins
     */
    public function extractGubbins()
    {
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
        foreach ($rawRels as $propertyName) {
            if (in_array(strtolower($propertyName), $lowerNames)) {
                $msg = 'Property names must be unique, without regard to case';
                throw new \Exception($msg);
            }
            $stub = AssociationStubFactory::associationStubFromRelation(/** @scrutinizer ignore-type */$this, $propertyName);
            $stubs[$propertyName] = $stub;
        }
        $gubbins->setStubs($stubs);

        return $gubbins;
    }

    public function isRunningInArtisan()
    {
        return App::runningInConsole() && !App::runningUnitTests();
    }

    /**
     * Get columns for selected table.
     *
     * @return array
     */
    protected function getTableColumns()
    {
        if (0 === count(self::$tableColumns)) {
            $table = $this->getTable();
            $connect = $this->getConnection();
            $builder = $connect->getSchemaBuilder();
            $columns = $builder->getColumnListing($table);

            self::$tableColumns = (array)$columns;
        }
        return self::$tableColumns;
    }

    /**
     * Get Doctrine columns for selected table.
     *
     * @throws \Doctrine\DBAL\DBALException
     * @return array
     */
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
}
