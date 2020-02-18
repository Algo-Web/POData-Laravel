<?php

namespace Tests\Legacy\Unit\AlgoWeb\PODataLaravel\Models;

use AlgoWeb\PODataLaravel\Models\MetadataGubbinsHolder;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationMonomorphic;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubMonomorphic;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubPolymorphic;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubRelationType;
use Tests\Legacy\Facets\AlgoWeb\PODataLaravel\Models\TestMonomorphicChildOfMorphTarget;
use Tests\Legacy\Facets\AlgoWeb\PODataLaravel\Models\TestMonomorphicManySource;
use Tests\Legacy\Facets\AlgoWeb\PODataLaravel\Models\TestMonomorphicManyTarget;
use Tests\Legacy\Facets\AlgoWeb\PODataLaravel\Models\TestMonomorphicParentOfMorphTarget;
use Tests\Legacy\Facets\AlgoWeb\PODataLaravel\Models\TestMonomorphicSource;
use Tests\Legacy\Facets\AlgoWeb\PODataLaravel\Models\TestMonomorphicTarget;
use Tests\Legacy\Facets\AlgoWeb\PODataLaravel\Models\TestMorphManySource;
use Tests\Legacy\Facets\AlgoWeb\PODataLaravel\Models\TestMorphManySourceAlternate;
use Tests\Legacy\Facets\AlgoWeb\PODataLaravel\Models\TestMorphManyToManySource;
use Tests\Legacy\Facets\AlgoWeb\PODataLaravel\Models\TestMorphManyToManyTarget;
use Tests\Legacy\Facets\AlgoWeb\PODataLaravel\Models\TestMorphOneSource;
use Tests\Legacy\Facets\AlgoWeb\PODataLaravel\Models\TestMorphTarget;
use Tests\Legacy\AlgoWeb\PODataLaravel\Models\TestCase;

class MetadataGubbinsHolderTest extends TestCase
{
    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \POData\Common\InvalidOperationException
     * @throws \ReflectionException
     */
    public function testAddSameModelTwice()
    {
        $metaRaw['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];

        $model = new TestMonomorphicSource($metaRaw);
        $gubbins = $model->extractGubbins();

        $foo = new MetadataGubbinsHolder();
        $foo->addEntity($gubbins);

        $expected = \Tests\Legacy\Facets\AlgoWeb\PODataLaravel\Models\TestMonomorphicSource::class .' already added';
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

        $expected = \Tests\Legacy\Facets\AlgoWeb\PODataLaravel\Models\TestMonomorphicSource::class . ' does not exist in holder';
        $actual = null;

        try {
            $foo->getRelationsByRelationName(TestMonomorphicSource::class, 'foo');
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \POData\Common\InvalidOperationException
     * @throws \ReflectionException
     */
    public function testGetRelationsOnNonExistentRelation()
    {
        $metaRaw['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];

        $model = new TestMonomorphicSource($metaRaw);
        $gubbins = $model->extractGubbins();

        $foo = new MetadataGubbinsHolder();
        $foo->addEntity($gubbins);

        $expected = 'Relation foo not registered on '. \Tests\Legacy\Facets\AlgoWeb\PODataLaravel\Models\TestMonomorphicSource::class;
        $actual = null;

        try {
            $foo->getRelationsByRelationName(TestMonomorphicSource::class, 'foo');
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \POData\Common\InvalidOperationException
     * @throws \ReflectionException
     */
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

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \POData\Common\InvalidOperationException
     * @throws \ReflectionException
     */
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

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \POData\Common\InvalidOperationException
     * @throws \ReflectionException
     */
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

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \POData\Common\InvalidOperationException
     * @throws \ReflectionException
     */
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

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \POData\Common\InvalidOperationException
     * @throws \ReflectionException
     */
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

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \POData\Common\InvalidOperationException
     * @throws \ReflectionException
     */
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

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \POData\Common\InvalidOperationException
     * @throws \ReflectionException
     */
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

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \POData\Common\InvalidOperationException
     * @throws \ReflectionException
     */
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

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \POData\Common\InvalidOperationException
     * @throws \ReflectionException
     */
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

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \POData\Common\InvalidOperationException
     * @throws \ReflectionException
     */
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

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \POData\Common\InvalidOperationException
     * @throws \ReflectionException
     */
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

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \POData\Common\InvalidOperationException
     * @throws \ReflectionException
     */
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
        $this->assertTrue($result[0]->getLast() instanceof AssociationStubPolymorphic, get_class($result[0]->getLast()));
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \POData\Common\InvalidOperationException
     * @throws \ReflectionException
     */
    public function testGetGubbinsIncludingHasManyThroughRelation()
    {
        $metaRaw['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['alternate_id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];

        $model = new TestMonomorphicParentOfMorphTarget($metaRaw);

        $gubbins = $model->extractGubbins();
        $stubs = $gubbins->getStubs();
        $this->assertTrue(array_key_exists('monomorphicChildren', $stubs));
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \POData\Common\InvalidOperationException
     * @throws \ReflectionException
     */
    public function testGetBidirectionalHasManyThroughRelation()
    {
        $metaRaw['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['alternate_id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];

        $model = new TestMonomorphicParentOfMorphTarget($metaRaw);
        $nuModel = new TestMonomorphicChildOfMorphTarget($metaRaw);

        $modelGubbins = $model->extractGubbins();
        $nuModelGubbins = $nuModel->extractGubbins();

        $left = $modelGubbins->getStubs()['monomorphicChildren'];
        $right = $nuModelGubbins->getStubs()['monomorphicParent'];
        $this->assertTrue($left->isCompatible($right));

        $foo = new MetadataGubbinsHolder();
        $foo->addEntity($model->extractGubbins());
        $foo->addEntity($nuModel->extractGubbins());

        $result = $foo->getRelationsByRelationName(TestMonomorphicParentOfMorphTarget::class, 'monomorphicChildren');
        $this->assertTrue(0 < count($result));
    }
}
