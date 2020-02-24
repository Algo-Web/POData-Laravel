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
        list($fkMethodName, $rkMethodName) = $this->polyglotKeyMethodNames($foo);
        return [$thruName, $fkMethodName, $rkMethodName];
    }

    /**
     * @param Relation $foo
     * @param mixed    $condition
     *
     * @throws InvalidOperationException
     * @return array
     */
    protected function polyglotKeyMethodNames(Relation $foo)
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
        return array_diff(
            get_class_methods($model),
            get_class_methods(\Illuminate\Database\Eloquent\Model::class),
            //TODO: sandi what will happen if Mock is not installed (I.e. Production?)
            get_class_methods(Mock::class),
            get_class_methods(MetadataTrait::class)
        );
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
