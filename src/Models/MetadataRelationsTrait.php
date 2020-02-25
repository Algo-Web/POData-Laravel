<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 13/02/20
 * Time: 1:08 PM.
 */
namespace AlgoWeb\PODataLaravel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Mockery\Mock;
use POData\Common\InvalidOperationException;

trait MetadataRelationsTrait
{
    /**
     * Get model's relationships.
     *
     * @throws InvalidOperationException
     * @throws \ReflectionException
     * @return array
     */
    public function getRelationships()
    {
        return $this->getRelationshipsFromMethods();
    }

    /**
     * @param  \ReflectionMethod         $method
     * @throws InvalidOperationException
     * @return string
     */
    protected function getCodeForMethod(\ReflectionMethod $method) : string
    {
        $fileName = $method->getFileName();

        $file = new \SplFileObject($fileName);
        $file->seek($method->getStartLine() - 1);
        $code = '';
        while ($file->key() < $method->getEndLine()) {
            $code .= $file->current();
            $file->next();
        }

        $code = trim(preg_replace('/\s\s+/', '', $code));
        if (false === stripos($code, 'function')) {
            $msg = 'Function definition must have keyword \'function\'';
            throw new InvalidOperationException($msg);
        }
        $begin = strpos($code, 'function(');
        $code = substr($code, $begin, strrpos($code, '}') - $begin + 1);
        $lastCode = $code[strlen($code) - 1];
        if ('}' != $lastCode) {
            $msg = 'Final character of function definition must be closing brace';
            throw new InvalidOperationException($msg);
        }
        return $code;
    }
    /**
     * @param  Model $model
     * @return array
     */
    protected function getModelClassMethods(Model $model)
    {
        // TODO: Handle case when Mock::class not present
        return array_diff(
            get_class_methods($model),
            get_class_methods(Model::class),
            get_class_methods(Mock::class),
            get_class_methods(MetadataTrait::class)
        );
    }
    /**
     * @param bool $biDir
     *
     * @throws InvalidOperationException
     * @throws \ReflectionException
     * @return array
     */
    protected function getRelationshipsFromMethods()
    {
        /*$biDirVal = true;
        $isCached = isset(static::$relationCategories[$biDirVal]) && !empty(static::$relationCategories[$biDirVal]);
        if ($isCached) {
            return static::$relationCategories[$biDirVal];
        }*/
        $relationships = [];
        /** @var Model $model */
        $model = $this;
        $methods = $this->getModelClassMethods($model);
        foreach ($methods as $method) {
            //Use reflection to inspect the code, based on Illuminate/Support/SerializableClosure.php
            $reflection = new \ReflectionMethod($model, $method);
            $code = $this->getCodeForMethod($reflection);
            foreach (static::$relTypes as $relation) {
                //Resolve the relation's model to a Relation object.
                if (
                    !stripos($code, sprintf('$this->%s(', $relation)) ||
                    !(($relationObj = $model->$method()) instanceof Relation) ||
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
