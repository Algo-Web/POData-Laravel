<?php

namespace AlgoWeb\PODataLaravel\Serialisers;

use AlgoWeb\PODataLaravel\Models\TestCase as TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Mockery as m;
use Symfony\Component\HttpFoundation\HeaderBag;

class SerialiserTestBase extends TestCase
{
    protected function setUpSchemaFacade()
    {
        $schema = Schema::getFacadeRoot();
        $schema->shouldReceive('hasTable')->withArgs([config('database.migrations')])->andReturn(true);
        $schema->shouldReceive('hasTable')->andReturn(true);
        $schema->shouldReceive('getColumnListing')->andReturn([]);

        $cacheStore = Cache::getFacadeRoot();
        $cacheStore->shouldReceive('get')->withArgs(['metadata'])->andReturn(null)->once();
    }

    /**
     * @return m\Mock
     */
    protected function setUpRequest()
    {
        $this->setUpSchemaFacade();
        $request = m::mock(Request::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $request->initialize();
        $request->headers = new HeaderBag(['CONTENT_TYPE' => 'application/atom+xml']);
        $request->setMethod('GET');
        $request->shouldReceive('getBaseUrl')->andReturn('http://localhost/');
        $request->shouldReceive('getQueryString')->andReturn('');
        $request->shouldReceive('getHost')->andReturn('localhost');
        $request->shouldReceive('isSecure')->andReturn(false);
        $request->shouldReceive('getPort')->andReturn(80);
        return $request;
    }
}
