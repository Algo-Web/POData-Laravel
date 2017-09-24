<?php

namespace AlgoWeb\PODataLaravel\Providers;

use AlgoWeb\PODataLaravel\Models\MetadataGubbinsHolder;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\Association;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationMonomorphic;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationPolymorphic;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubRelationType;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationType;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\EntityFieldType;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\EntityGubbins;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Map;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema as Schema;
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
        $objectMap = new Map();
        foreach ($modelNames as $modelName) {
            $modelInstance = App::make($modelName);
            $objectMap->addEntity($modelInstance->extractGubbins());
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
        $entities = $objectModel->getEntities();
        foreach ($entities as $entity) {
            $baseType = $entity->isPolymorphicAffected() ? $meta->resolveResourceType('polyMorphicPlaceholder') : null;
            $className = $entity->getClassName();
            $entityName = $entity->getName();
            $entityType = $meta->addEntityType(new \ReflectionClass($className), $entityName, false, $baseType);
            assert($entityType->hasBaseType() === isset($baseType));
            $entity->setOdataResourceType($entityType);
            $this->implementProperties($entity);
            $meta->addResourceSet($entity->getClassName(), $entityType);
            $meta->oDataEntityMap[$className] = $meta->oDataEntityMap[$entityName];
        }
        $metaCount = count($meta->oDataEntityMap);
        $entityCount = count($entities);
        assert($metaCount == 2 * $entityCount + 1);

        if (null === $objectModel->getAssociations()) {
            return;
        }
        $assoc = $objectModel->getAssociations();
        $assoc = null === $assoc ? [] : $assoc;
        foreach ($assoc as $association) {
            assert($association->isOk());
            if ($association instanceof AssociationMonomorphic) {
                $this->implementAssociationsMonomorphic($objectModel, $association);
            } elseif ($association instanceof AssociationPolymorphic) {
                $this->implementAssociationsPolymorphic($objectModel, $association);
            }
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

    /**
     * @param Map                    $objectModel
     * @param AssociationPolymorphic $association
     */
    private function implementAssociationsPolymorphic(Map $objectModel, AssociationPolymorphic $association)
    {
        $meta = App::make('metadata');
        $first = $association->getFirst();

        $polySet = $meta->resolveResourceSet(static::POLYMORPHIC_PLURAL);
        assert($polySet instanceof ResourceSet);

        $principalType = $objectModel->getEntities()[$first->getBaseType()]->getOdataResourceType();
        assert($principalType instanceof ResourceEntityType);
        $principalSet = $principalType->getCustomState();
        assert($principalSet instanceof ResourceSet);
        $principalProp = $first->getRelationName();
        $isPrincipalAdded = null !== $principalType->resolveProperty($principalProp);

        if (!$isPrincipalAdded) {
            if ($first->getMultiplicity()->getValue() !== AssociationStubRelationType::MANY) {
                $meta->addResourceReferenceProperty($principalType, $principalProp, $polySet);
            } else {
                $meta->addResourceSetReferenceProperty($principalType, $principalProp, $polySet);
            }
        }

        $types = $association->getAssociationType();
        $final = $association->getLast();
        $numRows = count($types);
        assert($numRows == count($final));

        for ($i = 0; $i < $numRows; $i++) {
            $type = $types[$i];
            $last = $final[$i];

            $dependentType = $objectModel->getEntities()[$last->getBaseType()]->getOdataResourceType();
            assert($dependentType instanceof ResourceEntityType);
            $dependentSet = $dependentType->getCustomState();
            assert($dependentSet instanceof ResourceSet);
            $dependentProp = $last->getRelationName();
            $isDependentAdded = null !== $dependentType->resolveProperty($dependentProp);

            switch ($type) {
                case AssociationType::NULL_ONE_TO_NULL_ONE():
                case AssociationType::NULL_ONE_TO_ONE():
                case AssociationType::ONE_TO_ONE():
                    if (!$isDependentAdded) {
                        $meta->addResourceReferenceProperty($dependentType, $dependentProp, $principalSet);
                    }
                    break;
                case AssociationType::NULL_ONE_TO_MANY():
                case AssociationType::ONE_TO_MANY():
                    if (!$isDependentAdded) {
                        $meta->addResourceSetReferenceProperty($dependentType, $dependentProp, $principalSet);
                    }
                    break;
                case AssociationType::MANY_TO_MANY():
                    if (!$isDependentAdded) {
                        $meta->addResourceSetReferenceProperty($dependentType, $dependentProp, $principalSet);
                    }
            }
        }
    }

    private function implementProperties(EntityGubbins $unifiedEntity)
    {
        $meta = App::make('metadata');
        $odataEntity = $unifiedEntity->getOdataResourceType();
        if (!$unifiedEntity->isPolymorphicAffected()) {
            foreach ($unifiedEntity->getKeyFields() as $keyField) {
                $meta->addKeyProperty($odataEntity, $keyField->getName(), $keyField->getEdmFieldType());
            }
        }
        foreach ($unifiedEntity->getFields() as $field) {
            if (in_array($field, $unifiedEntity->getKeyFields())) {
                continue;
            }
            if ($field->getPrimitiveType() == 'blob') {
                $odataEntity->setMediaLinkEntry(true);
                $streamInfo = new ResourceStreamInfo($field->getName());
                assert($odataEntity->isMediaLinkEntry());
                $odataEntity->addNamedStream($streamInfo);
                continue;
            }
            $meta->addPrimitiveProperty(
                $odataEntity,
                $field->getName(),
                $field->getEdmFieldType(),
                $field->getFieldType() == EntityFieldType::PRIMITIVE_BAG(),
                $field->getDefaultValue(),
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
        $hasCache = null != $meta;

        if ($isCaching && $hasCache) {
            App::instance('metadata', $meta);
            return;
        }
        $meta = App::make('metadata');
        if (false !== $reset) {
            $this->reset();
        }

        $stdRef = new \ReflectionClass(Model::class);
        $abstract = $meta->addEntityType($stdRef, static::POLYMORPHIC, true, null);
        $meta->addKeyProperty($abstract, 'PrimaryKey', TypeCode::STRING);

        $meta->addResourceSet(static::POLYMORPHIC, $abstract);

        $modelNames = $this->getCandidateModels();
        $objectModel = $this->extract($modelNames);
        $objectModel = $this->unify($objectModel);
        $this->verify($objectModel);
        $this->implement($objectModel);
        $this->completedObjectMap = $objectModel;
        $key = 'metadata';
        $this->handlePostBoot($isCaching, $hasCache, $key, $meta);
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
    }

    /**
     * @return array
     */
    protected function getCandidateModels()
    {
        $classes = $this->getClassMap();
        $ends = [];
        $startName = defined('PODATA_LARAVEL_APP_ROOT_NAMESPACE') ? PODATA_LARAVEL_APP_ROOT_NAMESPACE : 'App';
        foreach ($classes as $name) {
            if (\Illuminate\Support\Str::startsWith($name, $startName)) {
                if (in_array('AlgoWeb\\PODataLaravel\\Models\\MetadataTrait', class_uses($name))) {
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
     * @param Model $target
     * @param       $propName
     *
     * @return string|null
     */
    public function resolveReverseProperty(Model $source, Model $target, $propName)
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

        if ($association instanceof AssociationMonomorphic) {
            return $association->getLast()->getRelationName();
        }
        assert($association instanceof AssociationPolymorphic);

        $lasts = $association->getLast();
        foreach ($lasts as $stub) {
            if ($stub->getBaseType() == get_class($target)) {
                return $stub->getRelationName();
            }
        }
        return null;
    }
}
