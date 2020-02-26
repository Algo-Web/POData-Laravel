<?php

namespace AlgoWeb\PODataLaravel\Serialisers;

use Illuminate\Database\Eloquent\Model;
use POData\Common\InvalidOperationException;

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
     * Serialise needed bits of supplied model, taking fast path where possible.
     *
     * @param Model|mixed $model
     *
     * @throws InvalidOperationException
     * @return mixed
     */
    public function bulkSerialise($model)
    {
        $class = get_class($model);
        if (!$model instanceof Model) {
            throw new InvalidOperationException($class);
        }
        // dig up metadata
        if (!isset(self::$metadataCache[$class])) {
            self::$metadataCache[$class] = $model->metadata();
        }
        $meta = self::$metadataCache[$class];
        $keys = array_keys($meta);
        // dig up getter list - we only care about the mutators that end up in metadata
        if (!isset(self::$mutatorCache[$class])) {
            $getterz = [];
            /** @var array $datez */
            $datez = $model->getDates();
            /** @var array $castz */
            $castz = $model->retrieveCasts();
            foreach ($keys as $key) {
                if ($model->hasGetMutator($key) || in_array($key, $datez) || array_key_exists($key, $castz)) {
                    $getterz[] = $key;
                }
            }
            self::$mutatorCache[$class] = $getterz;
        }
        $getterz = self::$mutatorCache[$class];
        $modelAttrib = $model->getAttributes();
        $result = array_intersect_key($modelAttrib, $meta);
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
}
