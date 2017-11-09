<?php

namespace AlgoWeb\PODataLaravel\Models;

use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationPolymorphic;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubMonomorphic;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubPolymorphic;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubRelationType;

class MetadataGubbinsHolderTest extends TestCase
{
    public function testAddSameModelTwice()
    {
        $metaRaw['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];

        $model = new TestMonomorphicSource($metaRaw);
        $gubbins = $model->extractGubbins();

        $foo = new MetadataGubbinsHolder();
        $foo->addEntity($gubbins);

        $expected = 'AlgoWeb\PODataLaravel\Models\TestMonomorphicSource already added';
        $actual = null;

        try {
            $foo->addEntity($gubbins);
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetRelationsOnNonExistentClass()
    {
        $foo = new MetadataGubbinsHolder();

        $expected = 'AlgoWeb\PODataLaravel\Models\TestMonomorphicSource does not exist in holder';
        $actual = null;

        try {
            $foo->getRelationsByRelationName(TestMonomorphicSource::class, 'foo');
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetRelationsOnNonExistentRelation()
    {
        $metaRaw['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];

        $model = new TestMonomorphicSource($metaRaw);
        $gubbins = $model->extractGubbins();

        $foo = new MetadataGubbinsHolder();
        $foo->addEntity($gubbins);

        $expected = 'Relation foo not registered on AlgoWeb\PODataLaravel\Models\TestMonomorphicSource';
        $actual = null;

        try {
            $foo->getRelationsByRelationName(TestMonomorphicSource::class, 'foo');
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetRelationsByRelNameHasOne()
    {
        $metaRaw['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];

        $model = new TestMonomorphicSource($metaRaw);
        $nuModel = new TestMonomorphicTarget($metaRaw);

        $foo = new MetadataGubbinsHolder();
        $foo->addEntity($model->extractGubbins());
        $foo->addEntity($nuModel->extractGubbins());

        $expected = new AssociationStubMonomorphic();
        $expected->setRelationName('oneTarget');
        $expected->setForeignField('one_id');
        $expected->setKeyField('one_source');
        $expected->setBaseType(TestMonomorphicTarget::class);
        $expected->setTargType(TestMonomorphicSource::class);
        $expected->setMultiplicity(AssociationStubRelationType::ONE());

        $result = $foo->getRelationsByRelationName(TestMonomorphicSource::class, 'oneSource');
        $this->assertEquals(1, count($result));
        $this->assertEquals($expected, $result[0]);
    }

    public function testGetRelationsByRelNameBelongsToOne()
    {
        $metaRaw['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];

        $model = new TestMonomorphicSource($metaRaw);
        $nuModel = new TestMonomorphicTarget($metaRaw);

        $foo = new MetadataGubbinsHolder();
        $foo->addEntity($model->extractGubbins());
        $foo->addEntity($nuModel->extractGubbins());

        $expected = new AssociationStubMonomorphic();
        $expected->setRelationName('oneSource');
        $expected->setForeignField('one_source');
        $expected->setKeyField('one_id');
        $expected->setBaseType(TestMonomorphicSource::class);
        $expected->setTargType(TestMonomorphicTarget::class);
        $expected->setMultiplicity(AssociationStubRelationType::NULL_ONE());

        $result = $foo->getRelationsByRelationName(TestMonomorphicTarget::class, 'oneTarget');
        $this->assertEquals(1, count($result));
        $this->assertEquals($expected, $result[0]);
    }

    public function testGetRelationsByRelNameHasMany()
    {
        $metaRaw['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];

        $model = new TestMonomorphicSource($metaRaw);
        $nuModel = new TestMonomorphicTarget($metaRaw);

        $foo = new MetadataGubbinsHolder();
        $foo->addEntity($model->extractGubbins());
        $foo->addEntity($nuModel->extractGubbins());

        $expected = new AssociationStubMonomorphic();
        $expected->setRelationName('manyTarget');
        $expected->setForeignField('many_id');
        $expected->setKeyField('many_source');
        $expected->setBaseType(TestMonomorphicTarget::class);
        $expected->setTargType(TestMonomorphicSource::class);
        $expected->setMultiplicity(AssociationStubRelationType::ONE());

        $result = $foo->getRelationsByRelationName(TestMonomorphicSource::class, 'manySource');
        $this->assertEquals(1, count($result));
        $this->assertEquals($expected, $result[0]);
    }

    public function testGetRelationsByRelNameBelongsToMany()
    {
        $metaRaw['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];

        $model = new TestMonomorphicSource($metaRaw);
        $nuModel = new TestMonomorphicTarget($metaRaw);

        $foo = new MetadataGubbinsHolder();
        $foo->addEntity($model->extractGubbins());
        $foo->addEntity($nuModel->extractGubbins());

        $expected = new AssociationStubMonomorphic();
        $expected->setRelationName('manySource');
        $expected->setForeignField('many_source');
        $expected->setKeyField('many_id');
        $expected->setBaseType(TestMonomorphicSource::class);
        $expected->setTargType(TestMonomorphicTarget::class);
        $expected->setMultiplicity(AssociationStubRelationType::MANY());

        $result = $foo->getRelationsByRelationName(TestMonomorphicTarget::class, 'manyTarget');
        $this->assertEquals(1, count($result));
        $this->assertEquals($expected, $result[0]);
    }

    public function testGetRelationsByRelNameBelongsToManyToMany()
    {
        $metaRaw['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];

        $model = new TestMonomorphicManySource($metaRaw);
        $nuModel = new TestMonomorphicManyTarget($metaRaw);

        $foo = new MetadataGubbinsHolder();
        $foo->addEntity($model->extractGubbins());
        $foo->addEntity($nuModel->extractGubbins());

        $expected = new AssociationStubMonomorphic();
        $expected->setRelationName('manyTarget');
        $expected->setForeignField('many_id');
        $expected->setKeyField('many_source');
        $expected->setBaseType(TestMonomorphicManyTarget::class);
        $expected->setTargType(TestMonomorphicManySource::class);
        $expected->setMultiplicity(AssociationStubRelationType::MANY());

        $result = $foo->getRelationsByRelationName(TestMonomorphicManySource::class, 'manySource');
        $this->assertEquals(1, count($result));
        $this->assertEquals($expected, $result[0]);
    }

    public function testGetRelationsByRelNameBelongsToManyToManyReverse()
    {
        $metaRaw['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];

        $model = new TestMonomorphicManySource($metaRaw);
        $nuModel = new TestMonomorphicManyTarget($metaRaw);

        $foo = new MetadataGubbinsHolder();
        $foo->addEntity($model->extractGubbins());
        $foo->addEntity($nuModel->extractGubbins());

        $expected = new AssociationStubMonomorphic();
        $expected->setRelationName('manySource');
        $expected->setForeignField('many_source');
        $expected->setKeyField('many_id');
        $expected->setBaseType(TestMonomorphicManySource::class);
        $expected->setTargType(TestMonomorphicManyTarget::class);
        $expected->setMultiplicity(AssociationStubRelationType::MANY());

        $result = $foo->getRelationsByRelationName(TestMonomorphicManyTarget::class, 'manyTarget');
        $this->assertEquals(1, count($result));
        $this->assertEquals($expected, $result[0]);
    }

    public function testGetRelationsByRelNameBelongsToIsKnownSide()
    {
        $metaRaw['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];

        $model = new TestMorphTarget($metaRaw);
        $nuModel = new TestMorphOneSource($metaRaw);

        $foo = new MetadataGubbinsHolder();
        $foo->addEntity($model->extractGubbins());
        $foo->addEntity($nuModel->extractGubbins());

        $result = $foo->getRelationsByRelationName(TestMorphTarget::class, 'morph');
        $this->assertEquals(0, count($result));
    }

    public function testGetRelationsByRelNameMorphOne()
    {
        $metaRaw['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];

        $model = new TestMorphTarget($metaRaw);
        $nuModel = new TestMorphOneSource($metaRaw);

        $foo = new MetadataGubbinsHolder();
        $foo->addEntity($model->extractGubbins());
        $foo->addEntity($nuModel->extractGubbins());

        $expected = new AssociationStubPolymorphic();
        $expected->setRelationName('morph');
        $expected->setForeignField(null);
        $expected->setKeyField('id');
        $expected->setBaseType(TestMorphTarget::class);
        $expected->setTargType(null);
        $expected->setMultiplicity(AssociationStubRelationType::ONE());

        $result = $foo->getRelationsByRelationName(TestMorphOneSource::class, 'morphTarget');
        $this->assertEquals(1, count($result));
        $this->assertEquals($expected, $result[0]);
    }

    public function testGetRelationsByRelNameMorphMany()
    {
        $metaRaw['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];

        $model = new TestMorphTarget($metaRaw);
        $nuModel = new TestMorphManySource($metaRaw);

        $foo = new MetadataGubbinsHolder();
        $foo->addEntity($model->extractGubbins());
        $foo->addEntity($nuModel->extractGubbins());

        $expected = new AssociationStubPolymorphic();
        $expected->setRelationName('morph');
        $expected->setForeignField(null);
        $expected->setKeyField('id');
        $expected->setBaseType(TestMorphTarget::class);
        $expected->setTargType(null);
        $expected->setMultiplicity(AssociationStubRelationType::ONE());

        $result = $foo->getRelationsByRelationName(TestMorphManySource::class, 'morphTarget');
        $this->assertEquals(1, count($result));
        $this->assertEquals($expected, $result[0]);
    }

    public function testGetRelationsByRelNameMorphToMany()
    {
        $metaRaw['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];

        $model = new TestMorphManyToManySource($metaRaw);
        $nuModel = new TestMorphManyToManyTarget($metaRaw);

        $foo = new MetadataGubbinsHolder();
        $foo->addEntity($model->extractGubbins());
        $foo->addEntity($nuModel->extractGubbins());

        $expected = new AssociationStubPolymorphic();
        $expected->setRelationName('manyTarget');
        $expected->setForeignField(null);
        $expected->setKeyField('source_id');
        $expected->setBaseType(TestMorphManyToManyTarget::class);
        $expected->setTargType(null);
        $expected->setMultiplicity(AssociationStubRelationType::MANY());

        $result = $foo->getRelationsByRelationName(TestMorphManyToManySource::class, 'manySource');
        $this->assertEquals(1, count($result));
        $this->assertEquals($expected, $result[0]);
    }

    public function testGetRelationsByRelNameMorphedByMany()
    {
        $metaRaw['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];

        $model = new TestMorphManyToManySource($metaRaw);
        $nuModel = new TestMorphManyToManyTarget($metaRaw);

        $foo = new MetadataGubbinsHolder();
        $foo->addEntity($model->extractGubbins());
        $foo->addEntity($nuModel->extractGubbins());

        $result = $foo->getRelationsByRelationName(TestMorphManyToManyTarget::class, 'manyTarget');
        $this->assertEquals(0, count($result));
    }

    public function testGetRelationsTwoArmedPolymorphicRelation()
    {
        $metaRaw['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['alternate_id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];

        $model = new TestMorphTarget($metaRaw);
        $nuModel = new TestMorphManySource($metaRaw);
        $altModel = new TestMorphManySourceAlternate($metaRaw);

        $foo = new MetadataGubbinsHolder();
        $foo->addEntity($model->extractGubbins());
        $foo->addEntity($nuModel->extractGubbins());
        $foo->addEntity($altModel->extractGubbins());

        $result = $foo->getRelations();
        $this->assertEquals(2, count($result));
        $this->assertTrue($result[0] instanceof AssociationMonomorphic, get_class($result[0]));
        $this->assertEquals(1, count($result[0]->getLast()));
    }
}
