<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 13/02/20
 * Time: 4:22 AM.
 */
namespace AlgoWeb\PODataLaravel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
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
        $thruName = null;
        if ($foo instanceof HasManyThrough) {
            list($fkMethodName, $rkMethodName) = $this->polyglotKeyMethodBackupNames($foo, true);
            $thruName = $this->polyglotThroughKeyMethodNames($foo);
            return [$thruName, $fkMethodName, $rkMethodName];
        }
        if ($foo instanceof BelongsToMany) {
            list($fkMethodName, $rkMethodName) = $this->polyglotKeyMethodNames($foo, true);
            return [$thruName, $fkMethodName, $rkMethodName];
        }
        list($fkMethodName, $rkMethodName) = $this->polyglotKeyMethodBackupNames($foo, true);
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
        // if $condition is falsy, return quickly - don't muck around
        if (!$condition) {
            return [null, null];
        }

        $fkList = ['getQualifiedForeignPivotKeyName', 'getForeignKey'];
        $rkList = ['getQualifiedRelatedPivotKeyName', 'getQualifiedOwnerKeyName'];

        $fkMethodName = $this->checkMethodNameList($foo, $fkList);

        $rkMethodName = $this->checkMethodNameList($foo, $rkList);

        return [$fkMethodName, $rkMethodName];
    }

    /**
     * @param  Relation                  $foo
     * @param  bool                      $condition
     * @throws InvalidOperationException
     * @return array
     */
    protected function polyglotKeyMethodBackupNames(Relation $foo, $condition = false)
    {
        // if $condition is falsy, return quickly - don't muck around
        if (!$condition) {
            return [null, null];
        }

        $fkList = ['getForeignKey', 'getForeignKeyName', 'getQualifiedFarKeyName'];
        $rkList = ['getOtherKey', 'getQualifiedParentKeyName'];

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
