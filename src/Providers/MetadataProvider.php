<?php

namespace AlgoWeb\PODataLaravel\Providers;

use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Cache;
use POData\Providers\Metadata\IMetadataProvider;
use POData\Providers\Metadata\SimpleMetadataProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema as Schema;

class MetadataProvider extends MetadataBaseProvider
{
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
            if (!Schema::hasTable('migrations')) {
                return;
            }
        } catch (\Exception $e) {
            return;
        }

        self::setupRoute();
        $isCaching = true === $this->getIsCaching();
        $hasCache = Cache::has('metadata');

        if ($isCaching && $hasCache) {
            $meta = Cache::get('metadata');
            App::instance('metadata', $meta);
            return;
        }
        $meta = App::make('metadata');

        $modelNames = $this->getCandidateModels();

        list($EntityTypes, $ResourceSets, $ends) = $this->getEntityTypesAndResourceSets($meta, $modelNames);

        // now that endpoints are hooked up, tackle the relationships
        // if we'd tried earlier, we'd be guaranteed to try to hook a relation up to null, which would be bad
        foreach ($ends as $bitter) {
            $fqModelName = $bitter;
            $instance = new $fqModelName();
            $instance->hookUpRelationships($EntityTypes, $ResourceSets);
        }

        $key = 'metadata';
        $this->handlePostBoot($isCaching, $hasCache, $key, $meta);
    }

    private static function setupRoute()
    {
        $valueArray = [];

        Route::any('odata.svc/{section}', 'AlgoWeb\PODataLaravel\Controllers\ODataController@index')
            ->where(['section' => '.*']);
        Route::any('odata.svc', 'AlgoWeb\PODataLaravel\Controllers\ODataController@index');
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

        return array($EntityTypes, $ResourceSets, $begins);
    }
}
