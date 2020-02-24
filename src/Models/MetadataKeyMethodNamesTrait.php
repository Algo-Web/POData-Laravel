<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 13/02/20
 * Time: 4:22 AM.
 */
namespace AlgoWeb\PODataLaravel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Mockery\Mock;
use POData\Common\InvalidOperationException;

trait MetadataKeyMethodNamesTrait
{
    /**
     * @param  Relation                  $foo
     * @throws InvalidOperationException
     * @return array|null
     */
    protected function getRelationsHasManyKeyNames(Relation $foo)
    {
        $thruName = $foo instanceof HasManyThrough ?
            $this->polyglotThroughKeyMethodNames($foo) :
            null;
        list($fkMethodName, $rkMethodName) = $foo instanceof BelongsToMany ?
             $this->polyglotKeyMethodNames($foo, true ):
             $this->polyglotKeyMethodNames($foo, true);

        return [$thruName, $fkMethodName, $rkMethodName];
    }

    /**
     * @param Relation $foo
     * @param mixed    $condition
     *
     * @throws InvalidOperationException
     * @return array
     */
    protected function polyglotKeyMethodNames(Relation $foo, $condition = false)
    {
        if ($foo instanceof BelongsTo) {
            // getForeignKey for laravel 5.5
            $fkList = ['getForeignKeyName', 'getForeignKey'];
            // getOwnerKeyName for laravel 5.5
            $rkList = ['getOwnerKey', 'getOwnerKeyName'];
        }elseif ($foo instanceof BelongsToMany) {
            $fkList = ['getForeignPivotKeyName'];
            $rkList = ['getRelatedPivotKeyName'];
        }elseif($foo instanceof HasOneOrMany){
            $fkList = ['getForeignKeyName'];
            $rkList = ['getLocalKeyName'];
        }elseif($foo instanceof HasManyThrough) {
            $fkList = ['getQualifiedFarKeyName'];
            $rkList = ['getQualifiedParentKeyName'];
        }else{
            $msg = sprintf('Unknown Relationship Type %s', get_class($foo));
            throw new InvalidOperationException($msg);
        }
        $fkMethodName = $this->checkMethodNameList($foo, $fkList);

        $rkMethodName = $this->checkMethodNameList($foo, $rkList);

        return [$fkMethodName, $rkMethodName];
    }

    /**
     * @param  HasManyThrough            $foo
     * @throws InvalidOperationException
     * @return string
     */
    protected function polyglotThroughKeyMethodNames(HasManyThrough $foo)
    {
        $thruList = ['getThroughKey', 'getQualifiedFirstKeyName'];

        return $this->checkMethodNameList($foo, $thruList);
    }

    /**
     * @param  Model $model
     * @return array
     */
    protected function getModelClassMethods(Model $model)
    {
        $methods = get_class_methods($model);
        $filter = function ($method) {
            return (!method_exists('Illuminate\Database\Eloquent\Model', $method)
                    && !method_exists(Mock::class, $method)
                    && !method_exists(MetadataTrait::class, $method)
            );
        };
        $methods = array_filter($methods, $filter);

        return $methods;
    }

    /**
     * @param  Relation                  $foo
     * @param  array                     $methodList
     * @throws InvalidOperationException
     * @return string
     */
    protected function checkMethodNameList(Relation $foo, array $methodList)
    {
        foreach ($methodList as $methodName) {
            if (method_exists($foo, $methodName)) {
                return $methodName;
            }
        }
        $msg = 'Expected at least 1 element in related-key list, got 0';
        throw new InvalidOperationException($msg);
    }
}
