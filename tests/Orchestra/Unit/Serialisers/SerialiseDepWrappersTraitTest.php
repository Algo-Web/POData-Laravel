<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 28/02/20
 * Time: 11:56 AM.
 */
namespace AlgoWeb\PODataLaravel\Orchestra\Tests\Serialisers;

use AlgoWeb\PODataLaravel\Orchestra\Tests\TestCase;
use AlgoWeb\PODataLaravel\Serialisers\IronicSerialiser;
use Illuminate\Support\Facades\App;
use Mockery as m;
use POData\Providers\Query\QueryType;
use POData\SimpleDataService;
use POData\UriProcessor\RequestDescription;

class SerialiseDepWrappersTraitTest extends TestCase
{
    /**
     * @throws \POData\Common\InvalidOperationException
     * @throws \Exception
     */
    public function testSetRequest()
    {
        $request            = m::mock(RequestDescription::class);
        $request->queryType = QueryType::COUNT();

        $service = m::mock(SimpleDataService::class)->makePartial();
        $service->shouldReceive('getHost->getAbsoluteServiceUri->getUrlAsString')
            ->andReturn('http://localhost');

        /** @var IronicSerialiser $foo */
        $foo = new IronicSerialiser($service);

        $foo->setRequest($request);

        $new = $foo->getStack()->getRequest();
        $this->assertEquals(QueryType::COUNT(), $new->queryType);
    }

    /**
     * @throws \POData\Common\InvalidOperationException
     * @throws \Exception
     */
    public function testLoadStackIfEmpty()
    {
        $request            = m::mock(RequestDescription::class);
        $request->queryType = QueryType::COUNT();
        $request->shouldReceive('getTargetResourceType->getName')->andReturn('NARF!');

        $service = m::mock(SimpleDataService::class)->makePartial();
        $service->shouldReceive('getHost->getAbsoluteServiceUri->getUrlAsString')
            ->andReturn('http://localhost');

        $foo = new DummyIronicSerialiser($service, $request);

        $expectedOriginal = [];
        $original         = $foo->getLightStack();
        $this->assertEquals($expectedOriginal, $original);

        $expectedActual = [['type' => 'NARF!', 'property' => 'NARF!', 'count' => 1]];
        $foo->loadStackIfEmpty();
        $actual = $foo->getLightStack();
        $this->assertEquals($expectedActual, $actual);
    }
}
