<?php declare(strict_types=1);

namespace AlgoWeb\PODataLaravel\Providers;

use AlgoWeb\PODataLaravel\Models\IMetadataRelationshipContainer;
use AlgoWeb\PODataLaravel\Models\MetadataGubbinsHolder;
use AlgoWeb\PODataLaravel\Models\MetadataRelationshipContainer;
use AlgoWeb\PODataLaravel\Models\MetadataTrait;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationMonomorphic;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubRelationType;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationType;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\EntityField;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\EntityFieldType;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\EntityGubbins;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Map;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema as Schema;
use Illuminate\Support\Str;
use POData\Common\InvalidOperationException;
use POData\Providers\Metadata\ResourceStreamInfo;
use POData\Providers\Metadata\SimpleMetadataProvider;
use POData\Providers\Metadata\Type\TypeCode;

class MetadataProvider extends MetadataBaseProvider
{
    use MetadataProviderStepTrait;

    protected $multConstraints      = ['0..1' => ['1'], '1' => ['0..1', '*'], '*' => ['1', '*']];
    protected static $metaNAMESPACE = 'Data';
    protected static $isBooted      = false;
    const POLYMORPHIC               = 'polyMorphicPlaceholder';
    const POLYMORPHIC_PLURAL        = 'polyMorphicPlaceholders';

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

    protected $relationHolder;

    public function __construct($app)
    {
        parent::__construct($app);
        self::$isBooted = false;
    }

    /**
     * @param  array                        $modelNames
     * @throws InvalidOperationException
     * @throws \Doctrine\DBAL\DBALException
     * @throws \ReflectionException
     * @return Map
     */
    private function extract(array $modelNames)
    {
        /** @var Map $objectMap */
        $objectMap = App::make('objectmap');
        foreach ($modelNames as $modelName) {
            try {
                /** @var MetadataTrait $modelInstance */
                $modelInstance = App::make($modelName);
            } catch (BindingResolutionException $e) {
                // if we can't instantiate modelName for whatever reason, move on
                continue;
            }
            $gubbins   = $modelInstance->extractGubbins();
            $isEmpty   = 0 === count($gubbins->getFields());
            $inArtisan = $this->isRunningInArtisan();
            if (!($isEmpty && $inArtisan)) {
                $objectMap->addEntity($gubbins);
            }
        }
        $this->handleCustomFunction($objectMap, self::$afterExtract);
        return $objectMap;
    }

    /**
     * @param  Map                       $objectMap
     * @throws InvalidOperationException
     * @return Map
     */
    private function unify(Map $objectMap)
    {
        /** @var IMetadataRelationshipContainer $mgh */
        $mgh = $this->getRelationHolder();
        foreach ($objectMap->getEntities() as $entity) {
            $mgh->addEntity($entity);
        }
        $objectMap->setAssociations($mgh->getRelations());

        $this->handleCustomFunction($objectMap, self::$afterUnify);
        return $objectMap;
    }

    private function verify(Map $objectModel)
    {
        $objectModel->isOK();
        $this->handleCustomFunction($objectModel, self::$afterVerify);
    }

    /**
     * @param  Map                       $objectModel
     * @throws InvalidOperationException
     * @throws \ReflectionException
     */
    private function implement(Map $objectModel)
    {
        /** @var SimpleMetadataProvider $meta */
        $meta      = App::make('metadata');
        $namespace = $meta->getContainerNamespace().'.';

        $entities = $objectModel->getEntities();
        foreach ($entities as $entity) {
            $baseType   = null;
            $className  = $entity->getClassName();
            $entityName = $entity->getName();
            $pluralName = Str::plural($entityName);
            $entityType = $meta->addEntityType(new \ReflectionClass($className), $entityName, null, false, $baseType);
            if ($entityType->hasBaseType() !== isset($baseType)) {
                throw new InvalidOperationException('');
            }
            $entity->setOdataResourceType($entityType);
            $this->implementProperties($entity);
            $meta->addResourceSet($pluralName, $entityType);
            $meta->oDataEntityMap[$className] = $meta->oDataEntityMap[$namespace.$entityName];
        }
        $metaCount   = count($meta->oDataEntityMap);
        $entityCount = count($entities);
        $expected    = 2 * $entityCount;
        if ($metaCount != $expected) {
            $msg = 'Expected ' . $expected . ' items, actually got '.$metaCount;
            throw new InvalidOperationException($msg);
        }

        if (0 === count($objectModel->getAssociations())) {
            return;
        }
        $assoc = $objectModel->getAssociations();
        foreach ($assoc as $association) {
            if (!$association->isOk()) {
                throw new InvalidOperationException('');
            }
            $this->implementAssociationsMonomorphic($objectModel, $association);
        }
        $this->handleCustomFunction($objectModel, self::$afterImplement);
    }

    /**
     * @param  Map                       $objectModel
     * @param  AssociationMonomorphic    $associationUnderHammer
     * @throws InvalidOperationException
     * @throws \ReflectionException
     */
    private function implementAssociationsMonomorphic(Map $objectModel, AssociationMonomorphic $associationUnderHammer)
    {
        /** @var SimpleMetadataProvider $meta */
        $meta  = App::make('metadata');
        $first = $associationUnderHammer->getFirst();
        $last  = $associationUnderHammer->getLast();
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
                if ($first->getMultiplicity() == AssociationStubRelationType::MANY()) {
                    $oneSide  = $last;
                    $manySide = $first;
                } else {
                    $oneSide  = $first;
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
     * @param  EntityGubbins             $unifiedEntity
     * @throws InvalidOperationException
     * @throws \ReflectionException
     */
    private function implementProperties(EntityGubbins $unifiedEntity)
    {
        /** @var SimpleMetadataProvider $meta */
        $meta        = App::make('metadata');
        $odataEntity = $unifiedEntity->getOdataResourceType();
        $keyFields   = $unifiedEntity->getKeyFields();
        /** @var EntityField[] $fields */
        $fields = array_diff_key($unifiedEntity->getFields(), $keyFields);
        foreach ($keyFields as $keyField) {
            $meta->addKeyProperty($odataEntity, $keyField->getName(), $keyField->getEdmFieldType());
        }

        foreach ($fields as $field) {
            if ($field->getPrimitiveType() == 'blob') {
                $odataEntity->setMediaLinkEntry(true);
                $streamInfo = new ResourceStreamInfo($field->getName());
                $odataEntity->addNamedStream($streamInfo);
                continue;
            }

            $default     = $field->getDefaultValue();
            $isFieldBool = TypeCode::BOOLEAN() == $field->getEdmFieldType();
            $default     = $isFieldBool ? ($default ? 'true' : 'false') : strval($default);

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
     * @throws InvalidOperationException
     * @throws \ReflectionException
     * @throws \Doctrine\DBAL\DBALException
     * @return void
     */
    public function boot()
    {
        App::forgetInstance('metadata');
        App::forgetInstance('objectmap');
        $this->relationHolder = new MetadataRelationshipContainer();

        self::$metaNAMESPACE = env('ODataMetaNamespace', 'Data');
        // If we aren't migrated, there's no DB tables to pull metadata _from_, so bail out early
        try {
            if (!Schema::hasTable(config('database.migrations'))) {
                return;
            }
        } catch (\Exception $e) {
            return;
        }

        $isCaching = true === $this->getIsCaching();
        $meta      = Cache::get('metadata');
        $objectMap = Cache::get('objectmap');
        $hasCache  = null != $meta && null != $objectMap;

        if ($isCaching && $hasCache) {
            App::instance('metadata', $meta);
            App::instance('objectmap', $objectMap);
            self::$isBooted = true;
            return;
        }
        $meta = App::make('metadata');

        $modelNames  = $this->getCandidateModels();
        $objectModel = $this->extract($modelNames);
        $objectModel = $this->unify($objectModel);
        $this->verify($objectModel);
        $this->implement($objectModel);
        $this->completedObjectMap = $objectModel;
        $key                      = 'metadata';
        $objKey                   = 'objectmap';
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
        $this->app->/* @scrutinizer ignore-call */singleton('metadata', function () {
            return new SimpleMetadataProvider('Data', self::$metaNAMESPACE);
        });
        $this->app->/* @scrutinizer ignore-call */singleton('objectmap', function () {
            return new Map();
        });
    }

    /**
     * @return array
     */
    protected function getCandidateModels()
    {
        $classes   = $this->getClassMap();
        $ends      = [];
        $startName = $this->getAppNamespace();
        foreach ($classes as $name) {
            if (Str::startsWith($name, $startName)) {
                if (in_array('AlgoWeb\\PODataLaravel\\Models\\MetadataTrait', class_uses($name))) {
                    if (is_subclass_of($name, '\\Illuminate\\Database\\Eloquent\\Model')) {
                        $ends[] = $name;
                    }
                }
            }
        }
        return $ends;
    }

    /**
     * @return IMetadataRelationshipContainer|null
     */
    public function getRelationHolder() : ?IMetadataRelationshipContainer
    {
        return $this->relationHolder;
    }

    /**
     * Resolve possible reverse relation property names.
     *
     * @param Model $source
     * @param $propName
     * @throws InvalidOperationException
     * @return null|string
     * @internal param Model $target
     */
    public function resolveReverseProperty(Model $source, $propName)
    {
        if (!is_string($propName)) {
            throw new InvalidOperationException('Property name must be string');
        }
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

        if (!$association instanceof AssociationMonomorphic) {
            throw new InvalidOperationException('');
        }
        return $association->getLast()->getRelationName();
    }

    public function isRunningInArtisan()
    {
        return App::runningInConsole() && !App::runningUnitTests();
    }
}
