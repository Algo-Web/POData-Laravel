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
    protected static $relationCache;
    protected static $isBooted = false;
    const POLYMORPHIC = 'polyMorphicPlaceholder';
    const POLYMORPHIC_PLURAL = 'polyMorphicPlaceholders';

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

    private function unify(Map $ObjectMap)
    {
        $mgh = $this->getRelationHolder();
        foreach ($ObjectMap->getEntities() as $entity) {
            $mgh->addEntity($entity);
        }
        $ObjectMap->setAssociations($mgh->getRelations());
        if (null != self::$afterUnify) {
            $func = self::$afterUnify;
            $func($ObjectMap);
        }
        return $ObjectMap;
    }

    private function verify(Map $objectModel)
    {
        $failMessage = '';
        $objectModel->isOK($failMessage);
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
            $EntityType = $meta->addEntityType(new \ReflectionClass($className), $entityName, false, $baseType);
            assert($EntityType->hasBaseType() === isset($baseType));
            $entity->setOdataResourceType($EntityType);
            $this->implementProperties($entity);
            $meta->addResourceSet($entity->getClassName(), $EntityType);
            $meta->oDataEntityMap[$className] = $meta->oDataEntityMap[$entityName];
        }
        $metaCount = count($meta->oDataEntityMap);
        $entityCount = count($entities);
        assert($metaCount == 2 * $entityCount+1);

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
     * @param  mixed $reset
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
        //dd($objectModel);
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

    public function calculateRoundTripRelations()
    {
        $modelNames = $this->getCandidateModels();

        foreach ($modelNames as $name) {
            if (!$this->getRelationHolder()->hasClass($name)) {
                $model = App::make($name);
                $gubbinz = $model->extractGubbins();
                $this->getRelationHolder()->addEntity($gubbinz);
            }
        }

        $rels = $this->getRelationHolder()->getRelations();

        $result = [];
        foreach ($rels as $payload) {
            assert($payload instanceof Association);
            $raw = $payload->getArrayPayload();
            if (is_array($raw)) {
                foreach ($raw as $line) {
                    $result[] = $line;
                }
            }
        }

        return $result;
    }

    public function getPolymorphicRelationGroups()
    {
        $modelNames = $this->getCandidateModels();

        $knownSide = [];
        $unknownSide = [];

        $hooks = [];
        // fish out list of polymorphic-affected models for further processing
        foreach ($modelNames as $name) {
            $model = new $name();
            $isPoly = false;
            if ($model->isKnownPolymorphSide()) {
                $knownSide[$name] = [];
                $isPoly = true;
            }
            if ($model->isUnknownPolymorphSide()) {
                $unknownSide[$name] = [];
                $isPoly = true;
            }
            if (false === $isPoly) {
                continue;
            }

            $rels = $model->getRelationships();
            // it doesn't matter if a model has no relationships here, that lack will simply be skipped over
            // during hookup processing
            $hooks[$name] = $rels;
        }
        // ensure we've only loaded up polymorphic-affected models
        $knownKeys = array_keys($knownSide);
        $unknownKeys = array_keys($unknownSide);
        $dualKeys = array_intersect($knownKeys, $unknownKeys);
        assert(count($hooks) == (count($unknownKeys)+count($knownKeys)-count($dualKeys)));
        // if either list is empty, bail out - there's nothing to do
        if (0 === count($knownSide) || 0 === count($unknownSide)) {
            return [];
        }

        // commence primary ignition

        foreach ($unknownKeys as $key) {
            assert(isset($hooks[$key]));
            $hook = $hooks[$key];
            foreach ($hook as $barb) {
                foreach ($barb as $knownType => $propData) {
                    $propName = array_keys($propData)[0];
                    if (in_array($knownType, $knownKeys)) {
                        if (!isset($knownSide[$knownType][$key])) {
                            $knownSide[$knownType][$key] = [];
                        }
                        assert(isset($knownSide[$knownType][$key]));
                        $knownSide[$knownType][$key][] = $propData[$propName]['property'];
                    }
                }
            }
        }

        return $knownSide;
    }

    /**
     * Get round-trip relations after inserting polymorphic-powered placeholders.
     *
     * @return array
     */
    public function getRepairedRoundTripRelations()
    {
        if (!isset(self::$relationCache)) {
            $rels = $this->calculateRoundTripRelations();
            $groups = $this->getPolymorphicRelationGroups();

            if (0 === count($groups)) {
                self::$relationCache = $rels;
                return $rels;
            }

            $placeholder = static::POLYMORPHIC;

            $groupKeys = array_keys($groups);

            // we have at least one polymorphic relation, need to dig it out
            $numRels = count($rels);
            for ($i = 0; $i < $numRels; $i++) {
                $relation = $rels[$i];
                $principalType = $relation['principalType'];
                $dependentType = $relation['dependentType'];
                $principalPoly = in_array($principalType, $groupKeys);
                $dependentPoly = in_array($dependentType, $groupKeys);
                $rels[$i]['principalRSet'] = $principalPoly ? $placeholder : $principalType;
                $rels[$i]['dependentRSet'] = $dependentPoly ? $placeholder : $dependentType;
            }
            self::$relationCache = $rels;
        }
        return self::$relationCache;
    }


    public function reset()
    {
        self::$relationCache = null;
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
        $relations = $this->getRepairedRoundTripRelations();

        $sourceName = get_class($source);
        $targName = get_class($target);

        $filter = function ($segment) use ($sourceName, $targName, $propName) {
            $match = $sourceName == $segment['principalType'];
            $match &= $targName == $segment['dependentType'];
            $match &= $propName == $segment['principalProp'];

            return $match;
        };

        // array_filter does not reset keys - we have to do it ourselves
        $trim = array_values(array_filter($relations, $filter));
        $result = 0 === count($trim) ? null : $trim[0]['dependentProp'];

        return $result;
    }
}
