<?php

namespace AlgoWeb\PODataLaravel\Providers;

use AlgoWeb\PODataLaravel\Controllers\MetadataControllerTrait;
use AlgoWeb\PODataLaravel\Controllers\TestController;
use Illuminate\Routing\Controller;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Cache;
use AlgoWeb\PODataLaravel\Controllers\MetadataControllerContainer;
use Illuminate\Support\Facades\App;

class MetadataControllerProvider extends MetadataBaseProvider
{
    /**
     * Bootstrap the application services.  Post-boot.
     *
     * @return void
     */
    public function boot()
    {
        $isCaching = true === $this->getIsCaching();
        $hasCache = null;

        if ($isCaching) {
            $hasCache = Cache::has('metadataControllers');
            if ($hasCache) {
                $meta = Cache::get('metadataControllers');
                App::instance('metadataControllers', $meta);
                return;
            }
        }

        $meta = App::make('metadataControllers');

        $Classes = $this->getClassMap();
        $ends = $this->getCandidateControllers($Classes);

        // now process each class that uses the metadata controller trait and stick results in $metamix
        $metamix = [];
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

        $key = 'metadataControllers';
        $this->handlePostBoot($isCaching, $hasCache, $key, $meta);
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

    /**
     * @param $Classes
     * @return array
     * @throws \Exception
     */
    protected function getCandidateControllers($Classes)
    {
        $ends = [];
        $startName = defined('PODATA_LARAVEL_APP_ROOT_NAMESPACE') ? PODATA_LARAVEL_APP_ROOT_NAMESPACE : "App";
        foreach ($Classes as $name) {
            // not in app namespace, keep moving
            if (!\Illuminate\Support\Str::startsWith($name, $startName)) {
                continue;
            }
            // if class doesn't exist (for whatever reason), skip it now rather than muck about later
            if (!class_exists($name)) {
                continue;
            }
            try {
                if (in_array(MetadataControllerTrait::class, class_uses($name, false))) {
                    $result = App::make($name);
                    $ends[] = $result;
                    assert($result instanceof Controller, "Resolved result not a controller");
                }
            } catch (\Exception $e) {
                if (!App::runningInConsole()) {
                    throw $e;
                }
                // Squash exceptions thrown here when running from CLI so app can continue booting
            }
        }
        return $ends;
    }
}
