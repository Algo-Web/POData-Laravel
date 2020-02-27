<?php declare(strict_types=1);
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
    use MetadataTrait;

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

    public function bigReset()
    {
        self::$tableData            = [];
        self::$tableColumnsDoctrine = [];
        self::$tableColumns         = [];
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

    public function polyglotFkKeyAccess(Relation $rel)
    {
        return $this->polyglotFkKey($rel);
    }

    public function polyglotRkKeyAccess(Relation $rel)
    {
        return $this->polyglotRkKey($rel);
    }

    public function checkMethodNameListAccess(Relation $rel, array $methodList)
    {
        return $this->checkMethodNameList($rel, $methodList);
    }
}
