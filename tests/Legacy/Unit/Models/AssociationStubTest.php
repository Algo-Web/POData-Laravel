<?php declare(strict_types=1);

namespace Tests\Legacy\AlgoWeb\PODataLaravel\Unit\Models;

use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubMonomorphic;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubPolymorphic;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubRelationType;
use Mockery as m;
use POData\Common\InvalidOperationException;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMonomorphicSource;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMorphManyToManySource;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMorphManyToManyTarget;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMorphTarget;
use Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMorphTargetChild;
use Tests\Legacy\AlgoWeb\PODataLaravel\TestCase;

class AssociationStubTest extends TestCase
{
    public function testAssociationIncompatibleDifferentTypesPoly()
    {
        $foo = new AssociationStubPolymorphic('name', 'id', [], AssociationStubRelationType::NULL_ONE());
        $bar = new AssociationStubMonomorphic('name', 'id', [], AssociationStubRelationType::NULL_ONE());

        $this->assertFalse($foo->isCompatible($bar));
    }

    public function testAssociationIncompatibleDifferentTypesMono()
    {
        $foo = new AssociationStubPolymorphic('name', 'id', [], AssociationStubRelationType::NULL_ONE());
        $bar = new AssociationStubMonomorphic('name', 'id', [], AssociationStubRelationType::NULL_ONE());

        $this->assertFalse($bar->isCompatible($foo));
    }

    public function testMonomorphicAssociationIsOkBadForeignField()
    {
        $foo = new AssociationStubMonomorphic('rel', 'key', [], AssociationStubRelationType::NULL_ONE());
        $foo->setBaseType(TestMorphTarget::class);
        $foo->setTargType(TestMorphTargetChild::class);
        $this->assertFalse($foo->isOk());
        $foo->setForeignFieldName('');
        $this->assertFalse($foo->isOk());
    }

    public function testMonomorphicAssociationIsIncompatibleNotOk()
    {
        $foo = m::mock(AssociationStubMonomorphic::class);
        $foo->shouldReceive('isOk')->andReturn(false)->once();
        $foo->shouldReceive('isCompatible')->passthru()->once();
        $foo->shouldReceive('morphicType')->passthru();
        $other = m::mock(AssociationStubMonomorphic::class);
        $other->shouldReceive('isOk')->andReturn(false)->never();
        $other->shouldReceive('morphicType')->passthru();

        $this->assertFalse($foo->isCompatible($other));
    }

    public function testPolymorphicAssociationIsIncompatibleNotOk()
    {
        $foo = m::mock(AssociationStubPolymorphic::class);
        $foo->shouldReceive('isOk')->andReturn(true)->once();
        $foo->shouldReceive('isCompatible')->passthru()->once();
        $foo->shouldReceive('morphicType')->passthru();
        $other = m::mock(AssociationStubPolymorphic::class);
        $other->shouldReceive('isOk')->andReturn(false)->once();
        $other->shouldReceive('morphicType')->passthru();

        $this->assertFalse($foo->isCompatible($other));
    }

    public function testPolymorphicAssociationIsOkBadForeignField()
    {
        $foo = new AssociationStubPolymorphic('rel', 'key', [], AssociationStubRelationType::NULL_ONE());
        $foo->setBaseType(TestMorphTarget::class);
        $foo->setTargType(null);
        $foo->setForeignFieldName(123);
        $this->assertFalse($foo->isOk());
    }

    public function testAssociationIncompatibleOnBothNullableOne()
    {
        $foo = new AssociationStubPolymorphic('rel', 'key', [], AssociationStubRelationType::NULL_ONE());
        $bar = new AssociationStubPolymorphic('rel', 'key', [], AssociationStubRelationType::NULL_ONE());
        $foo->setBaseType(TestMorphTarget::class);
        $foo->setTargType(null);
        $bar->setBaseType(TestMorphTargetChild::class);
        $foo->setForeignFieldName(null);
        $bar->setForeignFieldName(null);
        $foo->setThroughFieldChain(['key',null]);
        $bar->setThroughFieldChain(['key',null]);
        $this->assertTrue($foo->isOk());
        $this->assertTrue($bar->isOk());

        $this->assertFalse($foo->isCompatible($bar));
        $this->assertFalse($bar->isCompatible($foo));
    }


    public function testAssociationMonomorphicNotOk()
    {
        $foo = new AssociationStubMonomorphic('rel', 'key', [], AssociationStubRelationType::ONE());
        $foo->setBaseType(TestMonomorphicSource::class);
        $this->assertFalse($foo->isOk());
        $foo->setTargType('');
        $this->assertFalse($foo->isOk());
    }

    public function testAssociationIncompatibleBothNotOk()
    {
        $foo = new AssociationStubPolymorphic('rel', 'key', [null, 'rel_id', 'rel_type'], AssociationStubRelationType::ONE());
        $bar = new AssociationStubPolymorphic('rel', '', ['rel_id',"rel_type",null], AssociationStubRelationType::ONE());
        $foo->setBaseType(TestMorphTarget::class);
        $this->assertTrue($foo->isOk());
        $this->assertFalse($bar->isOk());
        $this->assertFalse($foo->isCompatible($bar));
        $this->assertFalse($bar->isCompatible($foo));
    }

    public function testAssociationNewCreationNotOk()
    {
        $foo = new AssociationStubPolymorphic('', '', [], AssociationStubRelationType::ONE());
        $this->assertFalse($foo->isOk());
        $foo->setMultiplicity(AssociationStubRelationType::ONE());
        $this->assertFalse($foo->isOk());
        $foo->setKeyFieldName('key');
        $this->assertFalse($foo->isOk());
        $foo->setRelationName('rel');
        $this->assertFalse($foo->isOk());
        $foo->setBaseType(TestMorphTarget::class);
        $foo->setThroughFieldChain(['key',null]);
        $this->assertTrue($foo->isOk());
    }

    public function testStringFieldsRoundTrip()
    {
        $expectedKey   = 'key';
        $expectedRel   = 'rel';
        $expectedMorph = 'morph';
        $foo           = new AssociationStubPolymorphic($expectedRel, $expectedKey, [], AssociationStubRelationType::NULL_ONE());
        $actualKey = $foo->getKeyFieldName();
        $this->assertEquals($expectedKey, $actualKey);
        $actualRel = $foo->getRelationName();
        $this->assertEquals($expectedRel, $actualRel);
        $foo->setMorphType($expectedMorph);
        $actualMorph = $foo->getMorphType();
        $this->assertEquals($expectedMorph, $actualMorph);
    }

    public function testMonomorphicNotOkWithNullTargetType()
    {
        $expectedKey = 'key';
        $expectedRel = 'rel';

        $foo = new AssociationStubMonomorphic($expectedRel, $expectedKey, [], AssociationStubRelationType::NULL_ONE());
        $this->assertFalse($foo->isOk());
    }

    public function testCompareWithMonomorphicSelf()
    {
        $foo = new AssociationStubMonomorphic('rel', 'key', [], AssociationStubRelationType::NULL_ONE());
        $this->assertEquals(0, $foo->compare($foo));
    }

    public function testCompareWithPolymorphicSelf()
    {
        $foo = new AssociationStubPolymorphic('rel', 'key', [], AssociationStubRelationType::NULL_ONE());
        $this->assertEquals(0, $foo->compare($foo));
    }

    public function testCompareDifferentTypes()
    {
        $foo = new AssociationStubMonomorphic('rel', 'key', [], AssociationStubRelationType::NULL_ONE());
        $bar = new AssociationStubPolymorphic('rel', 'key', [], AssociationStubRelationType::NULL_ONE());

        $this->assertEquals(-1, $foo->compare($bar));
        $this->assertEquals(1, $bar->compare($foo));
    }

    public function testCompareDifferentBaseTypes()
    {
        $foo = new AssociationStubMonomorphic('rel', 'key', [], AssociationStubRelationType::NULL_ONE());
        $foo->setBaseType('def');
        $bar = new AssociationStubMonomorphic('rel', 'key', [], AssociationStubRelationType::NULL_ONE());
        $bar->setBaseType('abc');
        $this->assertEquals(1, $foo->compare($bar));
        $this->assertEquals(-1, $bar->compare($foo));
    }

    public function testCompareDifferentMethods()
    {
        $foo = new AssociationStubMonomorphic('slash', 'id', [], AssociationStubRelationType::ONE());
        $foo->setBaseType('abc');
        $foo->setRelationName('slash');
        $bar = new AssociationStubMonomorphic('slash', 'id', [], AssociationStubRelationType::ONE());
        $bar->setBaseType('abc');
        $bar->setRelationName('dot');
        $this->assertEquals(1, $foo->compare($bar));
        $this->assertEquals(-1, $bar->compare($foo));
    }
}
