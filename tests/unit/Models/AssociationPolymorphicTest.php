<?php

namespace AlgoWeb\PODataLaravel\Models;

use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationPolymorphic;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubMonomorphic;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubPolymorphic;
use Mockery as m;

class AssociationPolymorphicTest extends TestCase
{
    public function testNotOkNewCreation()
    {
        $foo = new AssociationPolymorphic();
        $this->assertFalse($foo->isOk());
    }

    public function testNotOkFirstNotPolymorphicStub()
    {
        $foo = new AssociationPolymorphic();
        $first = new AssociationStubMonomorphic();
        $foo->setFirst($first);
        $this->assertFalse($foo->isOk());
    }

    public function testNotOkFirstBad()
    {
        $foo = new AssociationPolymorphic();
        $first = m::mock(AssociationStubPolymorphic::class);
        $first->shouldReceive('isOk')->andReturn(false)->once();
        $foo->setFirst($first);
        $this->assertFalse($foo->isOk());
    }

    public function testNotOkLastEmpty()
    {
        $foo = new AssociationPolymorphic();
        $first = m::mock(AssociationStubPolymorphic::class);
        $first->shouldReceive('isOk')->andReturn(true)->once();
        $foo->setFirst($first);
        $this->assertEquals(0, count($foo->getLast()));
        $this->assertFalse($foo->isOk());
    }

    public function testNotOkLastElementWonky()
    {
        $foo = new AssociationPolymorphic();
        $first = m::mock(AssociationStubPolymorphic::class);
        $first->shouldReceive('isOk')->andReturn(true)->once();
        $foo->setFirst($first);
        $last = m::mock(AssociationStubMonomorphic::class);
        $foo->setLast([$last]);
        $this->assertEquals(1, count($foo->getLast()));
        $this->assertFalse($foo->isOk());
    }

    public function testNotOkLastElementIncompatible()
    {
        $foo = new AssociationPolymorphic();
        $first = m::mock(AssociationStubPolymorphic::class);
        $first->shouldReceive('isOk')->andReturn(true)->once();
        $first->shouldReceive('isCompatible')->andReturn(false)->once();
        $foo->setFirst($first);
        $last = m::mock(AssociationStubPolymorphic::class);
        $last->shouldReceive('isOk')->andReturn(true)->once();
        $foo->setLast([$last]);
        $this->assertEquals(1, count($foo->getLast()));
        $this->assertFalse($foo->isOk());
    }

    public function testOkLastElementCompatible()
    {
        $foo = new AssociationPolymorphic();
        $first = m::mock(AssociationStubPolymorphic::class);
        $first->shouldReceive('isOk')->andReturn(true)->once();
        $first->shouldReceive('isCompatible')->andReturn(true)->once();
        $foo->setFirst($first);
        $last = m::mock(AssociationStubPolymorphic::class);
        $last->shouldReceive('isOk')->andReturn(true)->once();
        $foo->setLast([$last]);
        $this->assertEquals(1, count($foo->getLast()));
        $this->assertTrue($foo->isOk());
    }
}
