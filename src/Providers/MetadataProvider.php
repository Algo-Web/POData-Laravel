<?php

namespace AlgoWeb\PODataLaravel\Providers;

use AlgoWeb\PODataLaravel\Models\MetadataRelationHolder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use POData\Providers\Metadata\IMetadataProvider;
use POData\Providers\Metadata\ResourceEntityType;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\SimpleMetadataProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema as Schema;
use POData\Providers\Metadata\Type\TypeCode;
use POData\Providers\ProvidersWrapper;

class MetadataProvider extends MetadataBaseProvider
{
    protected $multConstraints = [ '0..1' => ['1'], '1' => ['0..1', '*'], '*' => ['1', '*']];
    protected static $metaNAMESPACE = 'Data';
    protected static $relationCache;
    protected static $isBooted = false;
    const POLYMORPHIC = 'polyMorphicPlaceholder';
    const POLYMORPHIC_PLURAL = 'polyMorphicPlaceholders';

    protected $relationHolder;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->relationHolder = new MetadataRelationHolder();
        self::$isBooted = false;
    }

    /**
     * Bootstrap the application services.  Post-boot.
     *
     * @return void
     */
    public function boot()
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
        $this->reset();

        $stdRef = new \ReflectionClass(Model::class);
        $abstract = $meta->addEntityType($stdRef, static::POLYMORPHIC, true, null);
        $meta->addKeyProperty($abstract, 'PrimaryKey', TypeCode::STRING);

        $meta->addResourceSet(static::POLYMORPHIC, $abstract);

        $modelNames = $this->getCandidateModels();

        list($entityTypes) = $this->getEntityTypesAndResourceSets($meta, $modelNames);
        $entityTypes[static::POLYMORPHIC] = $abstract;

        // need to lift EntityTypes, adjust for polymorphic-affected relations, etc
        $biDirect = $this->getRepairedRoundTripRelations();

        // now that endpoints are hooked up, tackle the relationships
        // if we'd tried earlier, we'd be guaranteed to try to hook a relation up to null, which would be bad
        foreach ($biDirect as $line) {
            $this->processRelationLine($line, $entityTypes, $meta);
        }

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
     * @param $meta
     * @param $ends
     * @return array[]
     */
    protected function getEntityTypesAndResourceSets($meta, $ends)
    {
        assert($meta instanceof IMetadataProvider, get_class($meta));
        $entityTypes = [];
        $resourceSets = [];
        $begins = [];
        $numEnds = count($ends);

        for ($i = 0; $i < $numEnds; $i++) {
            $bitter = $ends[$i];
            $fqModelName = $bitter;

            $instance = App::make($fqModelName);
            $name = strtolower($instance->getEndpointName());
            $metaSchema = $instance->getXmlSchema();

            // if for whatever reason we don't get an XML schema, move on to next entry and drop current one from
            // further processing
            if (null == $metaSchema) {
                continue;
            }
            $entityTypes[$fqModelName] = $metaSchema;
            $resourceSets[$fqModelName] = $meta->addResourceSet($name, $metaSchema);
            $begins[] = $bitter;
        }

        return [$entityTypes, $resourceSets, $begins];
    }

    /**
     * @return MetadataRelationHolder
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
                $model = new $name();
                $this->getRelationHolder()->addModel($model);
            }
        }

        return $this->getRelationHolder()->getRelations();
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
        assert(count($hooks) == (count($unknownKeys) + count($knownKeys) - count($dualKeys)));
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
     * Get round-trip relations after inserting polymorphic-powered placeholders
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

    private function processRelationLine($line, $entityTypes, &$meta)
    {
        $principalType = $line['principalType'];
        $principalMult = $line['principalMult'];
        $principalProp = $line['principalProp'];
        $principalRSet = $line['principalRSet'];
        $dependentType = $line['dependentType'];
        $dependentMult = $line['dependentMult'];
        $dependentProp = $line['dependentProp'];
        $dependentRSet = $line['dependentRSet'];

        if (!isset($entityTypes[$principalType]) || !isset($entityTypes[$dependentType])) {
            return;
        }
        $principal = $entityTypes[$principalType];
        $dependent = $entityTypes[$dependentType];
        $isPoly = static::POLYMORPHIC == $principalRSet || static::POLYMORPHIC == $dependentRSet;

        if ($isPoly) {
            $this->attachReferencePolymorphic(
                $meta,
                $principalMult,
                $dependentMult,
                $principal,
                $dependent,
                $principalProp,
                $dependentProp,
                $principalRSet,
                $dependentRSet,
                $principalType,
                $dependentType
            );
            return null;
        }
        $this->attachReferenceNonPolymorphic(
            $meta,
            $principalMult,
            $dependentMult,
            $principal,
            $dependent,
            $principalProp,
            $dependentProp
        );
        return null;
    }

    /**
     * @param $meta
     * @param $principalMult
     * @param $dependentMult
     * @param $principal
     * @param $dependent
     * @param $principalProp
     * @param $dependentProp
     */
    private function attachReferenceNonPolymorphic(
        &$meta,
        $principalMult,
        $dependentMult,
        $principal,
        $dependent,
        $principalProp,
        $dependentProp
    ) {
        //many-to-many
        if ('*' == $principalMult && '*' == $dependentMult) {
            $meta->addResourceSetReferencePropertyBidirectional(
                $principal,
                $dependent,
                $principalProp,
                $dependentProp
            );
            return;
        }
        //one-to-one
        if ('0..1' == $principalMult || '0..1' == $dependentMult) {
            assert($principalMult != $dependentMult, 'Cannot have both ends with 0..1 multiplicity');
            $meta->addResourceReferenceSinglePropertyBidirectional(
                $principal,
                $dependent,
                $principalProp,
                $dependentProp
            );
            return;
        }
        assert($principalMult != $dependentMult, 'Cannot have both ends same multiplicity for 1:N relation');
        //principal-one-to-dependent-many
        if ('1' == $principalMult) {
            $meta->addResourceReferencePropertyBidirectional(
                $principal,
                $dependent,
                $principalProp,
                $dependentProp
            );
            return;
        }
        //dependent-one-to-principal-many
        $meta->addResourceReferencePropertyBidirectional(
            $dependent,
            $principal,
            $dependentProp,
            $principalProp
        );
        return;
    }

    /**
     * @param $meta
     * @param $principalMult
     * @param $dependentMult
     * @param $principal
     * @param $dependent
     * @param $principalProp
     * @param $dependentProp
     */
    private function attachReferencePolymorphic(
        &$meta,
        $principalMult,
        $dependentMult,
        $principal,
        $dependent,
        $principalProp,
        $dependentProp,
        $principalRSet,
        $dependentRSet,
        $principalType,
        $dependentType
    ) {
        $prinPoly = static::POLYMORPHIC == $principalRSet;
        $depPoly = static::POLYMORPHIC == $dependentRSet;
        $principalSet = (!$prinPoly) ? $principal->getCustomState()
            : $meta->resolveResourceSet(static::POLYMORPHIC_PLURAL);
        $dependentSet = (!$depPoly) ? $dependent->getCustomState()
            : $meta->resolveResourceSet(static::POLYMORPHIC_PLURAL);
        assert($principalSet instanceof ResourceSet, $principalRSet);
        assert($dependentSet instanceof ResourceSet, $dependentRSet);

        $isPrincipalAdded = null !== $principal->resolveProperty($principalProp);
        $isDependentAdded = null !== $dependent->resolveProperty($dependentProp);
        $prinMany = '*' == $principalMult;
        $depMany = '*' == $dependentMult;

        $prinConcrete = null;
        $depConcrete = null;
        if ($prinPoly) {
            $prinBitz = explode('\\', $principalType);
            $prinConcrete = $meta->resolveResourceType($prinBitz[count($prinBitz)-1]);
            assert(static::POLYMORPHIC !== $prinConcrete->getName());
        }
        if ($depPoly) {
            $depBitz = explode('\\', $dependentType);
            $depConcrete = $meta->resolveResourceType($depBitz[count($depBitz)-1]);
            assert(static::POLYMORPHIC !== $depConcrete->getName());
        }

        if (!$isPrincipalAdded) {
            if ('*' == $principalMult || $depMany) {
                $meta->addResourceSetReferenceProperty($principal, $principalProp, $dependentSet, $depConcrete);
            } else {
                $meta->addResourceReferenceProperty(
                    $principal,
                    $principalProp,
                    $dependentSet,
                    $prinPoly,
                    $depMany,
                    $depConcrete
                );
            }
        }
        if (!$isDependentAdded) {
            if ('*' == $dependentMult || $prinMany) {
                $meta->addResourceSetReferenceProperty($dependent, $dependentProp, $principalSet, $prinConcrete);
            } else {
                $meta->addResourceReferenceProperty(
                    $dependent,
                    $dependentProp,
                    $principalSet,
                    $depPoly,
                    $prinMany,
                    $prinConcrete
                );
            }
        }
        return;
    }

    public function reset()
    {
        self::$relationCache = null;
        self::$isBooted = false;
    }

    /**
     * Resolve possible reverse relation property names
     *
     * @param Model $source
     * @param Model $target
     * @param $propName
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
