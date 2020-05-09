<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 10/05/20
 * Time: 6:28 AM
 */

namespace AlgoWeb\PODataLaravel\Orchestra\Tests\Unit\OperationContext\Web;

use AlgoWeb\PODataLaravel\OperationContext\Web\Illuminate\IncomingIlluminateRequest;
use AlgoWeb\PODataLaravel\Orchestra\Tests\TestCase;
use Illuminate\Http\Request;
use Mockery as m;

class IncomingIlluminateRequestTest extends TestCase
{
    public function requestProvider(): array
    {
        $result = [];
        $result[] = ['foo', 'foo'];
        $result[] = [null, null];
        $result[] = [false, null];
        $result[] = ['', null];
        $result[] = [true, true];

        return $result;
    }

    /**
     * @dataProvider requestProvider
     *
     * @param $input
     * @param $output
     */
    public function testGetRequestHeader($input, $output)
    {
        $req = m::mock(Request::class);
        $req->shouldReceive('header')->andReturn($input);
        $req->shouldReceive('getMethod')->andReturn('GET');

        $request = new IncomingIlluminateRequest($req);

        $expected = $output;
        $actual = $request->getRequestHeader('key');
        $this->assertEquals($expected, $actual);
    }

    public function testGetAllInputEmptyContentDropsThrough()
    {
        $req = m::mock(Request::class);
        $req->shouldReceive('getMethod')->andReturn('GET');
        $req->shouldReceive('all')->andReturn([])->once();
        $req->shouldReceive('getContent')->andReturn(['foobar'])->once();

        $request = new IncomingIlluminateRequest($req);
        $expected = ['foobar'];
        $actual = $request->getAllInput();
        $this->assertEquals($expected, $actual);
    }

    public function testGetAllInputNotEmptyContentNotDropsThrough()
    {
        $req = m::mock(Request::class);
        $req->shouldReceive('getMethod')->andReturn('GET');
        $req->shouldReceive('all')->andReturn(['foobar'])->once();
        $req->shouldReceive('getContent')->andReturn(null)->never();

        $request = new IncomingIlluminateRequest($req);
        $expected = ['foobar'];
        $actual = $request->getAllInput();
        $this->assertEquals($expected, $actual);
    }
}
