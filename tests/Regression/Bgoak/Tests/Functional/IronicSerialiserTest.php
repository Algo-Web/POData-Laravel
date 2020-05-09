<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 9/02/20
 * Time: 6:42 PM.
 */
namespace Tests\Regression\AlgoWeb\PODataLaravel\Bgoak\Tests\Functional;

use AlgoWeb\PODataLaravel\Serialisers\IronicSerialiser;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Mockery as m;
use POData\OperationContext\ServiceHost;
use AlgoWeb\PODataLaravel\OperationContext\Web\Illuminate\IlluminateOperationContext;
use POData\Providers\Query\QueryResult;
use POData\SimpleDataService;
use Tests\Regression\AlgoWeb\PODataLaravel\Bgoak\Models\Address;
use Tests\Regression\AlgoWeb\PODataLaravel\Bgoak\Models\City;
use Tests\Regression\AlgoWeb\PODataLaravel\Bgoak\Models\Person;
use Tests\Regression\AlgoWeb\PODataLaravel\TestCase;

class IronicSerialiserTest extends TestCase
{
    //use DatabaseMigrations;

    public function setUp(): void
    {
        parent::setUp();
        $baz           = new City();
        $baz->cityId   = 'baz';
        $baz->name     = 'baz';
        $baz->postcode = 'WTF 0MG';
        $baz->country  = 'The Old Dart';
        $this->assertTrue($baz->save());

        $foo            = new Address();
        $foo->addressId = 'foo';
        $foo->cityid    = 'baz';
        $foo->street    = 'street';
        $this->assertTrue($foo->save());

        $bar            = new Person();
        $bar->personId  = 'bar';
        $bar->addressid = 'foo';
        $bar->name      = 'Zoidberg';
        $bar->givenname = 'John';
        $this->assertTrue($bar->save());
    }

    /**
     * @throws \Exception
     */
    public function testSerialiseThreeDeepBelongsToChange()
    {
        $url = 'http://localhost/odata.svc/People?$expand=Address/City';
        $foo = Person::with(['Address', 'Address.City'])->findOrFail('bar');

        $accept = 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';

        $request = m::mock(Request::class)->makePartial();
        $request->shouldReceive('getMethod')->andReturn('GET');
        $request->shouldReceive('fullUrl')->andReturn($url);
        $request->shouldReceive('all')->andReturn(['$expand' => 'Address/City']);
        $request->shouldReceive('header')->withArgs(['DATASERVICEVERSION'])->andReturnNull();
        $request->shouldReceive('header')->withArgs(['MAXDATASERVICEVERSION'])->andReturnNull();
        $request->shouldReceive('header')->withArgs(['IF_MATCH'])->andReturnNull();
        $request->shouldReceive('header')->withArgs(['IF_NONE_MATCH'])->andReturnNull();
        $request->shouldReceive('header')->withArgs(['ACCEPT'])->andReturn($accept);

        $context = new IlluminateOperationContext($request);
        $host    = new ServiceHost($context, $request);
        $host->setServiceUri('/odata.svc/');

        $query = App::make('odataquery');
        $meta  = App::make('metadata');

        $service = new SimpleDataService($query, $meta, $host);
        $cereal  = new IronicSerialiser($service);
        $service = new SimpleDataService($query, $meta, $host, $cereal);

        $service->handleRequest();

        $obj          = new QueryResult();
        $obj->results = $foo;

        $result = $cereal->writeTopLevelElement($obj);
        $this->assertEquals(1, count($result->links));
        $link = $result->links[0]->expandedResult;
        $this->assertEquals(2, count($link->links));
        $links    = $link->links;
        $targLink = null;
        foreach ($links as $link) {
            if ($link->title !== 'City') {
                continue;
            }
            $targLink = $link;
            break;
        }
        $this->assertNotNull($targLink);
        $this->assertTrue($targLink->isExpanded);
        $this->assertNotNull($targLink->expandedResult);
    }
}
