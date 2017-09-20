<?php

namespace AlgoWeb\PODataLaravel\Providers;

use AlgoWeb\PODataLaravel\Models\MetadataGubbinsHolder;
use AlgoWeb\PODataLaravel\Models\MetadataRelationHolder;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Map;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema as Schema;
use POData\Providers\Metadata\SimpleMetadataProvider;
use POData\Providers\Metadata\Type\TypeCode;

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

    private function extract(array $modelNames)
    {
        $objectMap = new Map();
        foreach ($modelNames as $modelName) {
            $modelInstance = App::make($modelName);
            $objectMap->addEntity($modelInstance->extractGubbins());
        }
        return $objectMap;
    }

    private function unify(Map $ObjectMap)
    {
        $mgh = new MetadataGubbinsHolder();
        foreach ($ObjectMap->getEntities() as $entity) {
            $mgh->addEntity($entity);
        }
        $ObjectMap->setAssociations($mgh->getRelations());
        return $ObjectMap;
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
        dd($this->unify($this->extract($modelNames)));

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
        $this->app->singleton(
            'metadata', function ($app) {
            return new SimpleMetadataProvider('Data', self::$metaNAMESPACE);
        }
        );
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
    }

    /**
     * Resolve possible reverse relation property names.
     *
     * @param  Model $source
     * @param  Model $target
     * @param        $propName
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
