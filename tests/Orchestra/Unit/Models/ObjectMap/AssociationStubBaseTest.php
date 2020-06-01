<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 8/03/20
 * Time: 11:31 AM
 */

namespace AlgoWeb\PODataLaravel\Orchestra\Tests\Unit\Models\ObjectMap;

use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubMonomorphic;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubPolymorphic;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubRelationType;
use AlgoWeb\PODataLaravel\Orchestra\Tests\TestCase;
use Mockery as m;

class AssociationStubBaseTest extends TestCase
{
    public function testNotCompatibleOnDifferentMorphTypes()
    {
        $foo = new AssociationStubMonomorphic(
            'foo',
            'foo_id',
            [],
            AssociationStubRelationType::NULL_ONE()
        );
        $bar = new AssociationStubPolymorphic(
            'foo',
            'bar_id',
            [],
            AssociationStubRelationType::NULL_ONE()
        );

        $foo = m::mock(AssociationStubMonomorphic::class)->makePartial();
        $foo->shouldReceive('getKeyFieldName')->andReturn('id');
        $foo->shouldReceive('getForeignFieldName')->andReturn('id');
        $foo->shouldReceive('getThroughFieldChain')->andReturn([]);
        $foo->shouldReceive('isOk')->andReturn(true);

        $bar = m::mock(AssociationStubPolymorphic::class)->makePartial();
        $bar->shouldReceive('getKeyFieldName')->andReturn('id');
        $bar->shouldReceive('getForeignFieldName')->andReturn('id');
        $bar->shouldReceive('getThroughFieldChain')->andReturn([]);
        $bar->shouldReceive('isOk')->andReturn(true);

        $this->assertNotEquals($foo->morphicType(), $bar->morphicType());
        $this->assertFalse($foo->isCompatible($bar));
        $this->assertFalse($bar->isCompatible($foo));
    }

    public function stringInputProvider(): array
    {
        $result = [];
        $result[] = [null, false];
        $result[] = [new \stdClass, false];
        $result[] = ['', false];
        $result[] = ['hiver', true];

        return $result;
    }

    /**
     * @dataProvider stringInputProvider
     *
     * @param $input
     * @param bool $expected
     * @throws \ReflectionException
     */
    public function testCheckStringInput($input, bool $expected)
    {
        $type = AssociationStubRelationType::NULL_ONE();
        $foo = new AssociationStubMonomorphic('', '', [], $type);

        $reflec = new \ReflectionClass($foo);
        $method = $reflec->getMethod('checkStringInput');
        $method->setAccessible(true);

        $actual = $method->invokeArgs($foo, [$input]);
        $this->assertEquals($expected, $actual);
    }
}
