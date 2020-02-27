<?php declare(strict_types=1);

namespace Tests\Legacy\AlgoWeb\PODataLaravel\Unit\Providers;

use Mockery as m;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\MetadataProviderDummy;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestModelTrait;
use Tests\Legacy\AlgoWeb\PODataLaravel\TestCase as TestCase;

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

        $result     = $foo->getCandidateModels();
        $traitClass = TestModelTrait::class;
        $this->assertFalse(in_array($traitClass, $result));
    }
}
