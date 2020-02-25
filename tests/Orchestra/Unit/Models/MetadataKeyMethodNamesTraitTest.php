<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 23/02/20
 * Time: 1:26 PM.
 */
namespace AlgoWeb\PODataLaravel\Orchestra\Tests\Unit\Models;

use AlgoWeb\PODataLaravel\Orchestra\Tests\Models\RelationTestDummyModel;
use AlgoWeb\PODataLaravel\Orchestra\Tests\TestCase;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\Relation;
use Mockery as m;
use POData\Common\InvalidOperationException;

class MetadataKeyMethodNamesTraitTest extends TestCase
{
    public function testFKKeyName()
    {
        $rel = m::mock(Relation::class)->makePartial();

        $foo = new RelationTestDummyModel();

        $this->expectException(InvalidOperationException::class);
        $this->expectExceptionMessage('Unknown Relationship Type');

        $foo->polyglotFkKeyAccess($rel);
    }

    public function testRKKeyName()
    {
        $rel = m::mock(Relation::class)->makePartial();

        $foo = new RelationTestDummyModel();

        $this->expectException(InvalidOperationException::class);
        $this->expectExceptionMessage('Unknown Relationship Type');

        $foo->polyglotRkKeyAccess($rel);
    }

    public function testcheckMethodNameListWithEmptyList()
    {
        $rel = m::mock(Relation::class)->makePartial();

        $foo = new RelationTestDummyModel();

        $this->expectException(InvalidOperationException::class);
        $this->expectExceptionMessage('Expected at least 1 element in related-key list');

        $foo->checkMethodNameListAccess($rel, []);
    }
}
