<?php

namespace AlgoWeb\PODataLaravel\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Cache;
use AlgoWeb\PODataLaravel\Controllers\MetadataControllerContainer;
use Illuminate\Support\Facades\App;

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
            // not in app namespace, keep moving
            if (!\Illuminate\Support\Str::startsWith($name, "App")) {
                continue;
            }
            // if class doesn't exist (for whatever reason), skip it now rather than muck about later
            if (!class_exists($name)) {
                continue;
            }
            try {
                if (in_array(
                    "AlgoWeb\\PODataLaravel\\Controllers\\MetadataControllerTrait",
                    class_uses($name, false)
                )) {
                    $ends[] = new $name();
                }
            } catch (\Exception $e) {
                if (!App::runningInConsole()) {
                    throw $e;
                }
                // Squash exceptions thrown here when running from CLI so app can continue booting
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
        $this->app->singleton('metadataControllers', function ($app) {
            return new MetadataControllerContainer();
        });
    }
}
