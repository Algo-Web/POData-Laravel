<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 20/02/20
 * Time: 12:35 PM
 */

namespace AlgoWeb\PODataLaravel\Orchestra\Tests\Unit\Serialisers;

use AlgoWeb\PODataLaravel\Orchestra\Tests\TestCase;
use AlgoWeb\PODataLaravel\Serialisers\SerialiserLowLevelWriters;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use POData\Providers\Metadata\Type\StringType;

class SerialiserLowLevelWritersTest extends TestCase
{
    use DatabaseMigrations;

    public function testUTF8StringNotMangled()
    {
        $expected = 'Müller';
        $type = new StringType();

        $actual = SerialiserLowLevelWriters::primitiveToString($type, $expected);

        $this->assertEquals($expected, $actual);
    }
}
