<?php

namespace AlgoWeb\PODataLaravel\Providers;

use AlgoWeb\PODataLaravel\Controllers\MetadataControllerContainer;
use AlgoWeb\PODataLaravel\Controllers\MetadataControllerTrait;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use POData\Common\InvalidOperationException;

class MetadataControllerProvider extends MetadataBaseProvider
{
    /*
     * Optional crud verbs - these need to be deduplicated for empty mappings
     */
    protected $optionalVerbs = ['bulkCreate', 'bulkUpdate'];

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

        /** @var MetadataControllerContainer $meta */
        $meta = App::make('metadataControllers');

        $classes = $this->getClassMap();
        $ends = $this->getCandidateControllers($classes);

        // now process each class that uses the metadata controller trait and stick results in $metamix
        $metamix = [];
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
                    $isOptional = in_array($barrel, $this->optionalVerbs);
                    $alreadyKey = array_key_exists($barrel, $metamix[$key]);
                    if ($isOptional) {
                        // if we've picked up a default mapping for an optional verb, then we can overwrite it
                        $alreadyKey = null === $metamix[$key][$barrel] ? false : $alreadyKey;
                    }
                    if ($alreadyKey) {
                        $msg = 'Mapping already defined for model ' . $key . ' and CRUD verb ' . $barrel;
                        throw new InvalidOperationException($msg);
                    }
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
        $this->app->singleton(
            'metadataControllers',
            function () {
                return new MetadataControllerContainer();
            }
        );
    }

    /**
     * @param $classes
     * @throws \Exception
     * @return array
     */
    protected function getCandidateControllers($classes)
    {
        $ends = [];
        $startName = $this->getAppNamespace();
        $rawClasses = [];
        foreach ($classes as $name) {
            // not in app namespace, keep moving
            if (!\Illuminate\Support\Str::startsWith($name, $startName)) {
                continue;
            }
            // if class doesn't exist (for whatever reason), skip it now rather than muck about later
            if (!class_exists($name)) {
                continue;
            }
            $rawClasses[] = $name;
        }

        foreach ($rawClasses as $name) {
            try {
                if (in_array(MetadataControllerTrait::class, class_uses($name, false))) {
                    $result = App::make($name);
                    $ends[] = $result;
                    if (!$result instanceof Controller) {
                        throw new InvalidOperationException('Resolved result not a controller');
                    }
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
