<?php

namespace AlgoWeb\PODataLaravel\Controllers;

use AlgoWeb\PODataLaravel\Models\TestCase as TestCase;
use AlgoWeb\PODataLaravel\Query\LaravelQuery;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Mockery as m;
use POData\Common\ODataException;
use POData\Providers\Metadata\SimpleMetadataProvider;
use Symfony\Component\HttpFoundation\HeaderBag;

/**
 * Generated Test Class.
 */
class ODataControllerTest extends TestCase
{
    /**
     * @var \AlgoWeb\PODataLaravel\Controllers\ODataController
     */
    protected $object;
    protected $mock;
    protected $query;
    protected $meta;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        parent::setUp();
//        $this->object  = \Mockery::mock('\AlgoWeb\PODataLaravel\Controllers\ODataController')->makePartial();
        $this->getMockBuilder('App\Http\Controllers\Controller')->getMock();
//        $this->mock = \Mockery::mock('App\Http\Controllers\Controller', 'Post');
        $this->object  = \Mockery::mock('\AlgoWeb\PODataLaravel\Controllers\ODataController')
            ->makePartial()->shouldAllowMockingProtectedMethods();
        $this->query = m::mock(LaravelQuery::class)->makePartial();
        $this->meta = m::mock(SimpleMetadataProvider::class)->makePartial();
        App::instance('odataquery', $this->query);
        App::instance('metadata', $this->meta);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    public function tearDown()
    {
        parent::tearDown();
    }

    public function testIndexMalformedBaseService()
    {
        $this->object->shouldReceive('getIsDumping')->passthru()->once();
        $request = m::mock(Request::class)->makePartial();
        $request->shouldReceive('getMethod')->andReturn('GET');
        $request->shouldReceive('getQueryString')->andReturn('http://192.168.2.1/abm-master/public/odata.svc');
        $request->shouldReceive('getBaseUrl')->andReturn('http://192.168.2.1/abm-master/public');
        $request->initialize();
        $dump = false;

        $expected = 'Malformed base service uri in the configuration file '
                    .'(should end with .svc, there should not be query or fragment in the base service uri)';
        $actual = null;

        try {
            $this->object->index($request, $dump);
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers \AlgoWeb\PODataLaravel\Controllers\ODataController::index
     */
    public function testIndexCallToBaseService()
    {
        $request = m::mock(Request::class)->makePartial();
        $request->shouldReceive('getMethod')->andReturn('GET');
        $request->shouldReceive('getQueryString')->andReturn('');
        $request->shouldReceive('getBaseUrl')->andReturn('http://192.168.2.1/abm-master/public/odata.svc');
        $request->initialize();
        $dump = false;

        $expected = '&lt;?xml version="1.0" encoding="UTF-8" standalone="yes"?&gt;
<service xml:base="http://:http://192.168.2.1/abm-master/public/odata.svc" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:app="http://www.w3.org/2007/app" >
 <workspace>
  <atom:title>Default</atom:title>
 </workspace>
</service>
';

        $result =  $this->object->index($request, $dump);
        $this->assertEquals(200, $result->getStatusCode());
        $actual = $result->getContent();
        //$this->assertEquals($expected, $actual);
    }

    /**
     * @covers \AlgoWeb\PODataLaravel\Controllers\ODataController::index
     */
    public function testIndexCallToBaseServiceDumpSetButNoHeader()
    {
        $knownDate = new Carbon(2017, 6, 15, 15, 14, 19);
        Carbon::setTestNow($knownDate);

        $root = "GET;-;15:17:00;";

        $storage = Storage::getFacadeRoot();
        $storage->shouldReceive('put')->with($root.'request', m::any())->andReturnNull()->once();
        $storage->shouldReceive('put')->with($root.'metadata', m::any())->andReturnNull()->once();
        $storage->shouldReceive('put')->with($root.'response', m::any())->andReturnNull()->once();

        $request = m::mock(Request::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $request->shouldReceive('getMethod')->andReturn('GET');
        $request->shouldReceive('getQueryString')->andReturn('');
        $request->shouldReceive('getBaseUrl')->andReturn('http://192.168.2.1/abm-master/public/odata.svc');
        $request->initialize();
        $dump = true;

        $expected = '&lt;?xml version="1.0" encoding="UTF-8" standalone="yes"?&gt;
<service xml:base="http://:http://192.168.2.1/abm-master/public/odata.svc" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:app="http://www.w3.org/2007/app" >
 <workspace>
  <atom:title>Default</atom:title>
 </workspace>
</service>
';

        $result =  $this->object->index($request, $dump);
        $this->assertEquals(200, $result->getStatusCode());
        $actual = $result->getContent();
        //$this->assertEquals($expected, $actual);
    }

    /**
     * @covers \AlgoWeb\PODataLaravel\Controllers\ODataController::index
     */
    public function testIndexCallToBaseServiceDumpSetButHasHeader()
    {
        $storage = Storage::getFacadeRoot();
        $storage->shouldReceive('put')->with('catchrequest', m::any())->andReturnNull()->once();
        $storage->shouldReceive('put')->with('catchmetadata', m::any())->andReturnNull()->once();
        $storage->shouldReceive('put')->with('catchresponse', m::any())->andReturnNull()->once();

        $request = m::mock(Request::class)->makePartial();
        $request->shouldReceive('getMethod')->andReturn('GET');
        $request->shouldReceive('getQueryString')->andReturn('');
        $request->shouldReceive('getBaseUrl')->andReturn('http://192.168.2.1/abm-master/public/odata.svc');
        $request->shouldReceive('header')->withArgs(['XTest'])->andReturn('catch')->once();
        $request->initialize();
        $dump = true;

        $expected = '&lt;?xml version="1.0" encoding="UTF-8" standalone="yes"?&gt;
<service xml:base="http://:http://192.168.2.1/abm-master/public/odata.svc" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:app="http://www.w3.org/2007/app" >
 <workspace>
  <atom:title>Default</atom:title>
 </workspace>
</service>
';

        $result =  $this->object->index($request, $dump);
        $this->assertEquals(200, $result->getStatusCode());
        $actual = $result->getContent();
        //$this->assertEquals($expected, $actual);
        $headers = $result->headers;
        $this->assertTrue($headers->has('Content-Type'));
        $this->assertFalse($headers->has('Content-Length'));
        $this->assertFalse($headers->has('ETag'));
        $this->assertTrue($headers->has('Cache-Control'));
        $this->assertFalse($headers->has('Last-Modified'));
        $this->assertFalse($headers->has('Location'));
        $this->assertTrue($headers->has('Status'));
        $this->assertFalse($headers->has('StatusCode'));
        $this->assertFalse($headers->has('StatusDesc'));
        $this->assertTrue($headers->has('DataServiceVersion'));
    }
}
