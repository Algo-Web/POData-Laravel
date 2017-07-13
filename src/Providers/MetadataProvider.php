<?php

namespace AlgoWeb\PODataLaravel\Providers;

use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Cache;
use POData\Providers\Metadata\IMetadataProvider;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\SimpleMetadataProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema as Schema;

class MetadataProvider extends MetadataBaseProvider
{
    protected $multConstraints = [ '0..1' => ['1'], '1' => ['0..1', '*'], '*' => ['1', '*']];
    protected static $METANAMESPACE = "Data";

    /**
     * Bootstrap the application services.  Post-boot.
     *
     * @return void
     */
    public function boot()
    {
        self::$METANAMESPACE = env('ODataMetaNamespace', 'Data');
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

        $modelNames = $this->getCandidateModels();

        list($EntityTypes) = $this->getEntityTypesAndResourceSets($meta, $modelNames);

        // need to lift EntityTypes
        $biDirect = $this->calculateRoundTripRelations();

        // now that endpoints are hooked up, tackle the relationships
        // if we'd tried earlier, we'd be guaranteed to try to hook a relation up to null, which would be bad
        foreach ($biDirect as $line) {
            $this->processRelationLine($line, $EntityTypes, $meta);
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
            return new SimpleMetadataProvider('Data', self::$METANAMESPACE);
        });
    }

    /**
     * @return array
     */
    protected function getCandidateModels()
    {
        $Classes = $this->getClassMap();
        $ends = [];
        $startName = defined('PODATA_LARAVEL_APP_ROOT_NAMESPACE') ? PODATA_LARAVEL_APP_ROOT_NAMESPACE : "App";
        foreach ($Classes as $name) {
            if (\Illuminate\Support\Str::startsWith($name, $startName)) {
                if (in_array("AlgoWeb\\PODataLaravel\\Models\\MetadataTrait", class_uses($name))) {
                    $ends[] = $name;
                }
            }
        }
        return $ends;
    }

    /**
     * @param $meta
     * @param $ends
     * @return array
     */
    protected function getEntityTypesAndResourceSets($meta, $ends)
    {
        assert($meta instanceof IMetadataProvider, get_class($meta));
        $EntityTypes = [];
        $ResourceSets = [];
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
            $EntityTypes[$fqModelName] = $metaSchema;
            $ResourceSets[$fqModelName] = $meta->addResourceSet($name, $metaSchema);
            $begins[] = $bitter;
        }

        return [$EntityTypes, $ResourceSets, $begins];
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

        // deduplicate rawLines - can't use array_unique as array value elements are themselves arrays
        $lines = [];
        foreach ($rawLines as $line) {
            if (!in_array($line, $lines)) {
                $lines[] = $line;
            }
        }

        return $lines;
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
                    $dependentKey = $foreign[$dependentType]['local'];
                    if ($fk != $dependentKey) {
                        continue;
                    }
                    $dependentMult = $foreign[$dependentType]['multiplicity'];
                    $dependentProperty = $foreign[$dependentType]['property'];
                    assert(
                        in_array($dependentMult, $this->multConstraints[$principalMult]),
                        "Cannot pair multiplicities " . $dependentMult . " and " . $principalMult
                    );
                    assert(
                        in_array($principalMult, $this->multConstraints[$dependentMult]),
                        "Cannot pair multiplicities " . $principalMult . " and " . $dependentMult
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
                            "Cannot pair multiplicities " . $dependentMult . " and " . $principalMult
                        );
                        assert(
                            in_array($principalMult, $this->multConstraints[$dependentMult]),
                            "Cannot pair multiplicities " . $principalMult . " and " . $dependentMult
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
     * @return array
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

    private function processRelationLine($line, $EntityTypes, &$meta)
    {
        $principalType = $line['principalType'];
        $principalMult = $line['principalMult'];
        $principalProp = $line['principalProp'];
        $dependentType = $line['dependentType'];
        $dependentMult = $line['dependentMult'];
        $dependentProp = $line['dependentProp'];
        if (!isset($EntityTypes[$principalType]) || !isset($EntityTypes[$dependentType])) {
            return;
        }
        $principal = $EntityTypes[$principalType];
        $dependent = $EntityTypes[$dependentType];
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
            assert($principalMult != $dependentMult, "Cannot have both ends with 0..1 multiplicity");
            $meta->addResourceReferenceSinglePropertyBidirectional(
                $principal,
                $dependent,
                $principalProp,
                $dependentProp
            );
            return;
        }
        assert($principalMult != $dependentMult, "Cannot have both ends same multiplicity for 1:N relation");
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
}
