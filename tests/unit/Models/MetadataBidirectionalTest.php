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
            'many_source' =>
                [
                    $targ => [ 'property' => 'manySource', 'local' => 'many_id', 'multiplicity' => '*']
                ],
            'one_source' =>
                [
                    $targ => [ 'property' => 'oneSource', 'local' => 'one_id', 'multiplicity' => '0..1']
                ]
        ];

        $actual = $foo->getRelationships();
        $this->assertTrue(isset($actual));
        $this->assertTrue(is_array($actual));
        $this->assertEquals(count($expected), count($actual));

        foreach ($expected as $key => $outer) {
            $this->assertTrue(isset($actual[$key]));
            $this->assertTrue(is_array($actual[$key]));
            $this->assertEquals(count($expected[$key]), count($actual[$key]));
            foreach ($outer as $innerKey => $innerVal) {
                $this->assertTrue(isset($actual[$key][$innerKey]));
                $this->assertTrue(is_array($actual[$key][$innerKey]));
                $this->assertEquals($expected[$key][$innerKey], $actual[$key][$innerKey]);
            }
        }
    }

    public function testMonomorphicTargetHooks()
    {
        $foo = new TestMonomorphicTarget();
        $targ = TestMonomorphicSource::class;

        $expected = [
            'many_id' =>
                [
                    $targ => [ 'property' => 'manyTarget', 'local' => 'many_source', 'multiplicity' => '1']
                ],
            'one_id' =>
                [
                    $targ => [ 'property' => 'oneTarget', 'local' => 'one_source', 'multiplicity' => '1']
                ]
        ];

        $actual = $foo->getRelationships();
        $this->assertTrue(isset($actual));
        $this->assertTrue(is_array($actual));
        $this->assertEquals(count($expected), count($actual));

        foreach ($expected as $key => $outer) {
            $this->assertTrue(isset($actual[$key]));
            $this->assertTrue(is_array($actual[$key]));
            $this->assertEquals(count($expected[$key]), count($actual[$key]));
            foreach ($outer as $innerKey => $innerVal) {
                $this->assertTrue(isset($actual[$key][$innerKey]));
                $this->assertTrue(is_array($actual[$key][$innerKey]));
                $this->assertEquals($expected[$key][$innerKey], $actual[$key][$innerKey]);
            }
        }
    }

    public function testMonomorphicManyToMany()
    {
        $foo = new TestMonomorphicManySource();
        $fooTarg = TestMonomorphicManyTarget::class;
        $bar = new TestMonomorphicManyTarget();
        $barTarg = TestMonomorphicManySource::class;

        $expectedFoo = [
            'many_source' =>
                [
                    $fooTarg => [ 'property' => 'manySource', 'local' => 'many_id', 'multiplicity' => '*']
                ]
        ];
        $expectedBar = [
            'many_id' =>
                [
                    $barTarg => [ 'property' => 'manyTarget', 'local' => 'many_source',  'multiplicity' => '*']
                ]
        ];

        $actual = $foo->getRelationships();
        $this->assertTrue(isset($actual));
        $this->assertTrue(is_array($actual));

        foreach ($expectedFoo as $key => $outer) {
            $this->assertTrue(isset($actual[$key]));
            $this->assertTrue(is_array($actual[$key]));
            $this->assertEquals(count($expectedFoo[$key]), count($actual[$key]));
            foreach ($outer as $innerKey => $innerVal) {
                $this->assertTrue(isset($actual[$key][$innerKey]));
                $this->assertTrue(is_array($actual[$key][$innerKey]));
                $this->assertEquals($expectedFoo[$key][$innerKey], $actual[$key][$innerKey]);
            }
        }

        $actual = $bar->getRelationships();
        $this->assertTrue(isset($actual));
        $this->assertTrue(is_array($actual));
        foreach ($expectedBar as $key => $outer) {
            $this->assertTrue(isset($actual[$key]));
            $this->assertTrue(is_array($actual[$key]));
            $this->assertEquals(count($expectedBar[$key]), count($actual[$key]));
            foreach ($outer as $innerKey => $innerVal) {
                $this->assertTrue(isset($actual[$key][$innerKey]));
                $this->assertTrue(is_array($actual[$key][$innerKey]));
                $this->assertEquals($expectedBar[$key][$innerKey], $actual[$key][$innerKey]);
            }
        }
    }

    public function testPolymorphicUnknownSide()
    {
        $foo = new TestMorphTarget();
        $targ = TestMorphTarget::class;

        $expected = [
            'morph_id' =>
                [
                    $targ => [ 'property' => 'morph', 'local' => 'id', 'multiplicity' => '1']
                ]
        ];

        $actual = $foo->getRelationships();
        $this->assertTrue(isset($actual));
        $this->assertTrue(is_array($actual));
        $this->assertEquals(count($expected), count($actual));

        foreach ($expected as $key => $outer) {
            $this->assertTrue(isset($actual[$key]));
            $this->assertTrue(is_array($actual[$key]));
            $this->assertEquals(count($expected[$key]), count($actual[$key]));
            foreach ($outer as $innerKey => $innerVal) {
                $this->assertTrue(isset($actual[$key][$innerKey]));
                $this->assertTrue(is_array($actual[$key][$innerKey]));
                $this->assertEquals($expected[$key][$innerKey], $actual[$key][$innerKey]);
            }
        }
    }

    public function testPolymorphicKnownManySide()
    {
        $foo = new TestMorphManySource();
        $targ = TestMorphTarget::class;

        $expected = [
            'id' =>
                [
                    $targ => [ 'property' => 'morphTarget', 'local' => 'morph_id', 'multiplicity' => '*']
                ]
        ];

        $actual = $foo->getRelationships();
        $this->assertTrue(isset($actual));
        $this->assertTrue(is_array($actual));
        $this->assertEquals(count($expected), count($actual));

        foreach ($expected as $key => $outer) {
            $this->assertTrue(isset($actual[$key]));
            $this->assertTrue(is_array($actual[$key]));
            $this->assertEquals(count($expected[$key]), count($actual[$key]));
            foreach ($outer as $innerKey => $innerVal) {
                $this->assertTrue(isset($actual[$key][$innerKey]));
                $this->assertTrue(is_array($actual[$key][$innerKey]));
                $this->assertEquals($expected[$key][$innerKey], $actual[$key][$innerKey]);
            }
        }
    }

    public function testPolymorphicKnownOneSide()
    {
        $foo = new TestMorphOneSource();
        $targ = TestMorphTarget::class;

        $expected = [
            'id' =>
                [
                    $targ => [ 'property' => 'morphTarget', 'local' => 'morph_id', 'multiplicity' => '0..1' ]
                ]
        ];

        $actual = $foo->getRelationships();
        $this->assertTrue(isset($actual));
        $this->assertTrue(is_array($actual));
        $this->assertEquals(count($expected), count($actual));

        foreach ($expected as $key => $outer) {
            $this->assertTrue(isset($actual[$key]));
            $this->assertTrue(is_array($actual[$key]));
            $this->assertEquals(count($expected[$key]), count($actual[$key]));
            foreach ($outer as $innerKey => $innerVal) {
                $this->assertTrue(isset($actual[$key][$innerKey]));
                $this->assertTrue(is_array($actual[$key][$innerKey]));
                $this->assertEquals($expected[$key][$innerKey], $actual[$key][$innerKey]);
            }
        }
    }

    public function testPolymorphicManyToManyUnknownSide()
    {
        $foo = new TestMorphManyToManySource();
        $targ = TestMorphManyToManyTarget::class;

        $expected = [
            'source_id' =>
                [
                    $targ => [ 'property' => 'manySource', 'local' => 'target_id', 'multiplicity' => '*']
                ]
        ];

        $actual = $foo->getRelationships();
        $this->assertTrue(isset($actual));
        $this->assertTrue(is_array($actual));
        $this->assertEquals(count($expected), count($actual));

        foreach ($expected as $key => $outer) {
            $this->assertTrue(isset($actual[$key]));
            $this->assertTrue(is_array($actual[$key]));
            $this->assertEquals(count($expected[$key]), count($actual[$key]));
            foreach ($outer as $innerKey => $innerVal) {
                $this->assertTrue(isset($actual[$key][$innerKey]));
                $this->assertTrue(is_array($actual[$key][$innerKey]));
                $this->assertEquals($expected[$key][$innerKey], $actual[$key][$innerKey]);
            }
        }
    }

    public function testPolymorphicManyToManyKnownSide()
    {
        $foo = new TestMorphManyToManyTarget();
        $targ = TestMorphManyToManySource::class;

        $expected = [
            'target_id' =>
                [
                    $targ => ['property' => 'manyTarget', 'local' => 'source_id', 'multiplicity' => '*']
                ]
        ];

        $actual = $foo->getRelationships();
        $this->assertTrue(isset($actual));
        $this->assertTrue(is_array($actual));
        $this->assertEquals(count($expected), count($actual));

        foreach ($expected as $key => $outer) {
            $this->assertTrue(isset($actual[$key]));
            $this->assertTrue(is_array($actual[$key]));
            $this->assertEquals(count($expected[$key]), count($actual[$key]));
            foreach ($outer as $innerKey => $innerVal) {
                $this->assertTrue(isset($actual[$key][$innerKey]));
                $this->assertTrue(is_array($actual[$key][$innerKey]));
                $this->assertEquals($expected[$key][$innerKey], $actual[$key][$innerKey]);
            }
        }
    }

    public function testMonomorphicRelationsKeyedOnSameField()
    {
        $foo = new TestMonomorphicOneAndManySource();
        $targ = TestMonomorphicOneAndManyTarget::class;
        $twoTarg = TestMonomorphicTarget::class;

        $expected = [
            'one_id' =>
                [
                    $targ => ['property' => 'oneTarget', 'local' => 'id', 'multiplicity' => '0..1'],
                    $twoTarg => ['property' => 'twoTarget', 'local' => 'id', 'multiplicity' => '0..1']
                ],
            'many_id' =>
                [
                    $targ => ['property' => 'manyTarget', 'local' => 'id', 'multiplicity' => '*']
                ]
        ];

        $actual = $foo->getRelationships();
        $this->assertTrue(isset($actual));
        $this->assertTrue(is_array($actual));
        $this->assertEquals(count($expected), count($actual));

        foreach ($expected as $key => $outer) {
            $this->assertTrue(isset($actual[$key]));
            $this->assertTrue(is_array($actual[$key]));
            $this->assertEquals(count($expected[$key]), count($actual[$key]));
            foreach ($outer as $innerKey => $innerVal) {
                $this->assertTrue(isset($actual[$key][$innerKey]));
                $this->assertTrue(is_array($actual[$key][$innerKey]));
                $this->assertEquals($expected[$key][$innerKey], $actual[$key][$innerKey]);
            }
        }
    }
}
