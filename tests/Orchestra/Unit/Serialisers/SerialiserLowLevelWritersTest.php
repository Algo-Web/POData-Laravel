<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 20/02/20
 * Time: 12:35 PM.
 */
namespace AlgoWeb\PODataLaravel\Orchestra\Tests\Unit\Serialisers;

use AlgoWeb\PODataLaravel\Orchestra\Tests\TestCase;
use AlgoWeb\PODataLaravel\Serialisers\SerialiserLowLevelWriters;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use POData\Providers\Metadata\Type\StringType;

class SerialiserLowLevelWritersTest extends TestCase
{
    public function testUTF8StringNotMangled()
    {
        $expected = 'Müller';
        $type     = new StringType();

        $actual = SerialiserLowLevelWriters::primitiveToString($type, $expected);

        $this->assertEquals($expected, $actual);
    }

    public function testDateWithNonDateIType()
    {
        $date = Carbon::create(2019, 1, 1, 0, 0, 0);
        $type = new StringType();

        $expected = '2019-01-01 00:00:00';

        $actual = SerialiserLowLevelWriters::primitiveToString($type, $date);

        $this->assertEquals($expected, $actual);
    }
}
