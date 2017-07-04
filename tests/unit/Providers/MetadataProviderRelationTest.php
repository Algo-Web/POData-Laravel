<?php

namespace AlgoWeb\PODataLaravel\Providers;

use AlgoWeb\PODataLaravel\Models\TestCase;
use AlgoWeb\PODataLaravel\Models\TestMonomorphicManySource;
use AlgoWeb\PODataLaravel\Models\TestMonomorphicManyTarget;
use AlgoWeb\PODataLaravel\Models\TestMonomorphicOneAndManySource;
use AlgoWeb\PODataLaravel\Models\TestMonomorphicOneAndManyTarget;
use AlgoWeb\PODataLaravel\Models\TestMonomorphicSource;
use AlgoWeb\PODataLaravel\Models\TestMonomorphicTarget;
use AlgoWeb\PODataLaravel\Models\TestMorphManySource;
use AlgoWeb\PODataLaravel\Models\TestMorphManyToManySource;
use AlgoWeb\PODataLaravel\Models\TestMorphManyToManyTarget;
use AlgoWeb\PODataLaravel\Models\TestMorphOneSource;
use AlgoWeb\PODataLaravel\Models\TestMorphTarget;
use Illuminate\Support\Facades\App;
use Mockery as m;

class MetadataProviderRelationTest extends TestCase
{
    public function testMonomorphicSourceAndTarget()
    {
        $app = App::make('app');
        $foo = new MetadataProvider($app);

        // only add one side of the expected relationships here, and explicitly reverse expected before checking for
        // reversed actual
        $expected = [];
        $expected[] = ["principalType" => TestMonomorphicManySource::class,
            "principalMult" => "*",
            "principalProp" => "manySource",
            "dependentType" => TestMonomorphicManyTarget::class,
            "dependentMult" => "*",
            "dependentProp" => "manyTarget"
        ];
        $expected[] = [
            "principalType" => TestMonomorphicSource::class,
            "principalMult" => "1",
            "principalProp" => "oneSource",
            "dependentType" => TestMonomorphicTarget::class,
            "dependentMult" => "0..1",
            "dependentProp" => "oneTarget"
        ];
        $expected[] = [
            "principalType" => TestMonomorphicSource::class,
            "principalMult" => "1",
            "principalProp" => "manySource",
            "dependentType" => TestMonomorphicTarget::class,
            "dependentMult" => "*",
            "dependentProp" => "manyTarget"
        ];
        $expected[] = [
            "principalType" => TestMorphManyToManySource::class,
            "principalMult" => "*",
            "principalProp" => "manySource",
            "dependentType" => TestMorphManyToManyTarget::class,
            "dependentMult" => "*",
            "dependentProp" => "manyTarget"
        ];
        $expected[] = [
            "principalType" => TestMonomorphicOneAndManySource::class,
            "principalMult" => "1",
            "principalProp" => "manyTarget",
            "dependentType" => TestMonomorphicOneAndManyTarget::class,
            "dependentMult" => "*",
            "dependentProp" => "manySource"
        ];
        $expected[] = [
            "principalType" => TestMorphManySource::class,
            "principalMult" => "1",
            "principalProp" => "morphTarget",
            "dependentType" => TestMorphTarget::class,
            "dependentMult" => "*",
            "dependentProp" => "morph"
        ];
        $expected[] = [
            "principalType" => TestMorphOneSource::class,
            "principalMult" => "1",
            "principalProp" => "morphTarget",
            "dependentType" => TestMorphTarget::class,
            "dependentMult" => "0..1",
            "dependentProp" => "morph"
        ];

        $actual = $foo->calculateRoundTripRelations();
        $this->assertTrue(is_array($actual), "Bidirectional relations result not an array");
        $this->assertEquals(2 * count($expected), count($actual));
        $reverse = [];
        foreach ($expected as $forward) {
            $this->assertTrue(in_array($forward, $actual));
            $reverse = $forward;
            $reverse['principalType'] = $forward['dependentType'];
            $reverse['principalMult'] = $forward['dependentMult'];
            $reverse['principalProp'] = $forward['dependentProp'];
            $reverse['dependentType'] = $forward['principalType'];
            $reverse['dependentMult'] = $forward['principalMult'];
            $reverse['dependentProp'] = $forward['principalProp'];
            $this->assertTrue(in_array($reverse, $actual));
        }
    }
}
