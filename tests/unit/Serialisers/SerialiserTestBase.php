<?php

namespace AlgoWeb\PODataLaravel\Serialisers;

use AlgoWeb\PODataLaravel\Models\ObjectMap\Map;
use AlgoWeb\PODataLaravel\Models\TestCase as TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Mockery as m;
use Symfony\Component\HttpFoundation\HeaderBag;

class SerialiserTestBase extends TestCase
{
    protected function setUpSchemaFacade()
    {
        $map = new Map();
        App::instance('objectmap', $map);
        $schema = Schema::getFacadeRoot();
        Schema::shouldReceive('hasTable')->withArgs([config('database.migrations')])->andReturn(true);
        Schema::shouldReceive('hasTable')->andReturn(true);
        Schema::shouldReceive('getColumnListing')->andReturn([]);

        $cacheStore = Cache::getFacadeRoot();
        Cache::shouldReceive('get')->withArgs(['metadata'])->andReturn(null)->once();
    }

    /**
     * @return m\Mock
     */
    protected function setUpRequest()
    {
        $map = new Map();
        App::instance('objectmap', $map);
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
