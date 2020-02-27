<?php declare(strict_types=1);

namespace Tests\Legacy\AlgoWeb\PODataLaravel\Unit\Models;

use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMonomorphicChildOfMorphTarget;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMonomorphicManySource;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMonomorphicManyTarget;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMonomorphicOneAndManySource;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMonomorphicOneAndManyTarget;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMonomorphicParentOfMorphTarget;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMonomorphicSource;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMonomorphicTarget;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMorphManySource;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMorphManyToManySource;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMorphManyToManyTarget;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMorphOneSource;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMorphTarget;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMorphTargetChild;
use Tests\Legacy\AlgoWeb\PODataLaravel\TestCase;

class MetadataBidirectionalTest extends TestCase
{
    public function testMonomorphicSourceHooks()
    {
        $foo = new TestMonomorphicSource();

        $expected = [
            'manySource',
            'oneSource'
        ];

        $actual = $foo->getRelationships();
        $this->assertTrue(isset($actual));
        $this->assertTrue(is_array($actual));
        $this->assertEquals(count($expected), count($actual));

        foreach ($expected as $key => $outer) {
            $this->assertTrue(isset($actual[$key]));
            $this->assertEquals($expected[$key], $actual[$key]);
        }
    }

    public function testMonomorphicTargetHooks()
    {
        $foo = new TestMonomorphicTarget();

        $expected = ['manyTarget', 'oneTarget'];


        $actual = $foo->getRelationships();
        $this->assertTrue(isset($actual));
        $this->assertTrue(is_array($actual));
        $this->assertEquals(count($expected), count($actual));

        foreach ($expected as $key => $outer) {
            $this->assertTrue(isset($actual[$key]));
            $this->assertEquals($expected[$key], $actual[$key]);
        }
    }

    public function testMonomorphicManyToMany()
    {
        $foo = new TestMonomorphicManySource();
        $bar = new TestMonomorphicManyTarget();

        $expectedFoo = [
            'manySource',
        ];

        $expectedBar = ['manyTarget'];

        $actual = $foo->getRelationships();

        $this->assertTrue(isset($actual));
        $this->assertTrue(is_array($actual));

        foreach ($expectedFoo as $key => $outer) {
            $this->assertTrue(isset($actual[$key]));
            $this->assertEquals($expectedFoo[$key], $actual[$key]);
        }

        $actual = $bar->getRelationships();
        $this->assertTrue(isset($actual));
        $this->assertTrue(is_array($actual));
        foreach ($expectedBar as $key => $outer) {
            $this->assertTrue(isset($actual[$key]));
            $this->assertEquals($expectedBar[$key], $actual[$key]);
        }
    }

    public function testPolymorphicUnknownSide()
    {
        $foo = new TestMorphTarget();

        $expected =  [
            'morph',
            'childMorph',
            'monomorphicChildren',
            'monomorphicParent',
        ];

        $actual = $foo->getRelationships();
        $this->assertTrue(isset($actual));
        $this->assertTrue(is_array($actual));
        $this->assertEquals(count($expected), count($actual));
    }

    public function testPolymorphicKnownManySide()
    {
        $foo  = new TestMorphManySource();
        $targ = TestMorphTarget::class;

        $expected = [
            'morphTarget'
        ];

        $actual = $foo->getRelationships();
        $this->assertTrue(isset($actual));
        $this->assertTrue(is_array($actual));
        $this->assertEquals(count($expected), count($actual));

        foreach ($expected as $key => $outer) {
            $this->assertTrue(isset($actual[$key]));
            $this->assertEquals($expected[$key], $actual[$key]) ;
        }
    }

    public function testPolymorphicKnownOneSide()
    {
        $foo = new TestMorphOneSource();

        $expected = [
            'morphTarget'
        ];

        $actual = $foo->getRelationships();
        $this->assertTrue(isset($actual));
        $this->assertTrue(is_array($actual));
        $this->assertEquals(count($expected), count($actual));

        foreach ($expected as $key => $outer) {
            $this->assertTrue(isset($actual[$key]));
            $this->assertEquals($expected[$key], $actual[$key]);
        }
    }

    public function testPolymorphicManyToManyUnknownSide()
    {
        $foo  = new TestMorphManyToManySource();
        $targ = TestMorphManyToManyTarget::class;

        $expected = ['manySource'];

        $actual = $foo->getRelationships();
        $this->assertTrue(isset($actual));
        $this->assertTrue(is_array($actual));
        $this->assertEquals(count($expected), count($actual));
        foreach ($expected as $key => $outer) {
            $this->assertTrue(isset($actual[$key]));
            $this->assertEquals($expected[$key], $actual[$key]);
        }
    }

    public function testPolymorphicManyToManyKnownSide()
    {
        $foo  = new TestMorphManyToManyTarget();
        $targ = TestMorphManyToManySource::class;

        $expected = ['manyTarget'];

        $actual = $foo->getRelationships();
        $this->assertTrue(isset($actual));
        $this->assertTrue(is_array($actual));
        $this->assertEquals(count($expected), count($actual));
        foreach ($expected as $key => $outer) {
            $this->assertTrue(isset($actual[$key]));
            $this->assertEquals($expected[$key], $actual[$key]);
        }
    }

    public function testMonomorphicRelationsKeyedOnSameField()
    {
        $foo = new TestMonomorphicOneAndManySource();

        $expected = ['oneTarget','twoTarget','manyTarget'];


        $actual = $foo->getRelationships();
        $this->assertTrue(isset($actual));
        $this->assertTrue(is_array($actual));
        $this->assertEquals(count($expected), count($actual));
        foreach ($expected as $key => $outer) {
            $this->assertTrue(isset($actual[$key]));
            $this->assertEquals($expected[$key], $actual[$key]);
        }
    }

    public function testMonomorphicRelationsKeyedOnSameFieldFromChild()
    {
        $foo      = new TestMonomorphicOneAndManyTarget();
        $targ     = new TestMonomorphicOneAndManySource();
        $targName = get_class($targ);

        $expected = ['oneSource','twoSource','manySource'];


        $actual = $foo->getRelationships();
        $this->assertTrue(isset($actual));
        $this->assertTrue(is_array($actual));
        $this->assertEquals($expected, $actual);
        foreach ($expected as $key => $outer) {
            $this->assertTrue(isset($actual[$key]));
            $this->assertEquals($expected[$key], $actual[$key]);
        }
    }
}
