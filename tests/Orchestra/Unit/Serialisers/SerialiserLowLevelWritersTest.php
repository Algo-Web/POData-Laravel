<?php

declare(strict_types=1);
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
use Mockery as m;
use POData\Common\InvalidOperationException;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourcePropertyKind;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\ResourceTypeKind;
use POData\Providers\Metadata\Type\DateTime;
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

    public function testBadDateWithDateType()
    {
        $date = 'date!';
        $type = new DateTime();

        $expected = 'date!';

        $actual = SerialiserLowLevelWriters::primitiveToString($type, $date);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @throws InvalidOperationException
     * @throws \ReflectionException
     */
    public function testWriteBagValueWithBadPrimitiveType()
    {
        $rProp = m::mock(ResourceProperty::class);
        $rProp->shouldReceive('getKind')->andReturn(ResourcePropertyKind::PRIMITIVE());
        $rProp->shouldReceive('getName')->andReturn('property');

        $rType = m::mock(ResourceType::class);
        $rType->shouldReceive('getAllProperties')->andReturn([$rProp]);
        $rType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::PRIMITIVE());
        $rType->shouldReceive('getInstanceType')->andReturn(new \stdClass());

        $result = ['foo'];

        $this->expectException(InvalidOperationException::class);
        $this->expectExceptionMessage('stdClass');

        SerialiserLowLevelWriters::writeBagValue($rType, $result);
    }

    public function testWriteComplexValueWithObjectCollision()
    {
        $rType = m::mock(ResourceType::class);

        $result = new \stdClass();
        $coll   = [$result];

        $this->expectException(InvalidOperationException::class);
        $this->expectExceptionMessage('circular loop was detected');

        SerialiserLowLevelWriters::writeComplexValue($rType, $result, 'property', $coll);
    }
}
