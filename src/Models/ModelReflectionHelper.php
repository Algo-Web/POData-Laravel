<?php

declare(strict_types=1);


namespace AlgoWeb\PODataLaravel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Mockery\Mock;
use POData\Common\InvalidOperationException;
use ReflectionException;
use ReflectionMethod;
use SplFileObject;

abstract class ModelReflectionHelper
{
    /** @var string[] */
    protected static $relTypes = [
        'hasMany',
        'hasManyThrough',
        'belongsToMany',
        'hasOne',
        'belongsTo',
        'morphOne',
        'morphTo',
        'morphMany',
        'morphToMany',
        'morphedByMany'
    ];

    /**
     * @param  ReflectionMethod $method
     * @return string
     */
    public static function getCodeForMethod(ReflectionMethod $method): string
    {
        /** @var string $fileName */
        $fileName = $method->getFileName();

        $file = new SplFileObject($fileName);
        $file->seek($method->getStartLine() - 1);
        /** @var string $code */
        $code = '';
        while ($file->key() < $method->getEndLine()) {
            $code .= $file->current();
            $file->next();
        }

        /** @var string $code */
        $code  = preg_replace('/\s\s+/', '', $code);
        /** @var int $begin */
        $begin = strpos($code, 'function(');
        $code  = substr($code, (int)$begin, strrpos($code, '}') - $begin + 1);
        return $code;
    }

    /**
     * @param  Model $model
     * @return string[]
     */
    public static function getModelClassMethods(Model $model): array
    {
        $raw = array_values(array_diff(
            get_class_methods($model),
            get_class_methods(Model::class),
            get_class_methods(Mock::class) ?? [],
            get_class_methods(MetadataTrait::class)
        ));
        $whiteList = $model->getVisible();
        if (0 < count($whiteList)) {
            return array_values(array_intersect($raw, $whiteList));
        }
        $blackList = $model->getHidden();
        return array_values(array_diff($raw, $blackList));
    }

    /**
     * @param  Model               $model
     * @throws ReflectionException
     * @return array|string[]
     */
    public static function getRelationshipsFromMethods(Model $model): array
    {
        $relationships = [];
        $methods       = self::getModelClassMethods($model);
        foreach ($methods as $method) {
            //Use reflection to inspect the code, based on Illuminate/Support/SerializableClosure.php
            $reflection = new ReflectionMethod($model, $method);
            $code       = self::getCodeForMethod($reflection);
            foreach (static::$relTypes as $relation) {
                //Resolve the relation's model to a Relation object.
                if (
                    !stripos($code, sprintf('$this->%s(', $relation)) ||
                    !(($relationObj = $model->{$method}()) instanceof Relation) ||
                    !in_array(MetadataTrait::class, class_uses($relationObj->getRelated()))
                ) {
                    continue;
                }
                $relationships[]= $method;
            }
        }
        return $relationships;
    }
}
