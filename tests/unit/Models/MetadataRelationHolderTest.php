<?php

namespace AlgoWeb\PODataLaravel\Models;

use Illuminate\Database\Eloquent\Model;
use Mockery as m;

class MetadataRelationHolderTest extends TestCase
{
    public function testAddBadModel()
    {
        $foo = new MetadataRelationHolder();
        $model = new TestMorphUnexposedTarget();

        $expected = 'Supplied model does not use MetadataTrait';
        $actual = null;

        try {
            $foo->addModel($model);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testAddGoodModelTwice()
    {
        $foo = new MetadataRelationHolder();
        $model = new TestModel();

        $expected = 'AlgoWeb\PODataLaravel\Models\TestModel already added';
        $actual = null;

        $foo->addModel($model);

        try {
            $foo->addModel($model);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetRelationsOnMissingModel()
    {
        $foo = new MetadataRelationHolder();
        $model = new TestModel();

        $foo->addModel($model);

        $expected = 'AlgoWeb\PODataLaravel\Models\TestMorphUnexposedTarget does not exist in holder';
        $actual = null;

        try {
            $foo->getRelationsByKey(TestMorphUnexposedTarget::class, 'id');
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetRelationsOnMissingForeignKey()
    {
        $foo = new MetadataRelationHolder();
        $model = new TestModel();

        $foo->addModel($model);

        $expected = 'Key foo_id not registered on AlgoWeb\PODataLaravel\Models\TestModel';
        $actual = null;

        try {
            $foo->getRelationsByKey(TestModel::class, 'foo_id');
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetSingleMonomorphicRelationPair()
    {
        $expected[] = [
            "principalType" => TestMonomorphicOneAndManySource::class,
            "principalRSet" => TestMonomorphicOneAndManySource::class,
            "principalMult" => "1",
            "principalProp" => "manyTarget",
            "dependentType" => TestMonomorphicOneAndManyTarget::class,
            "dependentRSet" => TestMonomorphicOneAndManyTarget::class,
            "dependentMult" => "*",
            "dependentProp" => "manySource"
        ];

        $foo = new MetadataRelationHolder();
        $src = new TestMonomorphicOneAndManySource();
        $targ = new TestMonomorphicOneAndManyTarget();

        $foo->addModel($src);
        $foo->addModel($targ);

        $actual = $foo->getRelationsByKey(TestMonomorphicOneAndManySource::class, 'many_id');

        $this->assertEquals(2 * count($expected), count($actual));
        foreach ($expected as $forward) {
            $this->assertTrue(in_array($forward, $actual));
            $reverse = $forward;
            $reverse['principalType'] = $forward['dependentType'];
            $reverse['principalMult'] = $forward['dependentMult'];
            $reverse['principalProp'] = $forward['dependentProp'];
            $reverse['principalRSet'] = $forward['dependentRSet'];
            $reverse['dependentType'] = $forward['principalType'];
            $reverse['dependentMult'] = $forward['principalMult'];
            $reverse['dependentProp'] = $forward['principalProp'];
            $reverse['dependentRSet'] = $forward['principalRSet'];
            $this->assertTrue(in_array($reverse, $actual));
        }
    }

    public function testGetOneMonomorphicRelationPairOfMany()
    {
        $expected = [];
        $expected[] = [
            "principalType" => TestMonomorphicOneAndManySource::class,
            "principalRSet" => TestMonomorphicOneAndManySource::class,
            "principalMult" => "1",
            "principalProp" => "oneTarget",
            "dependentType" => TestMonomorphicOneAndManyTarget::class,
            "dependentRSet" => TestMonomorphicOneAndManyTarget::class,
            "dependentMult" => "0..1",
            "dependentProp" => "oneSource"
        ];

        $foo = new MetadataRelationHolder();
        $src = new TestMonomorphicOneAndManySource();
        $targ = new TestMonomorphicOneAndManyTarget();

        $foo->addModel($src);
        $foo->addModel($targ);

        $actual = $foo->getRelationsByKey(TestMonomorphicOneAndManySource::class, 'one_id');

        $this->assertEquals(2 * count($expected), count($actual));
        foreach ($expected as $forward) {
            $this->assertTrue(in_array($forward, $actual));
            $reverse = $forward;
            $reverse['principalType'] = $forward['dependentType'];
            $reverse['principalMult'] = $forward['dependentMult'];
            $reverse['principalProp'] = $forward['dependentProp'];
            $reverse['principalRSet'] = $forward['dependentRSet'];
            $reverse['dependentType'] = $forward['principalType'];
            $reverse['dependentMult'] = $forward['principalMult'];
            $reverse['dependentProp'] = $forward['principalProp'];
            $reverse['dependentRSet'] = $forward['principalRSet'];
            $this->assertTrue(in_array($reverse, $actual));
        }
    }

    public function testGetMonomorphicBelongsToRelations()
    {
        $expected = [];
        $expected[] = [
            "principalType" => TestMonomorphicOneAndManySource::class,
            "principalRSet" => TestMonomorphicOneAndManySource::class,
            "principalMult" => "1",
            "principalProp" => "oneTarget",
            "dependentType" => TestMonomorphicOneAndManyTarget::class,
            "dependentRSet" => TestMonomorphicOneAndManyTarget::class,
            "dependentMult" => "0..1",
            "dependentProp" => "oneSource"
        ];
        $expected[] = [
            "principalType" => TestMonomorphicOneAndManySource::class,
            "principalRSet" => TestMonomorphicOneAndManySource::class,
            "principalMult" => "1",
            "principalProp" => "manyTarget",
            "dependentType" => TestMonomorphicOneAndManyTarget::class,
            "dependentRSet" => TestMonomorphicOneAndManyTarget::class,
            "dependentMult" => "*",
            "dependentProp" => "manySource"
        ];

        $foo = new MetadataRelationHolder();
        $src = new TestMonomorphicOneAndManyTarget();
        $targ = new TestMonomorphicOneAndManySource();

        $foo->addModel($src);
        $foo->addModel($targ);

        $actual = $foo->getRelationsByKey(TestMonomorphicOneAndManyTarget::class, 'id');

        $this->assertEquals(2 * count($expected), count($actual));
        foreach ($expected as $forward) {
            $this->assertTrue(in_array($forward, $actual));
            $reverse = $forward;
            $reverse['principalType'] = $forward['dependentType'];
            $reverse['principalMult'] = $forward['dependentMult'];
            $reverse['principalProp'] = $forward['dependentProp'];
            $reverse['principalRSet'] = $forward['dependentRSet'];
            $reverse['dependentType'] = $forward['principalType'];
            $reverse['dependentMult'] = $forward['principalMult'];
            $reverse['dependentProp'] = $forward['principalProp'];
            $reverse['dependentRSet'] = $forward['principalRSet'];
            $this->assertTrue(in_array($reverse, $actual));
        }
    }

    public function testGetMonomorphicBelongsToRelationsWithOtherEndMispointed()
    {
        $expected = [];
        $expected[] = [
            "principalType" => TestMonomorphicOneAndManySource::class,
            "principalRSet" => TestMonomorphicOneAndManySource::class,
            "principalMult" => "1",
            "principalProp" => "oneTarget",
            "dependentType" => TestMonomorphicOneAndManyTarget::class,
            "dependentRSet" => TestMonomorphicOneAndManyTarget::class,
            "dependentMult" => "0..1",
            "dependentProp" => "oneSource"
        ];
        $expected[] = [
            "principalType" => TestMonomorphicOneAndManySource::class,
            "principalRSet" => TestMonomorphicOneAndManySource::class,
            "principalMult" => "1",
            "principalProp" => "manyTarget",
            "dependentType" => TestMonomorphicOneAndManyTarget::class,
            "dependentRSet" => TestMonomorphicOneAndManyTarget::class,
            "dependentMult" => "*",
            "dependentProp" => "manySource"
        ];

        $foo = new MetadataRelationHolder();
        $src = new TestMonomorphicOneAndManyTarget();
        $targ = new TestMonomorphicOneAndManySource();
        $remix = new TestMonomorphicTarget();

        $foo->addModel($src);
        $foo->addModel($targ);
        $foo->addModel($remix);

        $actual = $foo->getRelationsByKey(TestMonomorphicOneAndManyTarget::class, 'id');

        $this->assertEquals(2 * count($expected), count($actual));
        foreach ($expected as $forward) {
            $this->assertTrue(in_array($forward, $actual));
            $reverse = $forward;
            $reverse['principalType'] = $forward['dependentType'];
            $reverse['principalMult'] = $forward['dependentMult'];
            $reverse['principalProp'] = $forward['dependentProp'];
            $reverse['principalRSet'] = $forward['dependentRSet'];
            $reverse['dependentType'] = $forward['principalType'];
            $reverse['dependentMult'] = $forward['principalMult'];
            $reverse['dependentProp'] = $forward['principalProp'];
            $reverse['dependentRSet'] = $forward['principalRSet'];
            $this->assertTrue(in_array($reverse, $actual));
        }
    }

    public function testGetMonomorphicManyToMany()
    {
        $expected = [];
        $expected[] = [
            "principalType" => TestMonomorphicManySource::class,
            "principalRSet" => TestMonomorphicManySource::class,
            "principalMult" => "*",
            "principalProp" => "manySource",
            "dependentType" => TestMonomorphicManyTarget::class,
            "dependentRSet" => TestMonomorphicManyTarget::class,
            "dependentMult" => "*",
            "dependentProp" => "manyTarget"
        ];

        $foo = new MetadataRelationHolder();
        $src = new TestMonomorphicManySource();
        $targ = new TestMonomorphicManyTarget();

        $foo->addModel($src);
        $foo->addModel($targ);

        $actual = $foo->getRelationsByKey(TestMonomorphicManySource::class, 'many_source');

        $this->assertEquals(2 * count($expected), count($actual));
        foreach ($expected as $forward) {
            $this->assertTrue(in_array($forward, $actual));
            $reverse = $forward;
            $reverse['principalType'] = $forward['dependentType'];
            $reverse['principalMult'] = $forward['dependentMult'];
            $reverse['principalProp'] = $forward['dependentProp'];
            $reverse['principalRSet'] = $forward['dependentRSet'];
            $reverse['dependentType'] = $forward['principalType'];
            $reverse['dependentMult'] = $forward['principalMult'];
            $reverse['dependentProp'] = $forward['principalProp'];
            $reverse['dependentRSet'] = $forward['principalRSet'];
            $this->assertTrue(in_array($reverse, $actual));
        }

        $actual = $foo->getRelationsByKey(TestMonomorphicManyTarget::class, 'many_id');

        $this->assertEquals(2 * count($expected), count($actual));
        foreach ($expected as $forward) {
            $this->assertTrue(in_array($forward, $actual));
            $reverse = $forward;
            $reverse['principalType'] = $forward['dependentType'];
            $reverse['principalMult'] = $forward['dependentMult'];
            $reverse['principalProp'] = $forward['dependentProp'];
            $reverse['principalRSet'] = $forward['dependentRSet'];
            $reverse['dependentType'] = $forward['principalType'];
            $reverse['dependentMult'] = $forward['principalMult'];
            $reverse['dependentProp'] = $forward['principalProp'];
            $reverse['dependentRSet'] = $forward['principalRSet'];
            $this->assertTrue(in_array($reverse, $actual));
        }
    }

    public function testGetPolymorphicOneToFooUnknownSide()
    {
        $expected = [];
        $expected[] = [
            "principalType" => TestMorphOneSource::class,
            "principalRSet" => TestMorphOneSource::class,
            "principalMult" => "1",
            "principalProp" => "morphTarget",
            "dependentType" => TestMorphTarget::class,
            "dependentRSet" => TestMorphTarget::class,
            "dependentMult" => "0..1",
            "dependentProp" => "morph"
        ];

        $foo = new MetadataRelationHolder();
        $src = new TestMorphOneSource();
        $targ = new TestMorphTarget();

        $foo->addModel($src);
        $foo->addModel($targ);

        $actual = $foo->getRelationsByKey(TestMorphOneSource::class, 'id');

        $this->assertEquals(2 * count($expected), count($actual));
        foreach ($expected as $forward) {
            $this->assertTrue(in_array($forward, $actual));
            $reverse = $forward;
            $reverse['principalType'] = $forward['dependentType'];
            $reverse['principalMult'] = $forward['dependentMult'];
            $reverse['principalProp'] = $forward['dependentProp'];
            $reverse['principalRSet'] = $forward['dependentRSet'];
            $reverse['dependentType'] = $forward['principalType'];
            $reverse['dependentMult'] = $forward['principalMult'];
            $reverse['dependentProp'] = $forward['principalProp'];
            $reverse['dependentRSet'] = $forward['principalRSet'];
            $this->assertTrue(in_array($reverse, $actual));
        }
    }

    public function testGetPolymorphicOneToFooKnownSide()
    {
        $expected = [];
        $expected[] = [
            "principalType" => TestMorphOneSource::class,
            "principalRSet" => TestMorphOneSource::class,
            "principalMult" => "1",
            "principalProp" => "morphTarget",
            "dependentType" => TestMorphTarget::class,
            "dependentRSet" => TestMorphTarget::class,
            "dependentMult" => "0..1",
            "dependentProp" => "morph"
        ];

        $foo = new MetadataRelationHolder();
        $src = new TestMorphOneSource();
        $targ = new TestMorphTarget();

        $foo->addModel($src);
        $foo->addModel($targ);

        $actual = $foo->getRelationsByKey(TestMorphTarget::class, 'morph_id');

        $this->assertEquals(2 * count($expected), count($actual));
        foreach ($expected as $forward) {
            $this->assertTrue(in_array($forward, $actual));
            $reverse = $forward;
            $reverse['principalType'] = $forward['dependentType'];
            $reverse['principalMult'] = $forward['dependentMult'];
            $reverse['principalProp'] = $forward['dependentProp'];
            $reverse['principalRSet'] = $forward['dependentRSet'];
            $reverse['dependentType'] = $forward['principalType'];
            $reverse['dependentMult'] = $forward['principalMult'];
            $reverse['dependentProp'] = $forward['principalProp'];
            $reverse['dependentRSet'] = $forward['principalRSet'];
            $this->assertTrue(in_array($reverse, $actual));
        }
    }

    public function testGetPolymorphicManyToManyUnknownSide()
    {
        $expected = [];
        $expected[] = [
            "principalType" => TestMorphManyToManySource::class,
            "principalRSet" => TestMorphManyToManySource::class,
            "principalMult" => "*",
            "principalProp" => "manySource",
            "dependentType" => TestMorphManyToManyTarget::class,
            "dependentRSet" => TestMorphManyToManyTarget::class,
            "dependentMult" => "*",
            "dependentProp" => "manyTarget"
        ];

        $foo = new MetadataRelationHolder();
        $src = new TestMorphManyToManySource();
        $targ = new TestMorphManyToManyTarget();

        $foo->addModel($src);
        $foo->addModel($targ);

        $actual = $foo->getRelationsByKey(TestMorphManyToManySource::class, 'source_id');

        $this->assertEquals(2 * count($expected), count($actual));
        foreach ($expected as $forward) {
            $this->assertTrue(in_array($forward, $actual));
            $reverse = $forward;
            $reverse['principalType'] = $forward['dependentType'];
            $reverse['principalMult'] = $forward['dependentMult'];
            $reverse['principalProp'] = $forward['dependentProp'];
            $reverse['principalRSet'] = $forward['dependentRSet'];
            $reverse['dependentType'] = $forward['principalType'];
            $reverse['dependentMult'] = $forward['principalMult'];
            $reverse['dependentProp'] = $forward['principalProp'];
            $reverse['dependentRSet'] = $forward['principalRSet'];
            $this->assertTrue(in_array($reverse, $actual));
        }
    }

    public function testGetPolymorphicManyToManyKnownSide()
    {
        $expected = [];
        $expected[] = [
            "principalType" => TestMorphManyToManySource::class,
            "principalRSet" => TestMorphManyToManySource::class,
            "principalMult" => "*",
            "principalProp" => "manySource",
            "dependentType" => TestMorphManyToManyTarget::class,
            "dependentRSet" => TestMorphManyToManyTarget::class,
            "dependentMult" => "*",
            "dependentProp" => "manyTarget"
        ];

        $foo = new MetadataRelationHolder();
        $src = new TestMorphManyToManySource();
        $targ = new TestMorphManyToManyTarget();

        $foo->addModel($src);
        $foo->addModel($targ);

        $actual = $foo->getRelationsByKey(TestMorphManyToManyTarget::class, 'target_id');

        $this->assertEquals(2 * count($expected), count($actual));
        foreach ($expected as $forward) {
            $this->assertTrue(in_array($forward, $actual));
            $reverse = $forward;
            $reverse['principalType'] = $forward['dependentType'];
            $reverse['principalMult'] = $forward['dependentMult'];
            $reverse['principalProp'] = $forward['dependentProp'];
            $reverse['principalRSet'] = $forward['dependentRSet'];
            $reverse['dependentType'] = $forward['principalType'];
            $reverse['dependentMult'] = $forward['principalMult'];
            $reverse['dependentProp'] = $forward['principalProp'];
            $reverse['dependentRSet'] = $forward['principalRSet'];
            $this->assertTrue(in_array($reverse, $actual));
        }
    }

    public function testGetRelationsByClassMonomorphic()
    {
        $expected = [];
        $expected[] = [
            "principalType" => TestMonomorphicOneAndManySource::class,
            "principalRSet" => TestMonomorphicOneAndManySource::class,
            "principalMult" => "1",
            "principalProp" => "oneTarget",
            "dependentType" => TestMonomorphicOneAndManyTarget::class,
            "dependentRSet" => TestMonomorphicOneAndManyTarget::class,
            "dependentMult" => "0..1",
            "dependentProp" => "oneSource"
        ];
        $expected[] = [
            "principalType" => TestMonomorphicOneAndManySource::class,
            "principalRSet" => TestMonomorphicOneAndManySource::class,
            "principalMult" => "1",
            "principalProp" => "manyTarget",
            "dependentType" => TestMonomorphicOneAndManyTarget::class,
            "dependentRSet" => TestMonomorphicOneAndManyTarget::class,
            "dependentMult" => "*",
            "dependentProp" => "manySource"
        ];

        $foo = new MetadataRelationHolder();
        $src = new TestMonomorphicOneAndManyTarget();
        $targ = new TestMonomorphicOneAndManySource();

        $foo->addModel($src);
        $foo->addModel($targ);

        $actual = $foo->getRelationsByClass(TestMonomorphicOneAndManySource::class);

        $this->assertEquals(2 * count($expected), count($actual));
        foreach ($expected as $forward) {
            $this->assertTrue(in_array($forward, $actual));
            $reverse = $forward;
            $reverse['principalType'] = $forward['dependentType'];
            $reverse['principalMult'] = $forward['dependentMult'];
            $reverse['principalProp'] = $forward['dependentProp'];
            $reverse['principalRSet'] = $forward['dependentRSet'];
            $reverse['dependentType'] = $forward['principalType'];
            $reverse['dependentMult'] = $forward['principalMult'];
            $reverse['dependentProp'] = $forward['principalProp'];
            $reverse['dependentRSet'] = $forward['principalRSet'];
            $this->assertTrue(in_array($reverse, $actual));
        }
    }

    public function testGetRelationsByClassPolymorphic()
    {
        $expected = [];
        $expected[] = [
            "principalType" => TestMorphOneSource::class,
            "principalRSet" => TestMorphOneSource::class,
            "principalMult" => "1",
            "principalProp" => "morphTarget",
            "dependentType" => TestMorphTarget::class,
            "dependentRSet" => TestMorphTarget::class,
            "dependentMult" => "0..1",
            "dependentProp" => "morph"
        ];
        $expected[] = [
            "principalType" => TestMorphOneSourceAlternate::class,
            "principalRSet" => TestMorphOneSourceAlternate::class,
            "principalMult" => "1",
            "principalProp" => "morphTarget",
            "dependentType" => TestMorphTarget::class,
            "dependentRSet" => TestMorphTarget::class,
            "dependentMult" => "0..1",
            "dependentProp" => "morph"
        ];
        $expected[] = [
            "principalType" => TestMorphManySource::class,
            "principalRSet" => TestMorphManySource::class,
            "principalMult" => "1",
            "principalProp" => "morphTarget",
            "dependentType" => TestMorphTarget::class,
            "dependentRSet" => TestMorphTarget::class,
            "dependentMult" => "*",
            "dependentProp" => "morph"
        ];
        $expected[] = [
            "principalType" => TestMorphManySourceAlternate::class,
            "principalRSet" => TestMorphManySourceAlternate::class,
            "principalMult" => "1",
            "principalProp" => "morphTarget",
            "dependentType" => TestMorphTarget::class,
            "dependentRSet" => TestMorphTarget::class,
            "dependentMult" => "*",
            "dependentProp" => "morph"
        ];

        $foo = new MetadataRelationHolder();
        $foo->addModel(new TestMorphTarget());
        $foo->addModel(new TestMorphOneSourceAlternate());
        $foo->addModel(new TestMorphOneSource());
        $foo->addModel(new TestMorphManySource());
        $foo->addModel(new TestMorphManySourceAlternate());

        $actual = $foo->getRelationsByClass(TestMorphTarget::class);

        $this->assertEquals(2 * count($expected), count($actual));
        foreach ($expected as $forward) {
            $this->assertTrue(in_array($forward, $actual));
            $reverse = $forward;
            $reverse['principalType'] = $forward['dependentType'];
            $reverse['principalMult'] = $forward['dependentMult'];
            $reverse['principalProp'] = $forward['dependentProp'];
            $reverse['principalRSet'] = $forward['dependentRSet'];
            $reverse['dependentType'] = $forward['principalType'];
            $reverse['dependentMult'] = $forward['principalMult'];
            $reverse['dependentProp'] = $forward['principalProp'];
            $reverse['dependentRSet'] = $forward['principalRSet'];
            $this->assertTrue(in_array($reverse, $actual));
        }
    }

    public function testGetRelationsForDualPolymorphic()
    {
        $expected = [];
        $expected[] = [
            "principalType" => TestPolymorphicDualSource::class,
            "principalRSet" => TestPolymorphicDualSource::class,
            "principalMult" => "1",
            "principalProp" => "morphAlternate",
            "dependentType" => TestMorphTargetAlternate::class,
            "dependentRSet" => TestMorphTargetAlternate::class,
            "dependentMult" => "0..1",
            "dependentProp" => "morph"
        ];

        $foo = new MetadataRelationHolder();
        $foo->addModel(new TestMorphTarget());
        $foo->addModel(new TestMorphTargetAlternate());
        $foo->addModel(new TestPolymorphicDualSource());

        $actual = $foo->getRelationsByClass(TestMorphTargetAlternate::class);

        $this->assertEquals(2 * count($expected), count($actual));
        foreach ($expected as $forward) {
            $this->assertTrue(in_array($forward, $actual));
            $reverse = $forward;
            $reverse['principalType'] = $forward['dependentType'];
            $reverse['principalMult'] = $forward['dependentMult'];
            $reverse['principalProp'] = $forward['dependentProp'];
            $reverse['principalRSet'] = $forward['dependentRSet'];
            $reverse['dependentType'] = $forward['principalType'];
            $reverse['dependentMult'] = $forward['principalMult'];
            $reverse['dependentProp'] = $forward['principalProp'];
            $reverse['dependentRSet'] = $forward['principalRSet'];
            $this->assertTrue(in_array($reverse, $actual));
        }
    }

    public function testGetRelationsByClassBadClass()
    {
        $foo = new MetadataRelationHolder();
        $model = new TestModel();

        $foo->addModel($model);

        $expected = 'AlgoWeb\PODataLaravel\Models\TestMorphUnexposedTarget does not exist in holder';
        $actual = null;

        try {
            $foo->getRelationsByClass(TestMorphUnexposedTarget::class);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }
}
