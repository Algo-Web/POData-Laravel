<?php

namespace Tests\Legacy\Unit\AlgoWeb\PODataLaravel\Models;

use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\Association;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\EntityGubbins;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Map;
use Mockery as m;
use Tests\Legacy\AlgoWeb\PODataLaravel\Models\TestCase;

class ObjectMapTest extends TestCase
{
    public function testAddBadAssociation()
    {
        $foo = new Map();
        $assoc = m::mock(Association::class);

        $expected = 'Association type not yet handled';
        $actual = null;

        try {
            $foo->addAssociation($assoc);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testSetEmptyEntities()
    {
        $foo = new Map();

        $foo->setEntities([]);
        $this->assertEquals(0, count($foo->getEntities()));
    }

    public function testSetEntitiesGood()
    {
        $foo = new Map();
        $gubbins = new EntityGubbins();
        $gubbins->setClassName('SpaceJam');

        $foo->setEntities([$gubbins]);
        $result = $foo->getEntities();
        $this->assertTrue(is_array($result));
        $this->assertTrue(isset($result['SpaceJam']));
        $this->assertTrue($result['SpaceJam'] instanceof EntityGubbins);
        $this->assertEquals('SpaceJam', $result['SpaceJam']->getClassName());
    }

    public function testSetEntitiesBad()
    {
        $foo = new Map();

        $expected = 'Entities array must contain only EntityGubbins objects';
        $actual = null;

        try {
            $foo->setEntities([null, 'abc']);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testAssociationsOnEmpty()
    {
        $foo = new Map();

        $result = $foo->getAssociations();
        $this->assertTrue(is_array($result));
        $this->assertEquals(0, count($result));
    }
}
