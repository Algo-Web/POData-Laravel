<?php

namespace AlgoWeb\PODataLaravel\Models;

use Mockery as m;

class MetadataBidirectionalTest extends TestCase
{
    public function testMonomorphicSourceHooks()
    {
        $foo = new TestMonomorphicSource();
        $targ = TestMonomorphicTarget::class;

        $expected = [
            'many_source' => [
                'target' => $targ,
                'property' => 'manySource',
                'local' => 'many_id',
                'multiplicity' => '*'
            ],
            'one_source' => [
                'target' => $targ,
                'property' => 'oneSource',
                'local' => 'one_id',
                'multiplicity' => '0..1'
            ]
        ];

        $actual = $foo->getRelationships();
        $this->assertTrue(isset($actual));
        $this->assertTrue(is_array($actual));
        $this->assertEquals(count($expected), count($actual));

        foreach ($expected as $key => $outer) {
            $this->assertTrue(isset($actual[$key]));
            $this->assertTrue(is_array($actual[$key]));
            $this->assertEquals($expected[$key], $actual[$key]);
        }
    }

    public function testMonomorphicTargetHooks()
    {
        $foo = new TestMonomorphicTarget();
        $targ = TestMonomorphicSource::class;

        $expected = [
            'many_id' => [
                'target' => $targ,
                'property' => 'manyTarget',
                'local' => 'many_source',
                'multiplicity' => '1'
            ],
            'one_id' => [
                'target' => $targ,
                'property' => 'oneTarget',
                'local' => 'one_source',
                'multiplicity' => '1'
            ]
        ];

        $actual = $foo->getRelationships();
        $this->assertTrue(isset($actual));
        $this->assertTrue(is_array($actual));
        $this->assertEquals(count($expected), count($actual));

        foreach ($expected as $key => $outer) {
            $this->assertTrue(isset($actual[$key]));
            $this->assertTrue(is_array($actual[$key]));
            $this->assertEquals($expected[$key], $actual[$key]);
        }
    }

    public function testMonomorphicManyToMany()
    {
        $foo = new TestMonomorphicManySource();
        $fooTarg = TestMonomorphicManyTarget::class;
        $bar = new TestMonomorphicManyTarget();
        $barTarg = TestMonomorphicManySource::class;

        $expectedFoo = [ 'many_source' => [
            'target' => $fooTarg,
            'property' => 'manySource',
            'local' => 'many_id',
            'multiplicity' => '*']
        ];
        $expectedBar = [ 'many_id' => [
            'target' => $barTarg,
            'property' => 'manyTarget',
            'local' => 'many_source',
            'multiplicity' => '*']
        ];

        $actual = $foo->getRelationships();
        $this->assertTrue(isset($actual));
        $this->assertTrue(is_array($actual));

        foreach ($expectedFoo as $key => $outer) {
            $this->assertTrue(isset($actual[$key]));
            $this->assertTrue(is_array($actual[$key]));
            $this->assertEquals($expectedFoo[$key], $actual[$key]);
        }

        $actual = $bar->getRelationships();
        foreach ($expectedBar as $key => $outer) {
            $this->assertTrue(isset($actual[$key]));
            $this->assertTrue(is_array($actual[$key]));
            $this->assertEquals($expectedBar[$key], $actual[$key]);
        }
    }

    public function testPolymorphicUnknownSide()
    {
        $foo = new TestMorphTarget();
        $targ = TestMorphTarget::class;

        $expected = [ 'morph_id' => [
            'target' => $targ,
            'property' => 'morph',
            'local' => 'id',
            'multiplicity' => '1']
        ];

        $actual = $foo->getRelationships();
        $this->assertTrue(isset($actual));
        $this->assertTrue(is_array($actual));
        $this->assertEquals(count($expected), count($actual));

        foreach ($expected as $key => $outer) {
            $this->assertTrue(isset($actual[$key]));
            $this->assertTrue(is_array($actual[$key]));
            $this->assertEquals($expected[$key], $actual[$key]);
        }
    }

    public function testPolymorphicKnownManySide()
    {
        $foo = new TestMorphManySource();
        $targ = TestMorphTarget::class;

        $expected = [ 'id' => [
            'target' => $targ,
            'property' => 'morphTarget',
            'local' => 'morph_id',
            'multiplicity' => '*']
        ];

        $actual = $foo->getRelationships();
        $this->assertTrue(isset($actual));
        $this->assertTrue(is_array($actual));
        $this->assertEquals(count($expected), count($actual));

        foreach ($expected as $key => $outer) {
            $this->assertTrue(isset($actual[$key]));
            $this->assertTrue(is_array($actual[$key]));
            $this->assertEquals($expected[$key], $actual[$key]);
        }
    }

    public function testPolymorphicKnownOneSide()
    {
        $foo = new TestMorphOneSource();
        $targ = TestMorphTarget::class;

        $expected = [ 'id' => [
            'target' => $targ,
            'property' => 'morphTarget',
            'local' => 'morph_id',
            'multiplicity' => '0..1']
        ];

        $actual = $foo->getRelationships();
        $this->assertTrue(isset($actual));
        $this->assertTrue(is_array($actual));
        $this->assertEquals(count($expected), count($actual));

        foreach ($expected as $key => $outer) {
            $this->assertTrue(isset($actual[$key]));
            $this->assertTrue(is_array($actual[$key]));
            $this->assertEquals($expected[$key], $actual[$key]);
        }
    }

    public function testPolymorphicManyToManyUnknownSide()
    {
        $foo = new TestMorphManyToManySource();
        $targ = TestMorphManyToManyTarget::class;

        $expected = [ 'source_id' => [
            'target' => $targ,
            'property' => 'manySource',
            'local' => 'target_id',
            'multiplicity' => '*']
        ];

        $actual = $foo->getRelationships();
        $this->assertTrue(isset($actual));
        $this->assertTrue(is_array($actual));
        $this->assertEquals(count($expected), count($actual));

        foreach ($expected as $key => $outer) {
            $this->assertTrue(isset($actual[$key]));
            $this->assertTrue(is_array($actual[$key]));
            $this->assertEquals($expected[$key], $actual[$key]);
        }
    }

    public function testPolymorphicManyToManyKnownSide()
    {
        $foo = new TestMorphManyToManyTarget();
        $targ = TestMorphManyToManySource::class;

        $expected = [ 'target_id' => [
            'target' => $targ,
            'property' => 'manyTarget',
            'local' => 'source_id',
            'multiplicity' => '*']
        ];

        $actual = $foo->getRelationships();
        $this->assertTrue(isset($actual));
        $this->assertTrue(is_array($actual));
        $this->assertEquals(count($expected), count($actual));

        foreach ($expected as $key => $outer) {
            $this->assertTrue(isset($actual[$key]));
            $this->assertTrue(is_array($actual[$key]));
            $this->assertEquals($expected[$key], $actual[$key]);
        }
    }
}
