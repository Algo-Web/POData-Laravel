<?php

namespace Tests\AlgoWeb\PODataLaravel\Providers;

use AlgoWeb\ODataMetadata\MetadataManager;
use AlgoWeb\ODataMetadata\MetadataV3\edm\TEntityTypeType;
use AlgoWeb\PODataLaravel\Models\MetadataGubbinsHolder;
use Tests\AlgoWeb\PODataLaravel\Models\MetadataProviderDummy;
use Tests\AlgoWeb\PODataLaravel\Models\TestCase as TestCase;
use Tests\AlgoWeb\PODataLaravel\Models\TestCastModel;
use Tests\AlgoWeb\PODataLaravel\Models\TestGetterModel;
use Tests\AlgoWeb\PODataLaravel\Models\TestModel;
use Tests\AlgoWeb\PODataLaravel\Models\TestModelAbstract;
use Tests\AlgoWeb\PODataLaravel\Models\TestModelTrait;
use Tests\AlgoWeb\PODataLaravel\Models\TestMonomorphicChildOfMorphTarget;
use Tests\AlgoWeb\PODataLaravel\Models\TestMonomorphicManySource;
use Tests\AlgoWeb\PODataLaravel\Models\TestMonomorphicManyTarget;
use Tests\AlgoWeb\PODataLaravel\Models\TestMonomorphicOneAndManySource;
use Tests\AlgoWeb\PODataLaravel\Models\TestMonomorphicOneAndManyTarget;
use Tests\AlgoWeb\PODataLaravel\Models\TestMonomorphicParentOfMorphTarget;
use Tests\AlgoWeb\PODataLaravel\Models\TestMonomorphicSource;
use Tests\AlgoWeb\PODataLaravel\Models\TestMonomorphicTarget;
use Tests\AlgoWeb\PODataLaravel\Models\TestMorphManySource;
use Tests\AlgoWeb\PODataLaravel\Models\TestMorphManySourceAlternate;
use Tests\AlgoWeb\PODataLaravel\Models\TestMorphManySourceWithUnexposedTarget;
use Tests\AlgoWeb\PODataLaravel\Models\TestMorphManyToManySource;
use Tests\AlgoWeb\PODataLaravel\Models\TestMorphManyToManyTarget;
use Tests\AlgoWeb\PODataLaravel\Models\TestMorphOneSource;
use Tests\AlgoWeb\PODataLaravel\Models\TestMorphOneSourceAlternate;
use Tests\AlgoWeb\PODataLaravel\Models\TestMorphTarget;
use Tests\AlgoWeb\PODataLaravel\Models\TestMorphTargetAlternate;
use Tests\AlgoWeb\PODataLaravel\Models\TestMorphTargetChild;
use Tests\AlgoWeb\PODataLaravel\Models\TestPolymorphicDualSource;
use Illuminate\Cache\ArrayStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery as m;
use POData\Providers\Metadata\ResourceEntityType;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\ResourceTypeKind;
use POData\Providers\Metadata\SimpleMetadataProvider;
use POData\Providers\Metadata\Type\StringType;

/**
 * Generated Test Class.
 */
class MetadataProviderClassTest extends TestCase
{
    public function testNonModelsExcluded()
    {
        // make sure that a trait that uses MetadataTrait (eg, extends it) doesn't get loaded on its own
        // as PHP gets rather annoyed when you try to do so
        $foo = m::mock(MetadataProviderDummy::class)->makePartial();

        $result = $foo->getCandidateModels();
        $traitClass = TestModelTrait::class;
        $this->assertFalse(in_array($traitClass, $result));
    }
}
