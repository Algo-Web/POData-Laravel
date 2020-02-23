<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 21/02/20
 * Time: 2:15 AM.
 */
namespace AlgoWeb\PODataLaravel\Orchestra\Tests\Serialisers;

use AlgoWeb\PODataLaravel\Orchestra\Tests\Models\OrchestraTestModel;
use AlgoWeb\PODataLaravel\Orchestra\Tests\TestCase;
use AlgoWeb\PODataLaravel\Serialisers\SerialiserLowLevelWriters;
use AlgoWeb\PODataLaravel\Serialisers\SerialiserUtilities;
use Mockery as m;
use POData\Common\InvalidOperationException;
use POData\Providers\Metadata\IMetadataProvider;
use POData\Providers\Metadata\ResourceEntityType;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\Type\StringType;
use POData\Providers\Query\QueryResult;

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
        $rType->shouldReceive('getName')->andReturn('name');
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
        $rType->shouldReceive('getName')->andReturn('name');
        $rType->shouldReceive('getKeyProperties')->andReturn(['id' => $keyProp])->once();

        $model = new OrchestraTestModel();

        $this->expectException(InvalidOperationException::class);
        $this->expectExceptionMessage('$keyType not instanceof IType');

        SerialiserUtilities::getEntryInstanceKey($model, $rType, 'container');
    }

    public function testCheckElementsInputEmptyArray()
    {
        $entry = new QueryResult();
        $entry->results = [];
        $entry->hasMore = true;

        SerialiserUtilities::checkElementsInput($entry);
        $this->assertFalse($entry->hasMore);
    }

    public function testCheckElementsInputEmptyCollection()
    {
        $entry = new QueryResult();
        $entry->results = collect([]);
        $entry->hasMore = true;

        SerialiserUtilities::checkElementsInput($entry);
        $this->assertFalse($entry->hasMore);
    }

    public function testGetConcreteTypeFromAbstractTypeFirstMatchWins()
    {
        $absType = m::mock(ResourceEntityType::class)->makePartial();
        $absType->shouldReceive('isAbstract')->andReturn(true)->atLeast(1);

        $conc1 = m::mock(ResourceEntityType::class)->makePartial();
        $conc1->shouldReceive('isAbstract')->andReturn(false)->twice();
        $conc1->shouldReceive('getInstanceType->getName')->andReturn('payload')->once();

        $conc2 = m::mock(ResourceEntityType::class)->makePartial();
        $conc2->shouldReceive('isAbstract')->andReturn(false)->once();
        $conc2->shouldReceive('getInstanceType->getName')->andReturn('payload')->never();

        $meta = m::mock(IMetadataProvider::class);
        $meta->shouldReceive('getDerivedTypes')->withArgs([$absType])->andReturn([$conc1, $conc2]);

        $result = SerialiserUtilities::getConcreteTypeFromAbstractType($absType, $meta, 'payload');
    }

    public function testWriteComplexValueBadPropertyType()
    {
        $keyProp = m::mock(ResourceProperty::class);
        $keyProp->shouldReceive('getInstanceType')->andReturn(new \stdClass);
        $keyProp->shouldReceive('getKind')->andReturn(16);
        $keyProp->shouldReceive('getName')->andReturn('property');

        $rType = m::mock(ResourceType::class)->makePartial();
        $rType->shouldReceive('getName')->andReturn('name');
        $rType->shouldReceive('getAllProperties')->andReturn(['property' => $keyProp])->once();

        $result = new \stdClass();
        $instanceColl = [];

        $this->expectException(InvalidOperationException::class);
        $this->expectExceptionMessage('stdClass');

        SerialiserLowLevelWriters::writeComplexValue($rType, $result, 'property', $instanceColl);
    }

    public function testWriteComplexValueBadResourcePropertyType()
    {
        $iType = new StringType();

        $keyProp = m::mock(ResourceProperty::class);
        $keyProp->shouldReceive('getInstanceType')->andReturn($iType);
        $keyProp->shouldReceive('getKind')->andReturn(16);
        $keyProp->shouldReceive('getName')->andReturn('property');
        $keyProp->shouldReceive('getResourceType->getInstanceType')->andReturn(new \stdClass)->once();

        $rType = m::mock(ResourceType::class)->makePartial();
        $rType->shouldReceive('getName')->andReturn('name');
        $rType->shouldReceive('getAllProperties')->andReturn(['property' => $keyProp])->once();

        $result = new \stdClass();
        $instanceColl = [];

        $this->expectException(InvalidOperationException::class);
        $this->expectExceptionMessage('stdClass');

        SerialiserLowLevelWriters::writeComplexValue($rType, $result, 'property', $instanceColl);
    }
}
