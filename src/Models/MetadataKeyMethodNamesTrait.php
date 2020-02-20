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

        $fkList = ['getQualifiedForeignKeyName', 'getForeignKey'];
        $rkList = ['getQualifiedRelatedKeyName', 'getOtherKey', 'getOwnerKey', 'getQualifiedOwnerKeyName'];

        $fkMethodName = null;
        $rkMethodName = null;

        $methodList = $this->getRelationClassMethods($foo);
        $fkMethodName = 'getQualifiedForeignPivotKeyName';
        $fkIntersect = array_values(array_intersect($fkList, $methodList));
        $fkMethodName = (0 < count($fkIntersect)) ? $fkIntersect[0] : $fkMethodName;
        if (!(in_array($fkMethodName, $methodList))) {
            $msg = 'Selected method, ' . $fkMethodName . ', not in method list';
            throw new InvalidOperationException($msg);
        }
        $rkMethodName = 'getQualifiedRelatedPivotKeyName';
        $rkIntersect = array_values(array_intersect($rkList, $methodList));
        $rkMethodName = (0 < count($rkIntersect)) ? $rkIntersect[0] : $rkMethodName;
        if (!(in_array($rkMethodName, $methodList))) {
            $msg = 'Selected method, ' . $rkMethodName . ', not in method list';
            throw new InvalidOperationException($msg);
        }
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

        $fkMethodName = null;
        $rkMethodName = null;

        foreach ($fkList as $methodName) {
            if (method_exists($foo, $methodName)) {
                $fkMethodName = $methodName;
                break;
            }
        }

        if (null === $fkMethodName) {
            $msg = 'Expected at least 1 element in foreign-key list, got 0';
            throw new InvalidOperationException($msg);
        }

        foreach ($rkList as $methodName) {
            if (method_exists($foo, $methodName)) {
                $rkMethodName = $methodName;
                break;
            }
        }
        if (null === $rkMethodName) {
            $msg = 'Expected at least 1 element in related-key list, got 0';
            throw new InvalidOperationException($msg);
        }
        return [$fkMethodName, $rkMethodName];
    }

    protected function polyglotThroughKeyMethodNames(HasManyThrough $foo)
    {
        $thruList = ['getThroughKey', 'getQualifiedFirstKeyName'];

        $methodList = $this->getRelationClassMethods($foo);
        $thruCombo = array_values(array_intersect($thruList, $methodList));
        return $thruCombo[0];
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
     * @param  Relation $rel
     * @return array
     */
    protected function getRelationClassMethods(Relation $rel)
    {
        $methods = get_class_methods($rel);

        return $methods;
    }
}
