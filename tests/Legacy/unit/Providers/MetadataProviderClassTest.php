<?php

namespace Tests\Legacy\Unit\AlgoWeb\PODataLaravel\Providers;

use Mockery as m;
use Tests\Legacy\AlgoWeb\PODataLaravel\Models\TestCase as TestCase;
use Tests\Legacy\Facets\AlgoWeb\PODataLaravel\Models\MetadataProviderDummy;
use Tests\Legacy\Facets\AlgoWeb\PODataLaravel\Models\TestModelTrait;

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
