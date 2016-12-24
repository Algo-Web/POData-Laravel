<?php

namespace AlgoWeb\PODataLaravel\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Cache;
use AlgoWeb\PODataLaravel\Controllers\MetadataControllerContainer;

class MetadataControllerProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.  Post-boot.
     *
     * @return void
     */
    public function boot()
    {
        $isCaching = env('APP_METADATA_CACHING', false);

        if ($isCaching && Cache::has('metadataControllers')) {
            $meta = Cache::get('metadataControllers');
            $this->app->instance('metadataControllers', $meta);
            return;
        }

        $meta = $this->app->make('metadataControllers');

        $classes = get_declared_classes();
        $AutoClass = null;
        foreach ($classes as $class) {
            if (\Illuminate\Support\Str::startsWith($class, "Composer\\Autoload\\ComposerStaticInit")) {
                $AutoClass = $class;
            }
        }

        $metamix = [];
        $ends = array();
        $Classes = $AutoClass::$classMap;
        foreach ($Classes as $name => $file) {
            if (\Illuminate\Support\Str::startsWith($name, "App")) {
                if (in_array("AlgoWeb\\PODataLaravel\\Controllers\\MetadataControllerTrait", class_uses($name))) {
                    $ends[] = new $name;
                }
            }
        }

        // now process each class that uses the metadata controller trait and stick results in $metamix
        $map = null;
        foreach ($ends as $end) {
            $map = $end->getMappings();
            // verify uniqueness - must be exactly one mapping for model-verb combo - different verb mappings for
            // a model can glom onto different controllers
            foreach ($map as $key => $lock) {
                if (!array_key_exists($key, $metamix)) {
                    // if we haven't yet got a mapping for this model, grab it holus-bolus
                    $metamix[$key] = $lock;
                    continue;
                }
                // if we do, make sure we aren't re-adding mappings for any of the CRUD verbs
                foreach ($lock as $barrel => $roll) {
                    assert(
                        !array_key_exists($barrel, $metamix[$key]),
                        'Mapping already defined for model '.$key.' and CRUD verb '.$barrel
                    );
                    $metamix[$key][$barrel] = $roll;
                }

            }
        }

        $meta->setMetadata($metamix);

    }

    /**
     * Register the application services.  Boot-time only.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('metadataControllers', function($app) {
            return new MetadataControllerContainer();
        });
    }
}
