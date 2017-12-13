<?php

namespace AlgoWeb\PODataLaravel\Models;

use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubMonomorphic;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubPolymorphic;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubRelationType;
use Mockery as m;

class AssociationStubFromGubbinsTest extends TestCase
{
    public function testStubsPolymorphicManyToMany()
    {
        $metaRaw['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];

        $model = new TestMorphManyToManySource($metaRaw);
        $nuModel = new TestMorphManyToManyTarget($metaRaw);

        $gubbins = $model->extractGubbins();
        $nuGubbins = $nuModel->extractGubbins();

        $manySource = $gubbins->getStubs()['manySource'];
        $manyPivot = $gubbins->getStubs()['manySourcePivot'];
        $targSource = $nuGubbins->getStubs()['manyTarget'];
        $targPivot = $nuGubbins->getStubs()['manyTargetPivot'];
        $stubs = [$manySource, $manyPivot, $targSource, $targPivot];

        // first, check that stubs that should be compatible, are compatible
        $this->assertTrue($manySource->isCompatible($targSource));
        $this->assertTrue($manyPivot->isCompatible($targPivot));
        $this->assertTrue($targSource->isCompatible($manySource));
        $this->assertTrue($targPivot->isCompatible($manyPivot));

        // second, check that stubs that should not be compatible, are not compatible
        $this->assertFalse($manySource->isCompatible($targPivot));
        $this->assertFalse($manyPivot->isCompatible($targSource));
        $this->assertFalse($targSource->isCompatible($manyPivot));
        $this->assertFalse($targPivot->isCompatible($manySource));
        foreach ($stubs as $stub) {
            $this->assertTrue($stub instanceof AssociationStubPolymorphic);
            $this->assertFalse($stub->isCompatible($stub));
        }
    }

    public function testStubsPolymorphicTwoArmedOneToMany()
    {
        $metaRaw['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['alternate_id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];

        $model = new TestMorphTarget($metaRaw);
        $nuModel = new TestMorphManySource($metaRaw);
        $altModel = new TestMorphManySourceAlternate($metaRaw);

        $gubbins = $model->extractGubbins();
        $nuGubbins = $nuModel->extractGubbins();
        $altGubbins = $altModel->extractGubbins();

        $nuStub = $nuGubbins->getStubs()['morphTarget'];
        $altStub = $altGubbins->getStubs()['morphTarget'];
        $this->assertFalse($nuStub->isCompatible($altStub));
        $stubs = $gubbins->getStubs();
        $targ = 'morph';
        foreach ($stubs as $rel => $stub) {
            if ($targ == $rel) {
                $this->assertTrue($stub->isCompatible($nuStub));
                $this->assertTrue($stub->isCompatible($altStub));
            } else {
                $this->assertFalse($stub->isCompatible($nuStub));
                $this->assertFalse($stub->isCompatible($altStub));
            }
        }
    }
}
