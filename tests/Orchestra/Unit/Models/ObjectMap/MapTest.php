<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 1/06/20
 * Time: 12:28 AM
 */

namespace AlgoWeb\PODataLaravel\Orchestra\Tests\Unit\Models\ObjectMap;

use AlgoWeb\PODataLaravel\Models\ObjectMap\Map;
use AlgoWeb\PODataLaravel\Orchestra\Tests\TestCase;

class MapTest extends TestCase
{
    public function testResolveBlankEntityName()
    {
        $foo = new Map();

        $this->assertNull($foo->resolveEntity(''));
    }
}
