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
    protected function polyglotFkKey(Relation $rel)
    {
        switch (true) {
            case $rel instanceof BelongsTo:
                return $rel->{$this->checkMethodNameList($rel, ['getForeignKeyName', 'getForeignKey'])}();
            case $rel instanceof BelongsToMany:
                return $rel->getForeignPivotKeyName();
            case $rel instanceof HasOneOrMany:
                return $rel->getForeignKeyName();
            case $rel instanceof HasManyThrough:
                $segments = explode('.', $rel->getQualifiedFarKeyName());
                return end($segments);
            default:
                $msg = sprintf('Unknown Relationship Type %s', get_class($rel));
                throw new InvalidOperationException($msg);
        }
    }
    protected function polyglotRkKey(Relation $rel)
    {
        switch (true) {
            case $rel instanceof BelongsTo:
                return $rel->{$this->checkMethodNameList($rel, ['getOwnerKey', 'getOwnerKeyName'])}();
            case $rel instanceof BelongsToMany:
                return $rel->getRelatedPivotKeyName();
            case $rel instanceof HasOneOrMany:
                $segments = explode('.', $rel->{$this->checkMethodNameList($rel, ['getLocalKeyName', 'getQualifiedParentKeyName'])}());
                return end($segments);
            case $rel instanceof HasManyThrough:
                $segments = explode('.', $rel->getQualifiedParentKeyName());
                return end($segments);
            default:
                $msg = sprintf('Unknown Relationship Type %s', get_class($rel));
                throw new InvalidOperationException($msg);
        }
    }

    protected function polyglotThroughKey(Relation $rel){
        if(! $rel instanceof HasManyThrough){
            return null;
        }
        $segments = explode('.', $rel->{$this->checkMethodNameList($rel, ['getThroughKey', 'getQualifiedFirstKeyName'])}());
        return end($segments);
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
        $msg = 'Expected at least 1 element in related-key list, got 0 for relation %s';
        throw new InvalidOperationException(sprintf($msg,get_class($foo)));
    }
}
