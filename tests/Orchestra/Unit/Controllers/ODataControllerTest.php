<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 10/05/20
 * Time: 5:31 AM
 */

namespace AlgoWeb\PODataLaravel\Orchestra\Tests\Unit\Controllers;

use AlgoWeb\PODataLaravel\Controllers\ODataController;
use AlgoWeb\PODataLaravel\Orchestra\Tests\TestCase;
use Illuminate\Http\Request;
use Mockery as m;
use POData\Configuration\ServiceConfiguration;

class ODataControllerTest extends TestCase
{
    /**
     * @throws \ReflectionException
     */
    public function testIndexWithDefaultPageSize()
    {
        $config = m::mock(ServiceConfiguration::class)->makePartial();
        $config->shouldReceive('setEntitySetPageSize')->withArgs(['*', 400])->passthru()->once();
        $config->shouldReceive('getLineEndings')->andReturn(PHP_EOL);
        $config->shouldReceive('getPrettyOutput')->andReturn(true);
        $config->shouldReceive('setAcceptCountRequests')->withArgs(['true'])->passthru()->once();
        $config->shouldReceive('setAcceptProjectionRequests')->withArgs(['true'])->passthru()->once();
        $config->setMaxResultsPerCollection(PHP_INT_MAX);

        $foo = m::mock(ODataController::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('makeConfig')->andReturn($config)->once();

        $req = m::mock(Request::class);
        $req->shouldReceive('getMethod')->andReturn('GET');
        $req->shouldReceive('fullUrl')->passthru();
        $req->shouldReceive('getQueryString')->andReturn('');
        $req->shouldReceive('getPathInfo')->andReturn('/abm-master/public/odata.svc');
        $req->shouldReceive('getBaseUrl')->andReturn('http://192.168.2.1/abm-master/public/odata.svc');
        $req->shouldReceive('fullUrl')->andReturn('http://192.168.2.1/abm-master/public/odata.svc');
        $req->shouldReceive('url')->andReturn('http://192.168.2.1/abm-master/public/odata.svc');
        $req->shouldReceive('getUri')->andReturn('http://192.168.2.1/abm-master/public/odata.svc');
        $req->shouldReceive('header')->andReturn(null);

        $foo->index($req);
    }
}
