<?php

namespace Tests\Legacy\Unit\AlgoWeb\PODataLaravel\Models;

use Tests\Legacy\Facets\AlgoWeb\PODataLaravel\Models\TestMonomorphicChildOfMorphTarget;
use Tests\Legacy\Facets\AlgoWeb\PODataLaravel\Models\TestMonomorphicManySource;
use Tests\Legacy\Facets\AlgoWeb\PODataLaravel\Models\TestMonomorphicManyTarget;
use Tests\Legacy\Facets\AlgoWeb\PODataLaravel\Models\TestMonomorphicOneAndManySource;
use Tests\Legacy\Facets\AlgoWeb\PODataLaravel\Models\TestMonomorphicOneAndManyTarget;
use Tests\Legacy\Facets\AlgoWeb\PODataLaravel\Models\TestMonomorphicParentOfMorphTarget;
use Tests\Legacy\Facets\AlgoWeb\PODataLaravel\Models\TestMonomorphicSource;
use Tests\Legacy\Facets\AlgoWeb\PODataLaravel\Models\TestMonomorphicTarget;
use Tests\Legacy\Facets\AlgoWeb\PODataLaravel\Models\TestMorphManySource;
use Tests\Legacy\Facets\AlgoWeb\PODataLaravel\Models\TestMorphManyToManySource;
use Tests\Legacy\Facets\AlgoWeb\PODataLaravel\Models\TestMorphManyToManyTarget;
use Tests\Legacy\Facets\AlgoWeb\PODataLaravel\Models\TestMorphOneSource;
use Tests\Legacy\Facets\AlgoWeb\PODataLaravel\Models\TestMorphTarget;
use Tests\Legacy\Facets\AlgoWeb\PODataLaravel\Models\TestMorphTargetChild;
use Tests\Legacy\AlgoWeb\PODataLaravel\Models\TestCase;

class MetadataBidirectionalTest extends TestCase
{
    public function testMonomorphicSourceHooks()
    {
        $foo = new TestMonomorphicSource();
        $targ = TestMonomorphicTarget::class;

        $expected = [
            'many_source' =>
                [
                    $targ => [
                        'manySource' =>
                            [ 'property' => 'manySource', 'local' => 'many_id', 'multiplicity' => '*', 'type' => null,
                                'through' => null]]
                ],
            'one_source' =>
                [
                    $targ => [
                        'oneSource' =>
                            ['property' => 'oneSource', 'local' => 'one_id', 'multiplicity' => '0..1', 'type' => null,
                                'through' => null]]
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
                    $targ => [
                        'manyTarget' => [
                        'property' => 'manyTarget',
                        'local' => 'many_source',
                        'multiplicity' => '1',
                        'type' => null,
                        'through' => null
                            ]
                    ]
                ],
            'one_id' =>
                [
                    $targ => [
                        'oneTarget' => [
                        'property' => 'oneTarget',
                        'local' => 'one_source',
                        'multiplicity' => '1',
                        'type' => null,
                        'through' => null
                            ]
                    ]
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
                    $fooTarg => [
                        'manySource' => [
                            'property' => 'manySource',
                            'local' => 'many_id',
                            'multiplicity' => '*',
                            'type' => null,
                            'through' => null
                        ]
                    ]
                ]
        ];
        $expectedBar = [
            'many_id' =>
                [
                    $barTarg => [
                        'manyTarget' => [
                            'property' => 'manyTarget',
                            'local' => 'many_source',
                            'multiplicity' => '*',
                            'type' => null,
                            'through' => null
                        ]
                    ]
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
        $childTarg = TestMorphTargetChild::class;
        $monoParent = TestMonomorphicParentOfMorphTarget::class;
        $monoChild = TestMonomorphicChildOfMorphTarget::class;

        $expected = [
            'morph_id' =>
                [
                    $targ => [ 'morph' => [
                        'property' => 'morph', 'local' => 'id', 'multiplicity' => '1', 'type' => 'known',
                        'through' => null]]
                ],
            'id' =>
                [
                    $childTarg => [ 'childMorph' => [
                        'property' => 'childMorph',
                        'local' => 'morph_id',
                        'multiplicity' => '0..1',
                        'type' => 'unknown',
                        'through' => null
                    ]
                    ],
                    $monoParent => [ 'monomorphicParent' => [
                        'property' => 'monomorphicParent',
                        'local' => 'child_id',
                        'multiplicity' => '1',
                        'type' => null,
                        'through' => null
                    ]
                    ]
                ],
            'parent_id' =>
                [
                    $monoChild => [ 'monomorphicChildren' => [
                        'property' => 'monomorphicChildren',
                        'local' => 'id',
                        'multiplicity' => '*',
                        'type' => null,
                        'through' => null
                    ]
                    ]
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
                    $targ => [
                        'morphTarget' => [
                        'property' => 'morphTarget',
                        'local' => 'morph_id',
                        'multiplicity' => '*',
                        'type' => 'unknown',
                        'through' => null
                            ]
                    ]
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
                    $targ => [
                        'morphTarget' => [
                        'property' => 'morphTarget',
                        'local' => 'morph_id',
                        'multiplicity' => '0..1',
                        'type' => 'unknown',
                        'through' => null
                            ]
                    ]
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
                    $targ => [
                        'manySource' => [
                        'property' => 'manySource',
                        'local' => 'target_id',
                        'multiplicity' => '*',
                        'type' => 'unknown',
                        'through' => null
                            ]
                    ]
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
                    $targ => [
                        'manyTarget' => [
                        'property' => 'manyTarget',
                        'local' => 'source_id',
                        'multiplicity' => '*',
                        'type' => 'known',
                        'through' => null
                            ]
                    ]
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
                    $targ => ['oneTarget' => [
                        'property' => 'oneTarget', 'local' => 'id', 'multiplicity' => '0..1', 'type' => null,
                        'through' => null]],
                    $twoTarg => ['twoTarget' => [
                        'property' => 'twoTarget', 'local' => 'id', 'multiplicity' => '0..1', 'type' => null,
                        'through' => null]]
                ],
            'many_id' =>
                [
                    $targ => ['manyTarget' =>
                        ['property' => 'manyTarget', 'local' => 'id', 'multiplicity' => '*', 'type' => null,
                            'through' => null]]
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

    public function testMonomorphicRelationsKeyedOnSameFieldFromChild()
    {
        $foo = new TestMonomorphicOneAndManyTarget();
        $targ = new TestMonomorphicOneAndManySource();
        $targName = get_class($targ);

        $expected = [
            'id' =>
                [
                    $targName => ['oneSource' => [
                            'property' => 'oneSource', 'local' => 'one_id', 'multiplicity' => '1', 'type' => null,
                        'through' => null],
                        'twoSource' => [
                            'property' => 'twoSource', 'local' => 'two_id', 'multiplicity' => '1', 'type' => null,
                            'through' => null],
                        'manySource' => [
                            'property' => 'manySource', 'local' => 'many_id', 'multiplicity' => '1', 'type' => null,
                            'through' => null]]
                ]
        ];

        $actual = $foo->getRelationships();
        $this->assertTrue(isset($actual));
        $this->assertTrue(is_array($actual));
        $this->assertEquals($expected, $actual);
    }
}
