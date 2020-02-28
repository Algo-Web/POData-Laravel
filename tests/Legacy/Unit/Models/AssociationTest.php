<?php declare(strict_types=1);

namespace Tests\Legacy\AlgoWeb\PODataLaravel\Unit\Models;

use AlgoWeb\PODataLaravel\Models\MetadataGubbinsHolder;
use AlgoWeb\PODataLaravel\Models\MetadataRelationshipContainer;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationMonomorphic;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubBase;
use Mockery as m;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMonomorphicSource;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMonomorphicTarget;
use Tests\Legacy\AlgoWeb\PODataLaravel\TestCase;

class AssociationTest extends TestCase
{
    public function testNotOkNewCreation()
    {
        $foo = new AssociationMonomorphic();
        $this->assertFalse($foo->isOk());
    }

    public function testNotOkFirstBad()
    {
        $one = m::mock(AssociationStubBase::class);
        $one->shouldReceive('isOk')->andReturn(false);
        $one->shouldReceive('addAssociation');

        $foo = new AssociationMonomorphic();
        $foo->setFirst($one);
        $this->assertFalse($foo->isOk());
    }

    public function testNotOkSecondEmpty()
    {
        $one = m::mock(AssociationStubBase::class);
        $one->shouldReceive('isOk')->andReturn(true);
        $one->shouldReceive('addAssociation');

        $foo = new AssociationMonomorphic();
        $foo->setFirst($one);
        $this->assertFalse($foo->isOk());
    }

    public function testNotOkSecondBad()
    {
        $one = m::mock(AssociationStubBase::class);
        $one->shouldReceive('isOk')->andReturn(true);
        $one->shouldReceive('addAssociation');

        $two = m::mock(AssociationStubBase::class);
        $two->shouldReceive('isOk')->andReturn(false);
        $two->shouldReceive('addAssociation');

        $foo = new AssociationMonomorphic();
        $foo->setFirst($one);
        $foo->setLast($two);
        $this->assertFalse($foo->isOk());
    }

    public function testNotOKIsNotCompatible()
    {
        $one = m::mock(AssociationStubBase::class);
        $one->shouldReceive('isOk')->andReturn(true);
        $one->shouldReceive('isCompatible')->andReturn(false);
        $one->shouldReceive('addAssociation');

        $two = m::mock(AssociationStubBase::class);
        $two->shouldReceive('isOk')->andReturn(true);
        $two->shouldReceive('addAssociation');

        $foo = new AssociationMonomorphic();
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
        $one->shouldReceive('addAssociation');

        $two = m::mock(AssociationStubBase::class);
        $two->shouldReceive('isOk')->andReturn(true);
        $two->shouldReceive('addAssociation');

        $foo = new AssociationMonomorphic();
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
        $one->shouldReceive('addAssociation');

        $two = m::mock(AssociationStubBase::class);
        $two->shouldReceive('isOk')->andReturn(true);
        $two->shouldReceive('addAssociation');

        $foo = new AssociationMonomorphic();
        $foo->setFirst($one);
        $foo->setLast($two);
        $this->assertTrue($foo->isOk());
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \POData\Common\InvalidOperationException
     * @throws \ReflectionException
     */
    public function testGetAssociationTwoRelsOnSameModelPair()
    {
        $metaRaw['id']   = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metaRaw['many_source'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metaRaw['one_source'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metaRaw['many_id'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metaRaw['one_id'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];

        $model   = new TestMonomorphicSource($metaRaw);
        $nuModel = new TestMonomorphicTarget($metaRaw);

        $foo = new MetadataRelationshipContainer();
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

        $assoc1 = new AssociationMonomorphic();
        $assoc1->setLast(clone $srcStubs[0][0]);
        $assoc1->setFirst(clone $dstStubs[0][0]);
        $assoc2 = new AssociationMonomorphic();
        $assoc2->setLast(clone $srcStubs[1][0]);
        $assoc2->setFirst(clone $dstStubs[1][0]);

        $result = $foo->getRelationsByClass(TestMonomorphicSource::class);
        $this->assertEquals(2, count($result));
        $this->assertEquals($srcStubs[1][0], $result[0]->getLast());
        $this->assertEquals($dstStubs[1][0], $result[0]->getFirst());
        $this->assertEquals($srcStubs[0][0], $result[1]->getLast());
        $this->assertEquals($dstStubs[0][0], $result[1]->getFirst());
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \POData\Common\InvalidOperationException
     * @throws \ReflectionException
     */
    public function testGetAssociationsWithOnlyOneModelHookedUp()
    {
        $metaRaw['id']   = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];

        $model = new TestMonomorphicSource($metaRaw);

        $foo = new MetadataRelationshipContainer();
        $foo->addEntity($model->extractGubbins());

        $result = $foo->getRelationsByClass(TestMonomorphicSource::class);
        $this->assertEquals(0, count($result));
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \POData\Common\InvalidOperationException
     * @throws \ReflectionException
     */
    public function testGetAssociationsWithTwoRelatedModels()
    {
        $metaRaw['id']   = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metaRaw['many_source'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metaRaw['one_source'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metaRaw['many_id'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metaRaw['one_id'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];

        $model   = new TestMonomorphicSource($metaRaw);
        $nuModel = new TestMonomorphicTarget($metaRaw);
        $foo = new MetadataRelationshipContainer();
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

        $assoc1 = new AssociationMonomorphic();
        $assoc1->setLast($srcStubs[0][0]);
        $assoc1->setFirst($dstStubs[0][0]);
        $assoc2 = new AssociationMonomorphic();
        $assoc2->setLast($srcStubs[1][0]);
        $assoc2->setFirst($dstStubs[1][0]);
        $result = $foo->getRelations();

        $this->assertEquals(2, count($result));
        $this->assertTrue(in_array($assoc1, $result));
        $this->assertTrue(in_array($assoc2, $result));
    }
}
