<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 27/02/20
 * Time: 11:41 AM
 */

namespace AlgoWeb\PODataLaravel\Orchestra\Tests\Unit\Models;

use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubFactory;
use AlgoWeb\PODataLaravel\Orchestra\Tests\Models\OrchestraMorphManyTestModel;
use AlgoWeb\PODataLaravel\Orchestra\Tests\Models\OrchestraMorphOneTestModel;
use AlgoWeb\PODataLaravel\Orchestra\Tests\Models\OrchestraMorphToTestModel;
use AlgoWeb\PODataLaravel\Orchestra\Tests\Models\OrchestraPolymorphToManySourceModel;
use AlgoWeb\PODataLaravel\Orchestra\Tests\Models\OrchestraPolymorphToManyTestModel;
use AlgoWeb\PODataLaravel\Orchestra\Tests\TestCase;

class AssociationStubFactoryTest extends TestCase
{
    public function testMorphToStub()
    {
        $model = new OrchestraMorphToTestModel();

        $result = AssociationStubFactory::associationStubFromRelation($model, 'parent');

        $fieldChain = ['morph_id', 'morph_type', null];

        $this->assertEquals(OrchestraMorphToTestModel::class, $result->getBaseType());
        $this->assertEquals(null, $result->getForeignField());
        $this->assertEquals(null, $result->getTargType());
        $this->assertEquals('parent', $result->getRelationName());
        $this->assertEquals($fieldChain, $result->getThroughFieldChain());
    }

    public function testMorphToManyStub()
    {
        $model = new OrchestraPolymorphToManySourceModel();

        $result = AssociationStubFactory::associationStubFromRelation($model, 'sourceChildren');

        $fieldChain = ['id', 'manyable_id', 'manyable_type', 'many_id', 'id'];

        $this->assertEquals(OrchestraPolymorphToManySourceModel::class, $result->getBaseType());
        $this->assertEquals('manyable_id', $result->getForeignField());
        $this->assertEquals(OrchestraPolymorphToManyTestModel::class, $result->getTargType());
        $this->assertEquals('sourceChildren', $result->getRelationName());
        $this->assertEquals($fieldChain, $result->getThroughFieldChain());
        $this->assertEquals('manyable_type', $result->getMorphType());
    }

    public function testMorphOneStub()
    {
        $model = new OrchestraMorphOneTestModel();

        $result = AssociationStubFactory::associationStubFromRelation($model, 'child');

        $fieldChain = ['morph_id', 'morph_type', 'id'];

        $this->assertEquals(OrchestraMorphOneTestModel::class, $result->getBaseType());
        $this->assertEquals('id', $result->getForeignField());
        $this->assertEquals(OrchestraMorphToTestModel::class, $result->getTargType());
        $this->assertEquals('child', $result->getRelationName());
        $this->assertEquals($fieldChain, $result->getThroughFieldChain());
        $this->assertEquals('morph_type', $result->getMorphType());
    }

    public function testMorphManyStub()
    {
        $model = new OrchestraMorphManyTestModel();

        $result = AssociationStubFactory::associationStubFromRelation($model, 'child');

        $fieldChain = ['morph_id', 'morph_type', 'id'];

        $this->assertEquals(OrchestraMorphManyTestModel::class, $result->getBaseType());
        $this->assertEquals('id', $result->getForeignField());
        $this->assertEquals(OrchestraMorphToTestModel::class, $result->getTargType());
        $this->assertEquals('child', $result->getRelationName());
        $this->assertEquals($fieldChain, $result->getThroughFieldChain());
        $this->assertEquals('morph_type', $result->getMorphType());
    }
}
