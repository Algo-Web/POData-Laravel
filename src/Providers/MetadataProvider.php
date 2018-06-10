<?php

namespace AlgoWeb\PODataLaravel\Providers;

use AlgoWeb\PODataLaravel\Models\MetadataGubbinsHolder;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\Association;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationMonomorphic;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubRelationType;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationType;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\EntityFieldType;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\EntityGubbins;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Map;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema as Schema;
use Illuminate\Support\Str;
use POData\Providers\Metadata\ResourceEntityType;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Metadata\ResourceStreamInfo;
use POData\Providers\Metadata\SimpleMetadataProvider;
use POData\Providers\Metadata\Type\TypeCode;

class MetadataProvider extends MetadataBaseProvider
{
    protected $multConstraints = ['0..1' => ['1'], '1' => ['0..1', '*'], '*' => ['1', '*']];
    protected static $metaNAMESPACE = 'Data';
    protected static $isBooted = false;
    const POLYMORPHIC = 'polyMorphicPlaceholder';
    const POLYMORPHIC_PLURAL = 'polyMorphicPlaceholders';

    /**
     * @var Map The completed object map set at post Implement;
     */
    private $completedObjectMap;

    /**
     * @return \AlgoWeb\PODataLaravel\Models\ObjectMap\Map
     */
    public function getObjectMap()
    {
        return $this->completedObjectMap;
    }

    protected static $afterExtract;
    protected static $afterUnify;
    protected static $afterVerify;
    protected static $afterImplement;

    public static function setAfterExtract(callable $method)
    {
        self::$afterExtract = $method;
    }

    public static function setAfterUnify(callable $method)
    {
        self::$afterUnify = $method;
    }

    public static function setAfterVerify(callable $method)
    {
        self::$afterVerify = $method;
    }

    public static function setAfterImplement(callable $method)
    {
        self::$afterImplement = $method;
    }


    protected $relationHolder;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->relationHolder = new MetadataGubbinsHolder();
        self::$isBooted = false;
    }

    private function extract(array $modelNames)
    {
        $objectMap = App::make('objectmap');
        foreach ($modelNames as $modelName) {
            try {
                $modelInstance = App::make($modelName);
            } catch (BindingResolutionException $e) {
                // if we can't instantiate modelName for whatever reason, move on
                continue;
            }
            $gubbins = $modelInstance->extractGubbins();
            $isEmpty = 0 === count($gubbins->getFields());
            $inArtisan = $this->isRunningInArtisan();
            if (!($isEmpty && $inArtisan)) {
                $objectMap->addEntity($gubbins);
            }
        }
        if (null != self::$afterExtract) {
            $func = self::$afterExtract;
            $func($objectMap);
        }
        return $objectMap;
    }

    private function unify(Map $objectMap)
    {
        $mgh = $this->getRelationHolder();
        foreach ($objectMap->getEntities() as $entity) {
            $mgh->addEntity($entity);
        }
        $objectMap->setAssociations($mgh->getRelations());
        if (null != self::$afterUnify) {
            $func = self::$afterUnify;
            $func($objectMap);
        }
        return $objectMap;
    }

    private function verify(Map $objectModel)
    {
        $objectModel->isOK();
        if (null != self::$afterVerify) {
            $func = self::$afterVerify;
            $func($objectModel);
        }
    }

    private function implement(Map $objectModel)
    {
        $meta = App::make('metadata');
        $namespace = $meta->getContainerNamespace().'.';

        $entities = $objectModel->getEntities();
        foreach ($entities as $entity) {
            $baseType = null;
            $className = $entity->getClassName();
            $entityName = $entity->getName();
            $pluralName = Str::plural($entityName);
            $entityType = $meta->addEntityType(new \ReflectionClass($className), $entityName, null, false, $baseType);
            assert($entityType->hasBaseType() === isset($baseType));
            $entity->setOdataResourceType($entityType);
            $this->implementProperties($entity);
            $meta->addResourceSet($pluralName, $entityType);
            $meta->oDataEntityMap[$className] = $meta->oDataEntityMap[$namespace.$entityName];
        }
        $metaCount = count($meta->oDataEntityMap);
        $entityCount = count($entities);
        $expected = 2 * $entityCount;
        assert($metaCount == $expected, 'Expected ' . $expected . ' items, actually got '.$metaCount);

        if (0 === count($objectModel->getAssociations())) {
            return;
        }
        $assoc = $objectModel->getAssociations();
        foreach ($assoc as $association) {
            assert($association->isOk());
            $this->implementAssociationsMonomorphic($objectModel, $association);
        }
        if (null != self::$afterImplement) {
            $func = self::$afterImplement;
            $func($objectModel);
        }
    }

    private function implementAssociationsMonomorphic(Map $objectModel, AssociationMonomorphic $associationUnderHammer)
    {
        $meta = App::make('metadata');
        $first = $associationUnderHammer->getFirst();
        $last = $associationUnderHammer->getLast();
        switch ($associationUnderHammer->getAssociationType()) {
            case AssociationType::NULL_ONE_TO_NULL_ONE():
            case AssociationType::NULL_ONE_TO_ONE():
            case AssociationType::ONE_TO_ONE():
                $meta->addResourceReferenceSinglePropertyBidirectional(
                    $objectModel->getEntities()[$first->getBaseType()]->getOdataResourceType(),
                    $objectModel->getEntities()[$last->getBaseType()]->getOdataResourceType(),
                    $first->getRelationName(),
                    $last->getRelationName()
                );
                break;
            case AssociationType::NULL_ONE_TO_MANY():
            case AssociationType::ONE_TO_MANY():
                if ($first->getMultiplicity()->getValue() == AssociationStubRelationType::MANY) {
                    $oneSide = $last;
                    $manySide = $first;
                } else {
                    $oneSide = $first;
                    $manySide = $last;
                }
                $meta->addResourceReferencePropertyBidirectional(
                    $objectModel->getEntities()[$oneSide->getBaseType()]->getOdataResourceType(),
                    $objectModel->getEntities()[$manySide->getBaseType()]->getOdataResourceType(),
                    $oneSide->getRelationName(),
                    $manySide->getRelationName()
                );
                break;
            case AssociationType::MANY_TO_MANY():
                $meta->addResourceSetReferencePropertyBidirectional(
                    $objectModel->getEntities()[$first->getBaseType()]->getOdataResourceType(),
                    $objectModel->getEntities()[$last->getBaseType()]->getOdataResourceType(),
                    $first->getRelationName(),
                    $last->getRelationName()
                );
        }
    }

    private function implementProperties(EntityGubbins $unifiedEntity)
    {
        $meta = App::make('metadata');
        $odataEntity = $unifiedEntity->getOdataResourceType();
        $keyFields = $unifiedEntity->getKeyFields();
        $fields = $unifiedEntity->getFields();
        foreach ($keyFields as $keyField) {
            $meta->addKeyProperty($odataEntity, $keyField->getName(), $keyField->getEdmFieldType());
        }

        foreach ($fields as $field) {
            if (in_array($field, $keyFields)) {
                continue;
            }
            if ($field->getPrimitiveType() == 'blob') {
                $odataEntity->setMediaLinkEntry(true);
                $streamInfo = new ResourceStreamInfo($field->getName());
                assert($odataEntity->isMediaLinkEntry());
                $odataEntity->addNamedStream($streamInfo);
                continue;
            }

            $default = $field->getDefaultValue();
            $isFieldBool = TypeCode::BOOLEAN == $field->getEdmFieldType();
            $default = $isFieldBool ? ($default ? 'true' : 'false') : strval($default);

            $meta->addPrimitiveProperty(
                $odataEntity,
                $field->getName(),
                $field->getEdmFieldType(),
                $field->getFieldType() == EntityFieldType::PRIMITIVE_BAG(),
                $default,
                $field->getIsNullable()
            );
        }
    }

    /**
     * Bootstrap the application services.  Post-boot.
     *
     * @param mixed $reset
     *
     * @return void
     */
    public function boot($reset = true)
    {
        self::$metaNAMESPACE = env('ODataMetaNamespace', 'Data');
        // If we aren't migrated, there's no DB tables to pull metadata _from_, so bail out early
        try {
            if (!Schema::hasTable(config('database.migrations'))) {
                return;
            }
        } catch (\Exception $e) {
            return;
        }

        assert(false === self::$isBooted, 'Provider booted twice');
        $isCaching = true === $this->getIsCaching();
        $meta = Cache::get('metadata');
        $objectMap = Cache::get('objectmap');
        $hasCache = null != $meta && null != $objectMap;

        if ($isCaching && $hasCache) {
            App::instance('metadata', $meta);
            App::instance('objectmap', $objectMap);
            return;
        }
        $meta = App::make('metadata');
        if (false !== $reset) {
            $this->reset();
        }

        $modelNames = $this->getCandidateModels();
        $objectModel = $this->extract($modelNames);
        $objectModel = $this->unify($objectModel);
        $this->verify($objectModel);
        $this->implement($objectModel);
        $this->completedObjectMap = $objectModel;
        $key = 'metadata';
        $objKey = 'objectmap';
        $this->handlePostBoot($isCaching, $hasCache, $key, $meta);
        $this->handlePostBoot($isCaching, $hasCache, $objKey, $objectModel);
        self::$isBooted = true;
    }

    /**
     * Register the application services.  Boot-time only.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('metadata', function ($app) {
            return new SimpleMetadataProvider('Data', self::$metaNAMESPACE);
        });
        $this->app->singleton('objectmap', function ($app) {
            return new Map();
        });
    }

    /**
     * @return array
     */
    protected function getCandidateModels()
    {
        $classes = $this->getClassMap();
        $ends = [];
        $startName = $this->app->getNamespace();
        foreach ($classes as $name) {
            if (\Illuminate\Support\Str::startsWith($name, $startName)) {
                if (in_array('AlgoWeb\\PODataLaravel\\Models\\MetadataTrait', class_uses($name)) &&
                is_subclass_of($name, '\\Illuminate\\Database\\Eloquent\\Model')) {
                    $ends[] = $name;
                }
            }
        }
        return $ends;
    }

    /**
     * @return MetadataGubbinsHolder
     */
    public function getRelationHolder()
    {
        return $this->relationHolder;
    }

    public function reset()
    {
        self::$isBooted = false;
        self::$afterExtract = null;
        self::$afterUnify = null;
        self::$afterVerify = null;
        self::$afterImplement = null;
    }

    /**
     * Resolve possible reverse relation property names.
     *
     * @param Model $source
     * @param $propName
     * @return null|string
     * @internal param Model $target
     */
    public function resolveReverseProperty(Model $source, $propName)
    {
        assert(is_string($propName), 'Property name must be string');
        $entity = $this->getObjectMap()->resolveEntity(get_class($source));
        if (null === $entity) {
            $msg = 'Source model not defined';
            throw new \InvalidArgumentException($msg);
        }
        $association = $entity->resolveAssociation($propName);
        if (null === $association) {
            return null;
        }
        $isFirst = $propName === $association->getFirst()->getRelationName();
        if (!$isFirst) {
            return $association->getFirst()->getRelationName();
        }

        assert($association instanceof AssociationMonomorphic);
        return $association->getLast()->getRelationName();
    }

    public function isRunningInArtisan()
    {
        return App::runningInConsole() && !App::runningUnitTests();
    }
}
