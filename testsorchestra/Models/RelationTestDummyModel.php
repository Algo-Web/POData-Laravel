<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 14/02/20
 * Time: 1:53 AM
 */

namespace AlgoWeb\PODataLaravel\Orchestra\Tests\Models;

use AlgoWeb\PODataLaravel\Models\MetadataTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

class RelationTestDummyModel extends Model
{
    use MetadataTrait {
        MetadataTrait::polyglotKeyMethodNames as parentPolyglot;
        MetadataTrait::polyglotKeyMethodBackupNames as parentPolyglotBackup;
        MetadataTrait::getRelationClassMethods as relateMethods;
    }

    protected $relMethods = [];

    public function getRelationClassMethods(Relation $rel)
    {
        $class = get_class($rel);
        if (array_key_exists($class, $this->relMethods)) {
            return $this->relMethods[$class];
        }
        return $this->relateMethods($rel);
    }

    public function setRelationClassMethods(array $methods)
    {
        $this->relMethods = $methods;
    }

    public function polyglotKeyMethodNames(Relation $foo, $condition = false)
    {
        return $this->parentPolyglot($foo, $condition);
    }

    public function polyglotKeyMethodBackupNames(Relation $foo, $condition = false)
    {
        return $this->parentPolyglotBackup($foo, $condition);
    }

    public function bigReset()
    {
        $this->reset();
        $this->resetKeyMethod();
    }
}
