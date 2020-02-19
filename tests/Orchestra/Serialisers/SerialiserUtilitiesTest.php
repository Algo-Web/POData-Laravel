<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 21/02/20
 * Time: 2:15 AM
 */

namespace AlgoWeb\PODataLaravel\Orchestra\Tests\Serialisers;

use AlgoWeb\PODataLaravel\Orchestra\Tests\Models\OrchestraTestModel;
use AlgoWeb\PODataLaravel\Orchestra\Tests\TestCase;
use AlgoWeb\PODataLaravel\Serialisers\SerialiserUtilities;
use Mockery as m;
use POData\Common\InvalidOperationException;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourceType;

class SerialiserUtilitiesTest extends TestCase
{
    /**
     * @throws InvalidOperationException
     * @throws \POData\Common\ODataException
     * @throws \ReflectionException
     */
    public function testGetEntryInstanceKeyWhenNoKeyProperties()
    {
        $rType = m::mock(ResourceType::class)->makePartial();
        $rType->shouldReceive("getName")->andReturn('name');
        $rType->shouldReceive('getKeyProperties')->andReturn([])->once();

        $model = new OrchestraTestModel();

        $this->expectException(InvalidOperationException::class);
        $this->expectExceptionMessage('count($keyProperties) == 0');

        SerialiserUtilities::getEntryInstanceKey($model, $rType, 'container');
    }

    /**
     * @throws InvalidOperationException
     * @throws \POData\Common\ODataException
     * @throws \ReflectionException
     */
    public function testGetEntryInstanceKeyWhenBadKeyProperties()
    {
        $keyProp = m::mock(ResourceProperty::class);
        $keyProp->shouldReceive('getInstanceType')->andReturnNull();

        $rType = m::mock(ResourceType::class)->makePartial();
        $rType->shouldReceive("getName")->andReturn('name');
        $rType->shouldReceive('getKeyProperties')->andReturn(['id' => $keyProp])->once();

        $model = new OrchestraTestModel();

        $this->expectException(InvalidOperationException::class);
        $this->expectExceptionMessage('$keyType not instanceof IType');

        SerialiserUtilities::getEntryInstanceKey($model, $rType, 'container');
    }
}
