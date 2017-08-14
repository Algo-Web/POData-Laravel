<?php

namespace AlgoWeb\PODataLaravel\Providers;

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

class MetadataProvider extends MetadataBaseProvider
{
    protected $multConstraints = [ '0..1' => ['1'], '1' => ['0..1', '*'], '*' => ['1', '*']];
    protected static $metaNAMESPACE = 'Data';
    const POLYMORPHIC = 'polyMorphicPlaceholder';
    const POLYMORPHIC_PLURAL = 'polyMorphicPlaceholders';

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

        $isCaching = true === $this->getIsCaching();
        $meta = Cache::get('metadata');
        $hasCache = null != $meta;

        if ($isCaching && $hasCache) {
            App::instance('metadata', $meta);
            return;
        }
        $meta = App::make('metadata');

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

    public function calculateRoundTripRelations()
    {
        $modelNames = $this->getCandidateModels();

        $hooks = [];
        foreach ($modelNames as $name) {
            $model = new $name();
            $rels = $model->getRelationships();
            // it doesn't matter if a model has no relationships here, that lack will simply be skipped over
            // during hookup processing
            $hooks[$name] = $rels;
        }

        // model relation gubbins are assembled, now the hard bit starts
        // storing assembled bidirectional relationship schema
        $rawLines = [];
        // storing unprocessed relation gubbins for second-pass processing
        $remix = [];
        $this->calculateRoundTripRelationsFirstPass($hooks, $rawLines, $remix);

        // now for second processing pass, to pick up stuff that first didn't handle
        $rawLines = $this->calculateRoundTripRelationsSecondPass($remix, $rawLines);

        $numLines = count($rawLines);
        for ($i = 0; $i < $numLines; $i++) {
            $rawLines[$i]['principalRSet'] = $rawLines[$i]['principalType'];
            $rawLines[$i]['dependentRSet'] = $rawLines[$i]['dependentType'];
        }

        // deduplicate rawLines - can't use array_unique as array value elements are themselves arrays
        $lines = [];
        foreach ($rawLines as $line) {
            if (!in_array($line, $lines)) {
                $lines[] = $line;
            }
        }

        return $lines;
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
                    if (in_array($knownType, $knownKeys)) {
                        if (!isset($knownSide[$knownType][$key])) {
                            $knownSide[$knownType][$key] = [];
                        }
                        assert(isset($knownSide[$knownType][$key]));
                        $knownSide[$knownType][$key][] = $propData['property'];
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
        $rels = $this->calculateRoundTripRelations();
        $groups = $this->getPolymorphicRelationGroups();

        if (0 === count($groups)) {
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
            // if relation is not polymorphic, then move on
            if (!($principalPoly || $dependentPoly)) {
                continue;
            } else {
                // if only one end is a known end of a polymorphic relation
                // for moment we're punting on both
                $oneEnd = $principalPoly !== $dependentPoly;
                assert($oneEnd, 'Multi-generational polymorphic relation chains not implemented');
                $targRels = $principalPoly ? $groups[$principalType] : $groups[$dependentType];
                $targUnknown = $targRels[$principalPoly ? $dependentType : $principalType];
                $targProperty = $principalPoly ? $relation['dependentProp'] : $relation['principalProp'];
                $msg = 'Specified unknown-side property ' . $targProperty . ' not found in polymorphic relation map';
                assert(in_array($targProperty, $targUnknown), $msg);

                $targType = $principalPoly ? 'dependentRSet' : 'principalRSet';
                $rels[$i][$targType] = $placeholder;
                continue;
            }
        }
        return $rels;
    }

    /**
     * @param $remix
     * @param $lines
     * @return array
     */
    private function calculateRoundTripRelationsSecondPass($remix, $lines)
    {
        foreach ($remix as $principalType => $value) {
            foreach ($value as $fk => $localRels) {
                foreach ($localRels as $dependentType => $deets) {
                    $principalMult = $deets['multiplicity'];
                    $principalProperty = $deets['property'];
                    $principalKey = $deets['local'];

                    if (!isset($remix[$dependentType])) {
                        continue;
                    }
                    $foreign = $remix[$dependentType];
                    if (!isset($foreign[$principalKey])) {
                        continue;
                    }
                    $foreign = $foreign[$principalKey];
                    $dependentMult = $foreign[$dependentType]['multiplicity'];
                    $dependentProperty = $foreign[$dependentType]['property'];
                    assert(
                        in_array($dependentMult, $this->multConstraints[$principalMult]),
                        'Cannot pair multiplicities ' . $dependentMult . ' and ' . $principalMult
                    );
                    assert(
                        in_array($principalMult, $this->multConstraints[$dependentMult]),
                        'Cannot pair multiplicities ' . $principalMult . ' and ' . $dependentMult
                    );
                    // generate forward and reverse relations
                    list($forward, $reverse) = $this->calculateRoundTripRelationsGenForwardReverse(
                        $principalType,
                        $principalMult,
                        $principalProperty,
                        $dependentType,
                        $dependentMult,
                        $dependentProperty
                    );
                    // add forward relation
                    $lines[] = $forward;
                    // add reverse relation
                    $lines[] = $reverse;
                }
            }
        }
        return $lines;
    }

    /**
     * @param $hooks
     * @param $lines
     * @param $remix
     */
    private function calculateRoundTripRelationsFirstPass($hooks, &$lines, &$remix)
    {
        foreach ($hooks as $principalType => $value) {
            foreach ($value as $fk => $localRels) {
                foreach ($localRels as $dependentType => $deets) {
                    if (!isset($hooks[$dependentType])) {
                        continue;
                    }
                    $principalMult = $deets['multiplicity'];
                    $principalProperty = $deets['property'];
                    $principalKey = $deets['local'];

                    $foreign = $hooks[$dependentType];
                    $foreign = null != $foreign && isset($foreign[$principalKey]) ? $foreign[$principalKey] : null;

                    if (null != $foreign && isset($foreign[$principalType])) {
                        $foreign = $foreign[$principalType];
                        $dependentMult = $foreign['multiplicity'];
                        $dependentProperty = $foreign['property'];
                        assert(
                            in_array($dependentMult, $this->multConstraints[$principalMult]),
                            'Cannot pair multiplicities ' . $dependentMult . ' and ' . $principalMult
                        );
                        assert(
                            in_array($principalMult, $this->multConstraints[$dependentMult]),
                            'Cannot pair multiplicities ' . $principalMult . ' and ' . $dependentMult
                        );
                        // generate forward and reverse relations
                        list($forward, $reverse) = $this->calculateRoundTripRelationsGenForwardReverse(
                            $principalType,
                            $principalMult,
                            $principalProperty,
                            $dependentType,
                            $dependentMult,
                            $dependentProperty
                        );
                        // add forward relation
                        $lines[] = $forward;
                        // add reverse relation
                        $lines[] = $reverse;
                    } else {
                        if (!isset($remix[$principalType])) {
                            $remix[$principalType] = [];
                        }
                        if (!isset($remix[$principalType][$fk])) {
                            $remix[$principalType][$fk] = [];
                        }
                        if (!isset($remix[$principalType][$fk][$dependentType])) {
                            $remix[$principalType][$fk][$dependentType] = $deets;
                        }
                        assert(isset($remix[$principalType][$fk][$dependentType]));
                    }
                }
            }
        }
    }

    /**
     * @param $principalType
     * @param $principalMult
     * @param $principalProperty
     * @param $dependentType
     * @param $dependentMult
     * @param $dependentProperty
     * @return array[]
     */
    private function calculateRoundTripRelationsGenForwardReverse(
        $principalType,
        $principalMult,
        $principalProperty,
        $dependentType,
        $dependentMult,
        $dependentProperty
    ) {
        $forward = [
            'principalType' => $principalType,
            'principalMult' => $dependentMult,
            'principalProp' => $principalProperty,
            'dependentType' => $dependentType,
            'dependentMult' => $principalMult,
            'dependentProp' => $dependentProperty
        ];
        $reverse = [
            'principalType' => $dependentType,
            'principalMult' => $principalMult,
            'principalProp' => $dependentProperty,
            'dependentType' => $principalType,
            'dependentMult' => $dependentMult,
            'dependentProp' => $principalProperty
        ];
        return [$forward, $reverse];
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
                $dependentRSet
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
        if ('*' == $principalMult) {
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
        $dependentRSet
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

        if (!$isPrincipalAdded) {
            if ('*' == $principalMult || $depMany) {
                $meta->addResourceSetReferenceProperty($principal, $principalProp, $dependentSet);
            } else {
                $meta->addResourceReferenceProperty($principal, $principalProp, $dependentSet, $prinPoly, $depMany);
            }
        }
        if (!$isDependentAdded) {
            if ('*' == $dependentMult || $prinMany) {
                $meta->addResourceSetReferenceProperty($dependent, $dependentProp, $principalSet);
            } else {
                $meta->addResourceReferenceProperty($dependent, $dependentProp, $principalSet, $depPoly, $prinMany);
            }
        }
        return;
    }
}
