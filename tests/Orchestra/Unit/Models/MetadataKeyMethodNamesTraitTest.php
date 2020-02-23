<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 23/02/20
 * Time: 1:26 PM
 */

namespace AlgoWeb\PODataLaravel\Orchestra\Tests\Unit\Models;

use AlgoWeb\PODataLaravel\Orchestra\Tests\Models\RelationTestDummyModel;
use AlgoWeb\PODataLaravel\Orchestra\Tests\TestCase;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\Relation;
use Mockery as m;

class MetadataKeyMethodNamesTraitTest extends TestCase
{
    /**
     * @throws \POData\Common\InvalidOperationException
     */
    public function testBackupMethodNamesWithFalseReturnsNulls()
    {
        $expected = [null, null];

        $foo = new RelationTestDummyModel();

        $rel = m::mock(Relation::class)->makePartial();

        $actual = $foo->polyglotKeyMethodBackupNamesDefault($rel);
        $this->assertEquals($expected, $actual);
    }

    public function testMethodNameListsLandUnaltered()
    {
        $expectedFk = ['getForeignKey', 'getForeignKeyName', 'getQualifiedFarKeyName'];
        $expectedRk = ['getOtherKey', 'getQualifiedParentKeyName'];

        $rel = m::mock(Relation::class)->makePartial();

        $expected = ['STOP!', 'HAMMER TIME!'];

        $foo = m::mock(RelationTestDummyModel::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('checkMethodNameList')->withArgs([$rel, $expectedFk])->andReturn('STOP!')->once();
        $foo->shouldReceive('checkMethodNameList')->withArgs([$rel, $expectedRk])->andReturn('HAMMER TIME!')->once();

        $actual = $foo->polyglotKeyMethodBackupNames($rel, true);
        $this->assertEquals($expected, $actual);
    }

    public function testThruNameListLandsUnaltered()
    {
        $expectedThru = ['getThroughKey', 'getQualifiedFirstKeyName'];

        $rel = m::mock(HasManyThrough::class)->makePartial();

        $expected = 'LARGE HAM UNITED!!';

        $foo = m::mock(RelationTestDummyModel::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('checkMethodNameList')->withArgs([$rel, $expectedThru])
            ->andReturn('LARGE HAM UNITED!!')->once();

        $actual = $foo->polyglotThroughKeyMethodNames($rel);
        $this->assertEquals($expected, $actual);
    }
}
