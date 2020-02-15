<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 15/02/20
 * Time: 4:26 AM
 */

namespace AlgoWeb\PODataLaravel\Orchestra\Tests\Unit\Models;

use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationMonomorphic;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\EntityGubbins;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Map;
use AlgoWeb\PODataLaravel\Orchestra\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Mockery as m;

class ObjectMapTest extends TestCase
{
    use DatabaseMigrations;

    public function testReset()
    {
        $foo = new Map();

        $mockEntity = m::mock(EntityGubbins::class);
        $mockEntity->shouldReceive('getClassName')->andReturn('name');
        $mockEntity->shouldReceive('addAssociation')->andReturnNull();

        $foo->addEntity($mockEntity);
        $this->assertEquals(1, count($foo->getEntities()));

        $assoc = m::mock(AssociationMonomorphic::class);
        $assoc->shouldReceive('getFirst->getBaseType')->andReturn('name');
        $assoc->shouldReceive('getLast->getBaseType')->andReturn('name');

        $foo->addAssociation($assoc);
        $this->assertEquals(1, count($foo->getAssociations()));

        $foo->reset();
        $this->assertEquals(0, count($foo->getEntities()));
        $this->assertEquals(0, count($foo->getAssociations()));
    }
}
