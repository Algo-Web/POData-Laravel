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
        $foo->setKeyFieldName('key');
        $foo->setRelationName('rel');
        $foo->setMultiplicity(AssociationStubRelationType::NULL_ONE());
        $foo->setBaseType(TestMorphTarget::class);
        $foo->setTargType(TestMorphTargetChild::class);
        $this->assertFalse($foo->isOk());
        $foo->setForeignFieldName(123);
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
        $foo->setKeyFieldName('key');
        $foo->setRelationName('rel');
        $foo->setMultiplicity(AssociationStubRelationType::NULL_ONE());
        $foo->setBaseType(TestMorphTarget::class);
        $foo->setTargType(null);
        $foo->setForeignFieldName(123);
        $this->assertFalse($foo->isOk());
    }

    public function testAssociationIncompatibleOnBothNullableOne()
    {
        $foo = new AssociationStubPolymorphic();
        $bar = new AssociationStubPolymorphic();
        $foo->setKeyFieldName('key');
        $bar->setKeyFieldName('key');
        $foo->setRelationName('rel');
        $bar->setRelationName('rel');
        $foo->setMultiplicity(AssociationStubRelationType::NULL_ONE());
        $bar->setMultiplicity(AssociationStubRelationType::NULL_ONE());
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
        $foo = new AssociationStubMonomorphic();
        $foo->setKeyFieldName('key');
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
        $foo->setKeyFieldName('key');
        $bar->setKeyFieldName('');
        $foo->setRelationName('rel');
        $bar->setRelationName('rel');
        $foo->setThroughFieldChain([]);
        $bar->setThroughFieldChain([]);
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
        $foo->setKeyFieldName('key');
        $this->assertFalse($foo->isOk());
        $foo->setRelationName('rel');
        $this->assertFalse($foo->isOk());
        $foo->setBaseType(TestMorphTarget::class);
        $this->assertFalse($foo->isOk());
        $foo->setThroughFieldChain(['key',null]);
        $this->assertTrue($foo->isOk());
    }

    public function testStringFieldsRoundTrip()
    {
        $expectedKey   = 'key';
        $expectedRel   = 'rel';
        $expectedMorph = 'morph';
        $foo           = new AssociationStubPolymorphic();
        $foo->setKeyFieldName($expectedKey);
        $actualKey = $foo->getKeyFieldName();
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
        $foo->setKeyFieldName($expectedKey);
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
}
