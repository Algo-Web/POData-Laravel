<?php

namespace AlgoWeb\PODataLaravel\Models;

use Mockery as m;

class AssociationStubTest extends TestCase
{
    public function testAssociationIncompatibleDifferentTypes()
    {
        $foo = new AssociationStubPolymorphic();
        $bar = new AssociationStubMonomorphic();

        $this->assertFalse($foo->isCompatible($bar));
        $this->assertFalse($bar->isCompatible($foo));
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
        $this->assertTrue($foo->isOk());
        $this->assertTrue($bar->isOk());

        $this->assertTrue($foo->isCompatible($bar));
        $this->assertTrue($bar->isCompatible($foo));
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
        $this->assertTrue($foo->isOk());
        $this->assertTrue($bar->isOk());

        $this->assertFalse($foo->isCompatible($bar));
        $this->assertFalse($bar->isCompatible($foo));
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
}
