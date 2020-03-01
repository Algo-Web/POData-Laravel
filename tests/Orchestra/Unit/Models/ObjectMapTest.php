<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 15/02/20
 * Time: 4:26 AM.
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
    public function testNotOkWhenComponentNotOk()
    {
        $foo = new Map();

        $mockEntity = m::mock(EntityGubbins::class);
        $mockEntity->shouldReceive('getClassName')->andReturn('name');
        $mockEntity->shouldReceive('addAssociation')->andReturnNull();
        $mockEntity->shouldReceive('isOK')->andReturn(false)->once();

        $foo->addEntity($mockEntity);

        $this->assertFalse($foo->isOK());
    }

    public function testNOkWhenNoComponents()
    {
        $foo = new Map();

        $this->assertTrue($foo->isOK());
    }
}
