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
                $key =  $rel->{$this->checkMethodNameList($rel, ['getForeignKeyName', 'getForeignKey'])}();
                break;
            case $rel instanceof BelongsToMany:
                $key = $rel->getForeignPivotKeyName();
                break;
            case $rel instanceof HasOneOrMany:
                $key = $rel->getForeignKeyName();
                break;
            case $rel instanceof HasManyThrough:
                $key =  $rel->getQualifiedFarKeyName();
                break;
            default:
                $msg = sprintf('Unknown Relationship Type %s', get_class($rel));
                throw new InvalidOperationException($msg);
        }
        $segments = explode('.', $key);
        return end($segments);
    }

    /**
     * @param  Relation                  $rel
     * @throws InvalidOperationException
     * @return mixed
     */
    protected function polyglotRkKey(Relation $rel)
    {
        switch (true) {
            case $rel instanceof BelongsTo:
                $key = $rel->{$this->checkMethodNameList($rel, ['getOwnerKey', 'getOwnerKeyName'])}();
                break;
            case $rel instanceof BelongsToMany:
                $key = $rel->getRelatedPivotKeyName();
                break;
            case $rel instanceof HasOneOrMany:
                $key = $rel->{$this->checkMethodNameList($rel, ['getLocalKeyName', 'getQualifiedParentKeyName'])}();
                break;
            case $rel instanceof HasManyThrough:
                $key = $rel->getQualifiedParentKeyName();
                break;
            default:
                $msg = sprintf('Unknown Relationship Type %s', get_class($rel));
                throw new InvalidOperationException($msg);
        }
        $segments = explode('.', $key);
        return end($segments);
    }

    /**
     * @param  Relation                  $rel
     * @throws InvalidOperationException
     * @return mixed
     */
    protected function polyglotThroughKey(Relation $rel)
    {
        $key = $rel->{$this->checkMethodNameList($rel, ['getThroughKey', 'getQualifiedFirstKeyName'])}();
        $segments = explode('.', $key);
        return end($segments);
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
            get_class_methods(\Illuminate\Database\Eloquent\Model::class),
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
        throw new InvalidOperationException(sprintf($msg, get_class($foo)));
    }
}
