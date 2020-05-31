<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 1/06/20
 * Time: 12:36 AM
 */

namespace AlgoWeb\PODataLaravel\Models;

use Cruxinator\ClassFinder\ClassFinder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;

/**
 * Class ClassReflectionHelper
 * @package AlgoWeb\PODataLaravel\Models
 */
abstract class ClassReflectionHelper
{
    /**
     * @throws \Exception
     * @return string[]
     */
    public static function getCandidateModels(): array
    {
        return ClassFinder::getClasses(
            static::getAppNamespace(),
            function ($className) {
                return in_array(MetadataTrait::class, class_uses($className)) &&
                       is_subclass_of($className, Model::class);
            },
            true
        );
    }

    /**
     * @return string
     */
    public static function getAppNamespace(): string
    {
        try {
            $startName = App::getNamespace();
        } catch (\Exception $e) {
            $startName = 'App';
        }
        return $startName;
    }
}
