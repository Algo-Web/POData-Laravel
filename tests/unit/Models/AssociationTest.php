<?php

namespace AlgoWeb\PODataLaravel\Models;

use Mockery as m;

class AssociationTest extends TestCase
{
    public function testNotOkNewCreation()
    {
        $foo = new Association();
        $this->assertFalse($foo->isOk());
    }

    public function testNotOkFirstBad()
    {
        $one = m::mock(AssociationStubBase::class);
        $one->shouldReceive('isOk')->andReturn(false);

        $foo = new Association();
        $foo->setFirst($one);
        $this->assertFalse($foo->isOk());
    }

    public function testNotOkSecondEmpty()
    {
        $one = m::mock(AssociationStubBase::class);
        $one->shouldReceive('isOk')->andReturn(true);

        $foo = new Association();
        $foo->setFirst($one);
        $this->assertFalse($foo->isOk());
    }

    public function testNotOkSecondBad()
    {
        $one = m::mock(AssociationStubBase::class);
        $one->shouldReceive('isOk')->andReturn(true);
        $two = m::mock(AssociationStubBase::class);
        $two->shouldReceive('isOk')->andReturn(false);

        $foo = new Association();
        $foo->setFirst($one);
        $foo->setLast($two);
        $this->assertFalse($foo->isOk());
    }

    public function testNotOKIsNotCompatible()
    {
        $one = m::mock(AssociationStubBase::class);
        $one->shouldReceive('isOk')->andReturn(true);
        $one->shouldReceive('isCompatible')->andReturn(false);
        $two = m::mock(AssociationStubBase::class);
        $two->shouldReceive('isOk')->andReturn(true);

        $foo = new Association();
        $foo->setFirst($one);
        $foo->setLast($two);
        $this->assertFalse($foo->isOk());
    }

    public function testNotOKWrongOrder()
    {
        $one = m::mock(AssociationStubBase::class);
        $one->shouldReceive('isOk')->andReturn(true);
        $one->shouldReceive('isCompatible')->andReturn(true);
        $one->shouldReceive('compare')->andReturn(42);
        $two = m::mock(AssociationStubBase::class);
        $two->shouldReceive('isOk')->andReturn(true);

        $foo = new Association();
        $foo->setFirst($one);
        $foo->setLast($two);
        $this->assertFalse($foo->isOk());
    }

    public function testIsOk()
    {
        $one = m::mock(AssociationStubBase::class);
        $one->shouldReceive('isOk')->andReturn(true);
        $one->shouldReceive('isCompatible')->andReturn(true);
        $one->shouldReceive('compare')->andReturn(-1);
        $two = m::mock(AssociationStubBase::class);
        $two->shouldReceive('isOk')->andReturn(true);

        $foo = new Association();
        $foo->setFirst($one);
        $foo->setLast($two);
        $this->assertTrue($foo->isOk());
    }

    public function testGetAssociationTwoRelsOnSameModelPair()
    {
        $metaRaw['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];

        $model = new TestMonomorphicSource($metaRaw);
        $nuModel = new TestMonomorphicTarget($metaRaw);

        $foo = new MetadataGubbinsHolder();
        $foo->addEntity($model->extractGubbins());
        $foo->addEntity($nuModel->extractGubbins());

        $srcStubs = [
            $foo->getRelationsByRelationName(TestMonomorphicSource::class, 'oneSource'),
            $foo->getRelationsByRelationName(TestMonomorphicSource::class, 'manySource')
        ];
        $dstStubs = [
            $foo->getRelationsByRelationName(TestMonomorphicTarget::class, 'oneTarget'),
            $foo->getRelationsByRelationName(TestMonomorphicTarget::class, 'manyTarget')
        ];

        $assoc1 = new Association();
        $assoc1->setLast($srcStubs[0][0]);
        $assoc1->setFirst($dstStubs[0][0]);
        $assoc2 = new Association();
        $assoc2->setLast($srcStubs[1][0]);
        $assoc2->setFirst($dstStubs[1][0]);

        $result = $foo->getRelationsByClass(TestMonomorphicSource::class);
        $this->assertEquals(2, count($result));
        $this->assertTrue(in_array($assoc1, $result));
        $this->assertTrue(in_array($assoc2, $result));
    }

    public function testGetAssociationsWithOnlyOneModelHookedUp()
    {
        $metaRaw['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];

        $model = new TestMonomorphicSource($metaRaw);

        $foo = new MetadataGubbinsHolder();
        $foo->addEntity($model->extractGubbins());

        $result = $foo->getRelationsByClass(TestMonomorphicSource::class);
        $this->assertEquals(0, count($result));
    }

    public function testGetAssociationsWithTwoRelatedModels()
    {
        $metaRaw['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];

        $model = new TestMonomorphicSource($metaRaw);
        $nuModel = new TestMonomorphicTarget($metaRaw);

        $foo = new MetadataGubbinsHolder();
        $foo->addEntity($model->extractGubbins());
        $foo->addEntity($nuModel->extractGubbins());

        $srcStubs = [
            $foo->getRelationsByRelationName(TestMonomorphicSource::class, 'oneSource'),
            $foo->getRelationsByRelationName(TestMonomorphicSource::class, 'manySource')
        ];
        $dstStubs = [
            $foo->getRelationsByRelationName(TestMonomorphicTarget::class, 'oneTarget'),
            $foo->getRelationsByRelationName(TestMonomorphicTarget::class, 'manyTarget')
        ];

        $assoc1 = new Association();
        $assoc1->setLast($srcStubs[0][0]);
        $assoc1->setFirst($dstStubs[0][0]);
        $assoc2 = new Association();
        $assoc2->setLast($srcStubs[1][0]);
        $assoc2->setFirst($dstStubs[1][0]);

        $result = $foo->getRelations();
        $this->assertEquals(2, count($result));
        $this->assertTrue(in_array($assoc1, $result));
        $this->assertTrue(in_array($assoc2, $result));
    }
}
