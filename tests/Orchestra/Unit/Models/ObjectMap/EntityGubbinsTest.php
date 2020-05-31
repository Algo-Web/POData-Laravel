<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 1/03/20
 * Time: 11:52 AM.
 */
namespace AlgoWeb\PODataLaravel\Orchestra\Tests\Unit\Models\ObjectMap;

use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationMonomorphic;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubMonomorphic;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\EntityField;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\EntityGubbins;
use AlgoWeb\PODataLaravel\Orchestra\Tests\Models\ObjectMap\Entities\DummyEntityGubbins;
use AlgoWeb\PODataLaravel\Orchestra\Tests\TestCase;
use Mockery as m;
use POData\Common\InvalidOperationException;

class EntityGubbinsTest extends TestCase
{
    /**
     * @throws InvalidOperationException
     * @throws \Exception
     */
    public function testAddAssociationWithUnnamedStub()
    {
        $stub = m::mock(AssociationStubMonomorphic::class)->makePartial();
        $stub->shouldReceive('getRelationName')->andReturn('')->once();

        $assoc = m::mock(AssociationMonomorphic::class)->makePartial();
        $assoc->shouldReceive('getFirst')->andReturn($stub)->once();

        $foo = new EntityGubbins();

        $this->expectException(InvalidOperationException::class);

        $foo->setStubs([$stub]);
        $foo->addAssociation($assoc);
    }

    public function testIsNotOkWhenFieldAndAssocNamesCollide()
    {
        $foo = m::mock(EntityGubbins::class)->shouldAllowMockingProtectedMethods()->makePartial();
        $foo->shouldReceive('getFieldNames')->andReturn(['foo', 'bar']);
        $foo->shouldReceive('getAssociationNames')->andReturn(['baz', 'bar']);

        $this->assertFalse($foo->isOk());
    }

    public function testIsOkWhenFieldAndAssocNamesDontCollide()
    {
        $foo = m::mock(EntityGubbins::class)->shouldAllowMockingProtectedMethods()->makePartial();
        $foo->shouldReceive('getFieldNames')->andReturn(['foo']);
        $foo->shouldReceive('getAssociationNames')->andReturn(['baz']);

        $this->assertTrue($foo->isOk());
    }

    /**
     * @throws \Exception
     */
    public function testGetFieldNames()
    {
        $foo   = new DummyEntityGubbins();
        $field = new EntityField();
        $field->setIsKeyField(true);
        $bar = new EntityField();

        $foo->setFields([$field, $bar]);
        $this->assertEquals(1, count($foo->getKeyFields()));
        $this->assertEquals(2, count($foo->getFields()));

        $expected = [null, null];
        $actual   = $foo->getFieldNames();

        $this->assertEquals($expected, $actual);
    }

    /**
     * @throws \Exception
     */
    public function testGetAssociationNames()
    {
        $foo   = new DummyEntityGubbins();

        $stub = m::mock(AssociationStubMonomorphic::class)->makePartial();
        $stub->shouldReceive('getRelationName')->andReturn('relName')->once();

        $newstub = m::mock(AssociationStubMonomorphic::class)->makePartial();
        $newstub->shouldReceive('getRelationName')->andReturn('newRelName')->once();

        $foo->setStubs([$stub, $newstub]);

        $expected = ['relName', 'newRelName'];
        $actual   = $foo->getAssociationNames();
        $this->assertEquals($expected, $actual);
    }

    public function testResolveBlankAssociationName()
    {
        $foo = new DummyEntityGubbins();

        $this->assertNull($foo->resolveAssociation(''));
    }
}
