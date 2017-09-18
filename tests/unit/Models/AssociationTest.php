<?php

namespace AlgoWeb\PODataLaravel\Models;

use Mockery as m;

class AssociationTest extends TestCase
{
    public function testNotOkNewCreation()
    {
        $foo = new Association();
        $this->assertFalse($foo->isOk());
    }

    public function testNotOkFirstBad()
    {
        $one = m::mock(AssociationStubBase::class);
        $one->shouldReceive('isOk')->andReturn(false);

        $foo = new Association();
        $foo->setFirst($one);
        $this->assertFalse($foo->isOk());
    }

    public function testNotOkSecondEmpty()
    {
        $one = m::mock(AssociationStubBase::class);
        $one->shouldReceive('isOk')->andReturn(true);

        $foo = new Association();
        $foo->setFirst($one);
        $this->assertFalse($foo->isOk());
    }

    public function testNotOkSecondBad()
    {
        $one = m::mock(AssociationStubBase::class);
        $one->shouldReceive('isOk')->andReturn(true);
        $two = m::mock(AssociationStubBase::class);
        $two->shouldReceive('isOk')->andReturn(false);

        $foo = new Association();
        $foo->setFirst($one);
        $foo->setLast($two);
        $this->assertFalse($foo->isOk());
    }

    public function testNotOKIsNotCompatible()
    {
        $one = m::mock(AssociationStubBase::class);
        $one->shouldReceive('isOk')->andReturn(true);
        $one->shouldReceive('isCompatible')->andReturn(false);
        $two = m::mock(AssociationStubBase::class);
        $two->shouldReceive('isOk')->andReturn(true);

        $foo = new Association();
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
        $two = m::mock(AssociationStubBase::class);
        $two->shouldReceive('isOk')->andReturn(true);

        $foo = new Association();
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
        $two = m::mock(AssociationStubBase::class);
        $two->shouldReceive('isOk')->andReturn(true);

        $foo = new Association();
        $foo->setFirst($one);
        $foo->setLast($two);
        $this->assertTrue($foo->isOk());
    }
}
