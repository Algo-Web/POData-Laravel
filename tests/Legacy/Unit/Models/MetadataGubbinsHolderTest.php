<?php declare(strict_types=1);

namespace Tests\Legacy\AlgoWeb\PODataLaravel\Unit\Models;

use AlgoWeb\PODataLaravel\Models\MetadataGubbinsHolder;
use AlgoWeb\PODataLaravel\Models\MetadataRelationshipContainer;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationMonomorphic;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubMonomorphic;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubPolymorphic;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubRelationType;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMonomorphicChildOfMorphTarget;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMonomorphicManySource;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMonomorphicManyTarget;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMonomorphicParentOfMorphTarget;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMonomorphicSource;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMonomorphicTarget;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMorphManySource;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMorphManySourceAlternate;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMorphManyToManySource;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMorphManyToManyTarget;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMorphOneSource;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMorphTarget;
use Tests\Legacy\AlgoWeb\PODataLaravel\TestCase;

class MetadataGubbinsHolderTest extends TestCase
{
    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \POData\Common\InvalidOperationException
     * @throws \ReflectionException
     */
    public function testAddSameModelTwice()
    {
        $metaRaw['id']   = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];

        $model   = new TestMonomorphicSource($metaRaw);
        $gubbins = $model->extractGubbins();

        $foo = new MetadataRelationshipContainer();
        $foo->addEntity($gubbins);

        $expected = \Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMonomorphicSource::class .' already added';
        $actual   = null;

        try {
            $foo->addEntity($gubbins);
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetRelationsOnNonExistentClass()
    {
        $foo = new MetadataRelationshipContainer();

        $expected = \Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMonomorphicSource::class . ' does not exist in holder';
        $actual   = null;

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
        $metaRaw['id']   = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];

        $model   = new TestMonomorphicSource($metaRaw);
        $gubbins = $model->extractGubbins();

        $foo = new MetadataRelationshipContainer();
        $foo->addEntity($gubbins);

        $expected = 'Relation foo not registered on '. \Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMonomorphicSource::class;
        $actual   = null;

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
        $metaRaw['id']          = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name']        = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metaRaw['many_source'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metaRaw['one_source']  = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metaRaw['many_id']     = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metaRaw['one_id']      = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];

        $model   = new TestMonomorphicSource($metaRaw);
        $nuModel = new TestMonomorphicTarget($metaRaw);

        $foo         = new MetadataRelationshipContainer();
        $modelEntity = $model->extractGubbins();
        $foo->addEntity($modelEntity);
        $nuEntity = $nuModel->extractGubbins();
        $foo->addEntity($nuEntity);

        $expected = new AssociationStubMonomorphic();
        $expected->setRelationName('oneTarget');
        $expected->setForeignFieldName('one_id');
        $expected->setKeyFieldName('one_source');
        $expected->setBaseType(TestMonomorphicTarget::class);
        $expected->setTargType(TestMonomorphicSource::class);
        $expected->setMultiplicity(AssociationStubRelationType::ONE());
        $expected->setThroughFieldChain(['one_source', 'one_id']);
        $expected->setEntity($nuEntity);

        $result = $foo->getRelationsByRelationName(TestMonomorphicSource::class, 'oneSource');
        $this->assertEquals(1, count($result));
        $expected->addAssociation($result[0]->getAssocations()[0]);
        $this->assertEquals($expected, $result[0]);
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \POData\Common\InvalidOperationException
     * @throws \ReflectionException
     */
    public function testGetRelationsByRelNameBelongsToOne()
    {
        $metaRaw['id']          = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name']        = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metaRaw['many_source'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metaRaw['one_source']  = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metaRaw['many_id']     = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metaRaw['one_id']      = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];

        $model   = new TestMonomorphicSource($metaRaw);
        $nuModel = new TestMonomorphicTarget($metaRaw);

        $foo         = new MetadataRelationshipContainer();
        $modelEntity = $model->extractGubbins();
        $foo->addEntity($modelEntity);
        $nuEntity = $nuModel->extractGubbins();
        $foo->addEntity($nuEntity);

        $expected = new AssociationStubMonomorphic();
        $expected->setRelationName('oneSource');
        $expected->setForeignFieldName('one_source');
        $expected->setKeyFieldName('one_id');
        $expected->setBaseType(TestMonomorphicSource::class);
        $expected->setTargType(TestMonomorphicTarget::class);
        $expected->setMultiplicity(AssociationStubRelationType::NULL_ONE());
        $expected->setThroughFieldChain(['one_id','one_source']);
        $expected->setEntity($modelEntity);
        $result = $foo->getRelationsByRelationName(TestMonomorphicTarget::class, 'oneTarget');
        $this->assertEquals(1, count($result));
        $expected->addAssociation($result[0]->getAssocations()[0]);
        $this->assertEquals($expected, $result[0]);
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \POData\Common\InvalidOperationException
     * @throws \ReflectionException
     */
    public function testGetRelationsByRelNameHasMany()
    {
        $metaRaw['id']          = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name']        = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metaRaw['many_source'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metaRaw['one_source']  = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metaRaw['many_id']     = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metaRaw['one_id']      = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];

        $model   = new TestMonomorphicSource($metaRaw);
        $nuModel = new TestMonomorphicTarget($metaRaw);

        $foo         = new MetadataRelationshipContainer();
        $modelEntity = $model->extractGubbins();
        $foo->addEntity($modelEntity);
        $nuEntity = $nuModel->extractGubbins();
        $foo->addEntity($nuEntity);

        $expected = new AssociationStubMonomorphic();
        $expected->setRelationName('manyTarget');
        $expected->setForeignFieldName('many_id');
        $expected->setKeyFieldName('many_source');
        $expected->setBaseType(TestMonomorphicTarget::class);
        $expected->setTargType(TestMonomorphicSource::class);
        $expected->setMultiplicity(AssociationStubRelationType::ONE());
        $expected->setThroughFieldChain(['many_source','many_id']);
        $expected->setEntity($nuEntity);

        $result = $foo->getRelationsByRelationName(TestMonomorphicSource::class, 'manySource');
        $this->assertEquals(1, count($result));
        $expected->addAssociation($result[0]->getAssocations()[0]);
        $this->assertEquals($expected, $result[0]);
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \POData\Common\InvalidOperationException
     * @throws \ReflectionException
     */
    public function testGetRelationsByRelNameBelongsToMany()
    {
        $metaRaw['id']          = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name']        = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metaRaw['many_id']     = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metaRaw['many_source'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metaRaw['one_source']  = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metaRaw['one_id']      = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];

        $model   = new TestMonomorphicSource($metaRaw);
        $nuModel = new TestMonomorphicTarget($metaRaw);

        $foo         = new MetadataRelationshipContainer();
        $modelEntity = $model->extractGubbins();
        $foo->addEntity($modelEntity);
        $nuEntity = $nuModel->extractGubbins();
        $foo->addEntity($nuEntity);

        $expected = new AssociationStubMonomorphic();
        $expected->setRelationName('manySource');
        $expected->setForeignFieldName('many_source');
        $expected->setKeyFieldName('many_id');
        $expected->setBaseType(TestMonomorphicSource::class);
        $expected->setTargType(TestMonomorphicTarget::class);
        $expected->setMultiplicity(AssociationStubRelationType::MANY());
        $expected->setThroughFieldChain(['many_id', 'many_source']);
        $expected->setEntity($modelEntity);

        $result = $foo->getRelationsByRelationName(TestMonomorphicTarget::class, 'manyTarget');
        $this->assertEquals(1, count($result));
        $expected->addAssociation($result[0]->getAssocations()[0]);
        $this->assertEquals($expected, $result[0]);
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \POData\Common\InvalidOperationException
     * @throws \ReflectionException
     */
    public function testGetRelationsByRelNameBelongsToManyToMany()
    {
        $metaRaw['id']   = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];

        $model   = new TestMonomorphicManySource($metaRaw);
        $nuModel = new TestMonomorphicManyTarget($metaRaw);

        $foo         = new MetadataRelationshipContainer();
        $modelEntity = $model->extractGubbins();
        $foo->addEntity($modelEntity);
        $nuEntity = $nuModel->extractGubbins();
        $foo->addEntity($nuEntity);

        $expected = new AssociationStubMonomorphic();
        $expected->setRelationName('manyTarget');
        $expected->setForeignFieldName('id');
        $expected->setKeyFieldName('id');
        $expected->setThroughFieldChain([ 'id', 'many_id', 'many_source', 'id']);
        $expected->setBaseType(TestMonomorphicManyTarget::class);
        $expected->setTargType(TestMonomorphicManySource::class);
        $expected->setMultiplicity(AssociationStubRelationType::MANY());
        $expected->setEntity($nuEntity);

        $result = $foo->getRelationsByRelationName(TestMonomorphicManySource::class, 'manySource');
        $this->assertEquals(1, count($result));
        $expected->addAssociation($result[0]->getAssocations()[0]);
        $this->assertEquals($expected, $result[0]);
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \POData\Common\InvalidOperationException
     * @throws \ReflectionException
     */
    public function testGetRelationsByRelNameBelongsToManyToManyReverse()
    {
        $metaRaw['id']   = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];

        $model   = new TestMonomorphicManySource($metaRaw);
        $nuModel = new TestMonomorphicManyTarget($metaRaw);

        $foo         = new MetadataRelationshipContainer();
        $modelEntity = $model->extractGubbins();
        $foo->addEntity($modelEntity);
        $nuEntity = $nuModel->extractGubbins();
        $foo->addEntity($nuEntity);

        $expected = new AssociationStubMonomorphic();
        $expected->setRelationName('manySource');
        $expected->setForeignFieldName('id');
        $expected->setKeyFieldName('id');
        $expected->setBaseType(TestMonomorphicManySource::class);
        $expected->setTargType(TestMonomorphicManyTarget::class);
        $expected->setMultiplicity(AssociationStubRelationType::MANY());
        $expected->setThroughFieldChain(['id', 'many_source', 'many_id', 'id']);
        $expected->setEntity($modelEntity);

        $result = $foo->getRelationsByRelationName(TestMonomorphicManyTarget::class, 'manyTarget');
        $this->assertEquals(1, count($result));
        $expected->addAssociation($result[0]->getAssocations()[0]);
        $this->assertEquals($expected, $result[0]);
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \POData\Common\InvalidOperationException
     * @throws \ReflectionException
     */
    public function testGetRelationsByRelNameBelongsToIsKnownSide()
    {
        $metaRaw['id']       = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name']     = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metaRaw['morph_id'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];

        $model   = new TestMorphTarget($metaRaw);
        $nuModel = new TestMorphOneSource($metaRaw);

        $foo         = new MetadataRelationshipContainer();
        $modelEntity = $model->extractGubbins();
        $foo->addEntity($modelEntity);
        $nuEntity = $nuModel->extractGubbins();
        $foo->addEntity($nuEntity);

        $result = $foo->getRelationsByRelationName(TestMorphTarget::class, 'morph');
        $this->assertEquals(1, count($result));
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \POData\Common\InvalidOperationException
     * @throws \ReflectionException
     */
    public function testGetRelationsByRelNameMorphOne()
    {
        $metaRaw['id']       = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name']     = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metaRaw['morph_id'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];

        $model   = new TestMorphTarget($metaRaw);
        $nuModel = new TestMorphOneSource($metaRaw);

        $foo         = new MetadataRelationshipContainer();
        $modelEntity = $model->extractGubbins();
        $foo->addEntity($modelEntity);
        $nuEntity = $nuModel->extractGubbins();
        $foo->addEntity($nuEntity);

        $expected = new AssociationStubPolymorphic();
        $expected->setRelationName('morph_TestMorphOneSource');
        $expected->setForeignFieldName('id');
        $expected->setKeyFieldName('morph_id');
        $expected->setBaseType(TestMorphTarget::class);
        $expected->setTargType(TestMorphOneSource::class);
        $expected->setMultiplicity(AssociationStubRelationType::ONE());
        $expected->setThroughFieldChain(['morph_id', 'morph_type', null]);
        $expected->setMorphType('morph_type');
        $expected->setEntity($modelEntity);

        $result = $foo->getRelationsByRelationName(TestMorphOneSource::class, 'morphTarget');
        $this->assertEquals(1, count($result));
        $expected->addAssociation($result[0]->getAssocations()[0]);
        $this->assertEquals($expected, $result[0]);
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \POData\Common\InvalidOperationException
     * @throws \ReflectionException
     */
    public function testGetRelationsByRelNameMorphMany()
    {
        $metaRaw['id']       = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name']     = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metaRaw['morph_id'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];

        $model   = new TestMorphTarget($metaRaw);
        $nuModel = new TestMorphManySource($metaRaw);

        $foo         = new MetadataRelationshipContainer();
        $modelEntity = $model->extractGubbins();
        $foo->addEntity($modelEntity);
        $nuEntity = $nuModel->extractGubbins();
        $foo->addEntity($nuEntity);

        $expected = new AssociationStubPolymorphic();
        $expected->setRelationName('morph_TestMorphManySource');
        $expected->setForeignFieldName('id');
        $expected->setKeyFieldName('morph_id');
        $expected->setBaseType(TestMorphTarget::class);
        $expected->setTargType(TestMorphManySource::class);
        $expected->setMultiplicity(AssociationStubRelationType::ONE());
        $expected->setThroughFieldChain(['morph_id', 'morph_type', null]);
        $expected->setMorphType('morph_type');
        $expected->setEntity($modelEntity);
        $result = $foo->getRelationsByRelationName(TestMorphManySource::class, 'morphTarget');
        $this->assertEquals(1, count($result));
        $expected->addAssociation($result[0]->getAssocations()[0]);
        $this->assertEquals($expected, $result[0]);
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \POData\Common\InvalidOperationException
     * @throws \ReflectionException
     */
    public function testGetRelationsByRelNameMorphToMany()
    {
        $metaRaw['id']        = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name']      = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metaRaw['source_id'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metaRaw['target_id'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];

        $model   = new TestMorphManyToManySource($metaRaw);
        $nuModel = new TestMorphManyToManyTarget($metaRaw);

        $foo         = new MetadataRelationshipContainer();
        $modelEntity = $model->extractGubbins();
        $foo->addEntity($modelEntity);
        $nuEntity = $nuModel->extractGubbins();
        $foo->addEntity($nuEntity);

        $expected = new AssociationStubPolymorphic();
        $expected->setRelationName('manyTarget');
        $expected->setForeignFieldName('id');
        $expected->setKeyFieldName('id');
        $expected->setBaseType(TestMorphManyToManyTarget::class);
        $expected->setTargType(TestMorphManyToManySource::class);
        $expected->setMultiplicity(AssociationStubRelationType::MANY());
        $expected->setThroughFieldChain(['id', 'target_id', 'manyable_type', 'source_id', 'id']);
        $expected->setMorphType('manyable_type');
        $expected->setEntity($nuEntity);

        $result = $foo->getRelationsByRelationName(TestMorphManyToManySource::class, 'manySource');
        $this->assertEquals(1, count($result));
        $expected->addAssociation($result[0]->getAssocations()[0]);
        $this->assertEquals($expected, $result[0]);
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \POData\Common\InvalidOperationException
     * @throws \ReflectionException
     */
    public function testGetRelationsByRelNameMorphedByMany()
    {
        $metaRaw['id']        = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name']      = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metaRaw['source_id'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metaRaw['target_id'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];

        $model   = new TestMorphManyToManySource($metaRaw);
        $nuModel = new TestMorphManyToManyTarget($metaRaw);

        $foo         = new MetadataRelationshipContainer();
        $modelEntity = $model->extractGubbins();
        $foo->addEntity($modelEntity);
        $nuEntity = $nuModel->extractGubbins();
        $foo->addEntity($nuEntity);
        $result = $foo->getRelationsByRelationName(TestMorphManyToManyTarget::class, 'manyTarget');

        $this->assertEquals(1, count($result));
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \POData\Common\InvalidOperationException
     * @throws \ReflectionException
     */
    public function testGetRelationsTwoArmedPolymorphicRelation()
    {
        $metaRaw['id']               = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['alternate_id']     = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name']             = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metaRaw['morph_id']         = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];

        $model    = new TestMorphTarget($metaRaw);
        $nuModel  = new TestMorphManySource($metaRaw);
        $altModel = new TestMorphManySourceAlternate($metaRaw);

        $foo = new MetadataRelationshipContainer();
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
        $metaRaw['id']           = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['alternate_id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name']         = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];

        $model = new TestMonomorphicParentOfMorphTarget($metaRaw);

        $gubbins = $model->extractGubbins();
        $stubs   = $gubbins->getStubs();
        $this->assertTrue(array_key_exists('monomorphicChildren', $stubs));
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \POData\Common\InvalidOperationException
     * @throws \ReflectionException
     */
    public function testGetBidirectionalHasManyThroughRelation()
    {
        $metaRaw['id']                = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['alternate_id']      = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metaRaw['name']              = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metaRaw['parent_id']         = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];

        $model   = new TestMonomorphicParentOfMorphTarget($metaRaw);
        $nuModel = new TestMonomorphicChildOfMorphTarget($metaRaw);

        $modelGubbins   = $model->extractGubbins();
        $nuModelGubbins = $nuModel->extractGubbins();

        $left  = $modelGubbins->getStubs()['monomorphicChildren'];
        $right = $nuModelGubbins->getStubs()['monomorphicParent'];
        $this->assertTrue($left->isCompatible($right));

        $foo = new MetadataRelationshipContainer();
        $foo->addEntity($model->extractGubbins());
        $foo->addEntity($nuModel->extractGubbins());

        $result = $foo->getRelationsByRelationName(TestMonomorphicParentOfMorphTarget::class, 'monomorphicChildren');
        $this->assertTrue(0 < count($result));
    }
}
