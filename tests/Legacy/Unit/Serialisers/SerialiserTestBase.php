<?php

namespace Tests\Legacy\AlgoWeb\PODataLaravel\Unit\Serialisers;

use AlgoWeb\PODataLaravel\Models\ObjectMap\Map;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Mockery as m;
use Symfony\Component\HttpFoundation\HeaderBag;
use Tests\Legacy\AlgoWeb\PODataLaravel\TestCase as TestCase;

class SerialiserTestBase extends TestCase
{
    protected function setUpSchemaFacade()
    {
        $map = new Map();
        App::instance('objectmap', $map);
        $schema = Schema::getFacadeRoot();
        $schema->shouldReceive('hasTable')->withArgs([config('database.migrations')])->andReturn(true);
        $schema->shouldReceive('hasTable')->andReturn(true);
        $schema->shouldReceive('getColumnListing')->andReturn([]);

        $cacheStore = Cache::getFacadeRoot();
        Cache::shouldReceive('get')->withArgs(['metadata'])->andReturn(null);
        Cache::shouldReceive('get')->withArgs(['objectmap'])->andReturn(null);
        Cache::shouldReceive('forget')->withArgs(['metadataControllers'])->andReturn(null);
        Cache::shouldReceive('forget')->withArgs(['metadata'])->andReturn(null);
        Cache::shouldReceive('forget')->withArgs(['objectmap'])->andReturn(null);
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
