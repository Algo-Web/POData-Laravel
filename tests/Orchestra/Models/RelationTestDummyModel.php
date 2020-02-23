<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 14/02/20
 * Time: 1:53 AM.
 */
namespace AlgoWeb\PODataLaravel\Orchestra\Tests\Models;

use AlgoWeb\PODataLaravel\Models\MetadataTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\Relation;

class RelationTestDummyModel extends Model
{
    use MetadataTrait {
        MetadataTrait::polyglotKeyMethodNames as parentPolyglot;
        MetadataTrait::polyglotKeyMethodBackupNames as parentPolyglotBackup;
        MetadataTrait::polyglotThroughKeyMethodNames as parentThruNames;
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

    public function polyglotKeyMethodBackupNamesDefault(Relation $foo)
    {
        return $this->parentPolyglotBackup($foo);
    }

    public function polyglotThroughKeyMethodNames(HasManyThrough $foo)
    {
        return $this->parentThruNames($foo);
    }

    public function bigReset()
    {
        $this->reset();
    }


    /**
     * @param  Relation $rel
     * @return array
     */
    protected function relateMethods(Relation $rel)
    {
        $methods = get_class_methods($rel);

        return $methods;
    }
}
