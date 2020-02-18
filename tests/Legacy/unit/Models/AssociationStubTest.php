<?php

namespace Tests\AlgoWeb\PODataLaravel\Models;

use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubMonomorphic;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubPolymorphic;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubRelationType;
use Mockery as m;
use POData\Common\InvalidOperationException;

class AssociationStubTest extends TestCase
{
    public function testAssociationIncompatibleDifferentTypesPoly()
    {
        $foo = new AssociationStubPolymorphic();
        $bar = new AssociationStubMonomorphic();

        $this->assertFalse($foo->isCompatible($bar));
    }

    public function testAssociationIncompatibleDifferentTypesMono()
    {
        $foo = new AssociationStubPolymorphic();
        $bar = new AssociationStubMonomorphic();

        $this->assertFalse($bar->isCompatible($foo));
    }

    public function testMonomorphicAssociationIsOkBadForeignField()
    {
        $foo = new AssociationStubMonomorphic();
        $foo->setKeyField('key');
        $foo->setRelationName('rel');
        $foo->setMultiplicity(AssociationStubRelationType::NULL_ONE());
        $foo->setBaseType(TestMorphTarget::class);
        $foo->setTargType(TestMorphTargetChild::class);
        $this->assertFalse($foo->isOk());
        $foo->setForeignField(123);
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
        $foo = new AssociationStubPolymorphic();
        $foo->setKeyField('key');
        $foo->setRelationName('rel');
        $foo->setMultiplicity(AssociationStubRelationType::NULL_ONE());
        $foo->setBaseType(TestMorphTarget::class);
        $foo->setTargType(null);
        $foo->setForeignField(123);
        $this->assertFalse($foo->isOk());
    }

    public function testAssociationIncompatibleOnBothNullableOne()
    {
        $foo = new AssociationStubPolymorphic();
        $bar = new AssociationStubPolymorphic();
        $foo->setKeyField('key');
        $bar->setKeyField('key');
        $foo->setRelationName('rel');
        $bar->setRelationName('rel');
        $foo->setMultiplicity(AssociationStubRelationType::NULL_ONE());
        $bar->setMultiplicity(AssociationStubRelationType::NULL_ONE());
        $foo->setBaseType(TestMorphTarget::class);
        $foo->setTargType(TestMorphTargetChild::class);
        $bar->setBaseType(TestMorphTargetChild::class);
        $foo->setForeignField('foreign');
        $bar->setForeignField(null);
        $this->assertTrue($foo->isOk());
        $this->assertTrue($bar->isOk());

        $this->assertFalse($foo->isCompatible($bar));
        $this->assertFalse($bar->isCompatible($foo));
    }

    public function testAssociationCompatibleOnBothMany()
    {
        $foo = new AssociationStubPolymorphic();
        $bar = new AssociationStubPolymorphic();
        $foo->setKeyField('key');
        $bar->setKeyField('key');
        $foo->setRelationName('rel');
        $bar->setRelationName('rel');
        $foo->setBaseType(TestMorphManyToManySource::class);
        $foo->setTargType(TestMorphManyToManyTarget::class);
        $bar->setBaseType(TestMorphManyToManyTarget::class);
        $foo->setMultiplicity(AssociationStubRelationType::MANY());
        $bar->setMultiplicity(AssociationStubRelationType::MANY());
        $foo->setForeignField('foreign');
        $bar->setForeignField(null);
        $this->assertTrue($foo->isOk());
        $this->assertTrue($bar->isOk());

        $this->assertTrue($foo->isCompatible($bar));
        $this->assertTrue($bar->isCompatible($foo));
        $this->assertFalse($foo->isKnownSide());
        $this->assertTrue($bar->isKnownSide());
    }

    public function testAssociationIncompatibleOnBothOne()
    {
        $foo = new AssociationStubPolymorphic();
        $bar = new AssociationStubPolymorphic();
        $foo->setKeyField('key');
        $bar->setKeyField('key');
        $foo->setRelationName('rel');
        $bar->setRelationName('rel');
        $foo->setMultiplicity(AssociationStubRelationType::ONE());
        $bar->setMultiplicity(AssociationStubRelationType::ONE());
        $foo->setBaseType(TestMorphTarget::class);
        $foo->setTargType(TestMorphTargetChild::class);
        $bar->setBaseType(TestMorphTargetChild::class);
        $foo->setForeignField('foreign');
        $bar->setForeignField(null);
        $this->assertTrue($foo->isOk());
        $this->assertTrue($bar->isOk());

        $this->assertFalse($foo->isCompatible($bar));
        $this->assertFalse($bar->isCompatible($foo));
        $this->assertFalse($foo->isKnownSide());
        $this->assertTrue($bar->isKnownSide());
    }

    public function testAssociationPolymorphicWithBothEndsKnown()
    {
        $foo = new AssociationStubPolymorphic();
        $bar = new AssociationStubPolymorphic();
        $foo->setKeyField('key');
        $bar->setKeyField('key');
        $foo->setRelationName('rel');
        $bar->setRelationName('rel');
        $foo->setMultiplicity(AssociationStubRelationType::ONE());
        $bar->setMultiplicity(AssociationStubRelationType::NULL_ONE());
        $foo->setBaseType(TestMorphTarget::class);
        $bar->setBaseType(TestMorphTargetChild::class);
        $this->assertTrue($foo->isOk());
        $this->assertTrue($bar->isOk());

        $this->assertFalse($foo->isCompatible($bar));
        $this->assertFalse($bar->isCompatible($foo));
        $this->assertTrue($foo->isKnownSide());
        $this->assertTrue($bar->isKnownSide());
    }

    public function testAssociationPolymorphicWithIncompatibleTypes()
    {
        $foo = new AssociationStubPolymorphic();
        $bar = new AssociationStubPolymorphic();
        $foo->setKeyField('key');
        $bar->setKeyField('key');
        $foo->setRelationName('rel');
        $bar->setRelationName('rel');
        $foo->setMultiplicity(AssociationStubRelationType::ONE());
        $bar->setMultiplicity(AssociationStubRelationType::NULL_ONE());
        $foo->setBaseType(TestMorphTarget::class);
        $foo->setTargType(TestMonomorphicSource::class);
        $bar->setBaseType(TestMorphTarget::class);
        $foo->setForeignField('foreign');
        $bar->setForeignField(null);
        $this->assertTrue($foo->isOk());
        $this->assertTrue($bar->isOk());

        $this->assertFalse($foo->isCompatible($bar));
        $this->assertFalse($bar->isCompatible($foo));
    }

    public function testAssociationMonomorphicNotOk()
    {
        $foo = new AssociationStubMonomorphic();
        $foo->setKeyField('key');
        $foo->setRelationName('rel');
        $foo->setMultiplicity(AssociationStubRelationType::ONE());
        $foo->setBaseType(TestMonomorphicSource::class);
        $this->assertFalse($foo->isOk());
        $foo->setTargType(123);
        $this->assertFalse($foo->isOk());
    }

    public function testAssociationIncompatibleBothNotOk()
    {
        $foo = new AssociationStubPolymorphic();
        $bar = new AssociationStubPolymorphic();
        $foo->setKeyField('key');
        $bar->setKeyField('');
        $foo->setRelationName('rel');
        $bar->setRelationName('rel');
        $foo->setMultiplicity(AssociationStubRelationType::ONE());
        $bar->setMultiplicity(AssociationStubRelationType::ONE());
        $foo->setBaseType(TestMorphTarget::class);
        $this->assertTrue($foo->isOk());
        $this->assertFalse($bar->isOk());

        $this->assertFalse($foo->isCompatible($bar));
        $this->assertFalse($bar->isCompatible($foo));
    }

    public function testAssociationNewCreationNotOk()
    {
        $foo = new AssociationStubPolymorphic();
        $this->assertFalse($foo->isOk());
        $foo->setMultiplicity(AssociationStubRelationType::ONE());
        $this->assertFalse($foo->isOk());
        $foo->setKeyField('key');
        $this->assertFalse($foo->isOk());
        $foo->setRelationName('rel');
        $this->assertFalse($foo->isOk());
        $foo->setBaseType(TestMorphTarget::class);
        $this->assertTrue($foo->isOk());
    }

    public function testStringFieldsRoundTrip()
    {
        $expectedKey = 'key';
        $expectedRel = 'rel';
        $expectedMorph = 'morph';
        $foo = new AssociationStubPolymorphic();
        $foo->setKeyField($expectedKey);
        $actualKey = $foo->getKeyField();
        $this->assertEquals($expectedKey, $actualKey);
        $foo->setRelationName($expectedRel);
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

        $foo = new AssociationStubMonomorphic();
        $foo->setKeyField($expectedKey);
        $foo->setRelationName($expectedRel);
        $foo->setMultiplicity(AssociationStubRelationType::NULL_ONE());
        $this->assertFalse($foo->isOk());
    }

    public function testCompareWithMonomorphicSelf()
    {
        $foo = new AssociationStubMonomorphic();
        $this->assertEquals(0, $foo->compare($foo));
    }

    public function testCompareWithPolymorphicSelf()
    {
        $foo = new AssociationStubPolymorphic();
        $this->assertEquals(0, $foo->compare($foo));
    }

    public function testCompareDifferentTypes()
    {
        $foo = new AssociationStubMonomorphic();
        $bar = new AssociationStubPolymorphic();

        $this->assertEquals(-1, $foo->compare($bar));
        $this->assertEquals(1, $bar->compare($foo));
    }

    public function testCompareDifferentBaseTypes()
    {
        $foo = new AssociationStubMonomorphic();
        $foo->setBaseType('def');
        $bar = new AssociationStubMonomorphic();
        $bar->setBaseType('abc');
        $this->assertEquals(1, $foo->compare($bar));
        $this->assertEquals(-1, $bar->compare($foo));
    }

    public function testCompareDifferentMethods()
    {
        $foo = new AssociationStubMonomorphic();
        $foo->setBaseType('abc');
        $foo->setRelationName('slash');
        $bar = new AssociationStubMonomorphic();
        $bar->setBaseType('abc');
        $bar->setRelationName('dot');
        $this->assertEquals(1, $foo->compare($bar));
        $this->assertEquals(-1, $bar->compare($foo));
    }

    public function testIsKnownSideInconsistentState()
    {
        $foo = new AssociationStubPolymorphic();

        $expected = 'Polymorphic stub not OK so known-side determination is meaningless';
        $actual = null;

        try {
            $foo->isKnownSide();
        } catch (InvalidOperationException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }
}
