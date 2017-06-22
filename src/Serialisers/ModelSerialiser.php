<?php

namespace AlgoWeb\PODataLaravel\Serialisers;

use Illuminate\Database\Eloquent\Model;

class ModelSerialiser
{
    // take a supplied Eloquent model with metadata trait and serialise it in bulk.
    // Upstream POData implementation has an N+1 lookup problem that interacts badly with how
    // Eloquent handles property accesses

    private static $mutatorCache = [];
    private static $metadataCache = [];

    public function __construct()
    {

    }

    /**
     * Serialise needed bits of supplied model, taking fast path where possible
     *
     * @param $model
     * @return mixed
     */
    public function bulkSerialise($model)
    {
        $class = get_class($model);
        assert($model instanceof Model, $class);
        // dig up metadata
        if (!isset(static::$metadataCache[$class])) {
            static::$metadataCache[$class] = $model->metadata();
        }
        $meta = static::$metadataCache[$class];
        $keys = array_keys($meta);
        // dig up getter list - we only care about the mutators that end up in metadata
        if (!isset(static::$mutatorCache[$class])) {
            $getterz = [];
            $datez = $model->getDates();
            $castz = $model->getCasts();
            foreach ($keys as $key) {
                if ($model->hasGetMutator($key) || in_array($key, $datez) || array_key_exists($key, $castz)) {
                    $getterz[] = $key;
                }
            }
            static::$mutatorCache[$class] = $getterz;
        }
        $getterz = static::$mutatorCache[$class];
        $result = array_intersect_key($model->getAttributes(), $meta);
        foreach ($keys as $key) {
            if (!isset($result[$key])) {
                $result[$key] = null;
            }
        }
        foreach ($getterz as $getter) {
            $result[$getter] = $model->$getter;
        }

        return $result;
    }

    public function reset()
    {
        static::$mutatorCache = [];
        static::$metadataCache = [];
    }
}
