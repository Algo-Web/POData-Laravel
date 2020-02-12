<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 13/02/20
 * Time: 4:22 AM
 */

namespace AlgoWeb\PODataLaravel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\Relation;
use POData\Common\InvalidOperationException;

trait MetadataKeyMethodNamesTrait
{
    protected static $methodAlternate = [];
    protected static $methodPrimary = [];

    /**
     * @param Relation $foo
     * @return array|null
     * @throws InvalidOperationException
     */
    protected function getRelationsHasManyKeyNames($foo)
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
     * @param       $foo
     * @param mixed $condition
     *
     * @return array
     * @throws InvalidOperationException
     */
    protected function polyglotKeyMethodNames($foo, $condition = false)
    {
        // if $condition is falsy, return quickly - don't muck around
        if (!$condition) {
            return [null, null];
        }

        $fkList = ['getQualifiedForeignKeyName', 'getForeignKey'];
        $rkList = ['getQualifiedRelatedKeyName', 'getOtherKey', 'getOwnerKey', 'getQualifiedOwnerKeyName'];

        $fkMethodName = null;
        $rkMethodName = null;

        if (array_key_exists(get_class($foo), static::$methodPrimary)) {
            $line = static::$methodPrimary[get_class($foo)];
            $fkMethodName = $line['fk'];
            $rkMethodName = $line['rk'];
        } else {
            $methodList = get_class_methods(get_class($foo));
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
            $line = ['fk' => $fkMethodName, 'rk' => $rkMethodName];
            static::$methodPrimary[get_class($foo)] = $line;
        }
        return [$fkMethodName, $rkMethodName];
    }

    /**
     * @param Model|Relation $foo
     * @param bool $condition
     * @return array
     * @throws InvalidOperationException
     */
    protected function polyglotKeyMethodBackupNames($foo, $condition = false)
    {
        // if $condition is falsy, return quickly - don't muck around
        if (!$condition) {
            return [null, null];
        }

        $fkList = ['getForeignKey', 'getForeignKeyName', 'getQualifiedFarKeyName'];
        $rkList = ['getOtherKey', 'getQualifiedParentKeyName'];

        $fkMethodName = null;
        $rkMethodName = null;

        if (array_key_exists(get_class($foo), static::$methodAlternate)) {
            $line = static::$methodAlternate[get_class($foo)];
            $fkMethodName = $line['fk'];
            $rkMethodName = $line['rk'];
        } else {
            $methodList = get_class_methods(get_class($foo));
            $fkCombo = array_values(array_intersect($fkList, $methodList));
            if (!(1 <= count($fkCombo))) {
                $msg = 'Expected at least 1 element in foreign-key list, got ' . count($fkCombo);
                throw new InvalidOperationException($msg);
            }
            $fkMethodName = $fkCombo[0];
            if (!(in_array($fkMethodName, $methodList))) {
                $msg = 'Selected method, ' . $fkMethodName . ', not in method list';
                throw new InvalidOperationException($msg);
            }
            $rkCombo = array_values(array_intersect($rkList, $methodList));
            if (!(1 <= count($rkCombo))) {
                $msg = 'Expected at least 1 element in related-key list, got ' . count($rkCombo);
                throw new InvalidOperationException($msg);
            }
            $rkMethodName = $rkCombo[0];
            if (!(in_array($rkMethodName, $methodList))) {
                $msg = 'Selected method, ' . $rkMethodName . ', not in method list';
                throw new InvalidOperationException($msg);
            }
            $line = ['fk' => $fkMethodName, 'rk' => $rkMethodName];
            static::$methodAlternate[get_class($foo)] = $line;
        }
        return [$fkMethodName, $rkMethodName];
    }

    protected function polyglotThroughKeyMethodNames(HasManyThrough $foo)
    {
        $thruList = ['getThroughKey', 'getQualifiedFirstKeyName'];

        $methodList = get_class_methods(get_class($foo));
        $thruCombo = array_values(array_intersect($thruList, $methodList));
        return $thruCombo[0];
    }
}
