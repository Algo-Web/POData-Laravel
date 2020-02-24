<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 23/02/20
 * Time: 10:30 PM
 */

namespace AlgoWeb\PODataLaravel\Orchestra\Tests\Unit\Query;

use AlgoWeb\PODataLaravel\Orchestra\Tests\Controllers\OrchestraTestController;
use AlgoWeb\PODataLaravel\Orchestra\Tests\Models\OrchestraTestModel;
use AlgoWeb\PODataLaravel\Orchestra\Tests\Query\DummyBulkQuery;
use AlgoWeb\PODataLaravel\Orchestra\Tests\Requests\TestBulkCreateRequest;
use AlgoWeb\PODataLaravel\Orchestra\Tests\Requests\TestRequest;
use AlgoWeb\PODataLaravel\Orchestra\Tests\TestCase;
use AlgoWeb\PODataLaravel\Query\LaravelQuery;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;
use Mockery as m;
use POData\Common\InvalidOperationException;
use POData\Common\ODataException;
use POData\Providers\Metadata\ResourceSet;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\KeyDescriptor;

class LaravelBulkQueryTest extends TestCase
{
    public function testInputPrepEmptyInput()
    {
        $paramList = [];
        $data = [];
        $keyDesc = null;

        $query = m::mock(LaravelQuery::class)->makePartial();
        $foo = new DummyBulkQuery($query);

        $expected = [];
        $actual = $foo->prepareBulkRequestInput($paramList, $data, $keyDesc);
        $this->assertEquals($expected, $actual);
    }

    public function testOnlyNonRequestInputIsCreate()
    {
        $paramList = ['foo'];
        $data = [11];
        $keyDesc = null;

        $query = m::mock(LaravelQuery::class)->makePartial();
        $foo = new DummyBulkQuery($query);

        $expected = [];
        $actual = $foo->prepareBulkRequestInput($paramList, $data, $keyDesc);
        $this->assertEquals($expected, $actual);
    }

    public function testOnlyRequestInputIsUpdate()
    {
        $bitz = ['name' => ['name'],
            'added_at' => ['2012-11-10'],
            'weight' => [11],
            'code' => ['up to 11'],
            'success' => [true]];

        $rawDesc = m::mock(KeyDescriptor::class);
        $rawDesc->shouldReceive('getNamedValues')->andReturn($bitz);

        $spec = ['type' => TestRequest::class, 'isRequest' => true];

        $paramList = [$spec];
        $data = [11];
        $keyDesc = [$rawDesc];

        $query = m::mock(LaravelQuery::class)->makePartial();
        $foo = new DummyBulkQuery($query);

        $bulkData = ['data' => [11], 'keys' => [['name' => 'name', 'added_at' => '2012-11-10', 'weight' => 11,
            'code' => 'up to 11', 'success' => true]]];
        $exp = new TestRequest();
        $exp->setMethod('PUT');
        $exp->request = new \Symfony\Component\HttpFoundation\ParameterBag($bulkData);

        $expected = [$exp];
        $actual = $foo->prepareBulkRequestInput($paramList, $data, $keyDesc);
        $this->assertEquals($expected, $actual);
    }

    public function testOnlyNonRequestInputIsUpdateBadKeyDescriptors()
    {
        $spec = ['type' => TestRequest::class, 'isRequest' => true];

        $paramList = [$spec];
        $data = [11];
        $keyDesc = [new \stdClass];

        $query = m::mock(LaravelQuery::class)->makePartial();
        $foo = new DummyBulkQuery($query);

        $this->expectException(InvalidOperationException::class);

        $foo->prepareBulkRequestInput($paramList, $data, $keyDesc);
    }

    public function testBulkCustomThrows500Afterwards()
    {
        $paramList = [
            'method' => 'storeBulkTestModel',
            'controller' => OrchestraTestController::class,
            'parameters' => ['request' =>
                ['name' => 'request', 'type' => TestBulkCreateRequest::class, 'isRequest' => true]]
        ];

        $date = new \DateTime('2017-01-01');

        $rawData = [];
        $rawData[] = ['name' => 'name', 'added_at' => $date, 'weight' => 0, 'code' => '42', 'success' => true];
        $rawData[] = ['name' => 'name', 'added_at' => $date, 'weight' => 0, 'code' => '42', 'success' => true];

        $query = m::mock(LaravelQuery::class)->makePartial();
        $foo = new DummyBulkQuery($query);

        $rSet = m::mock(ResourceSet::class);
        $rSet->shouldReceive('getResourceType->getInstanceType->getName')->andReturn(OrchestraTestModel::class);

        $model = m::mock(OrchestraTestModel::class)->makePartial();
        $model->shouldReceive('findMany')->andThrow(\Exception::class);
        App::instance(OrchestraTestModel::class, $model);

        try {
            $foo->processBulkCustom($rSet, $rawData, $paramList, 'bulkCreated');
        } catch (ODataException $e) {
            $this->assertEquals(500, $e->getStatusCode());
        }
    }

    public function testProcessOutputEmptyResponse()
    {
        $response = new JsonResponse();

        $query = m::mock(LaravelQuery::class)->makePartial();
        $foo = new DummyBulkQuery($query);

        $this->expectException(ODataException::class);
        $this->expectExceptionMessage('at least one of id, status and/or errors fields.');

        $foo->createUpdateDeleteProcessOutput($response);
    }


    public function processOutputProvider() : array
    {
        $result = [];
        $result[] = [['id' => 1]];
        $result[] = [['status' => 'OK']];
        $result[] = [['errors' => null]];
        $result[] = [['id' => 1, 'status' => 'OK']];
        $result[] = [['id' => 1, 'errors' => null]];
        $result[] = [['status' => 'OK', 'errors' => null]];

        return $result;
    }

    /**
     * @dataProvider processOutputProvider
     *
     * @param array $data
     * @throws ODataException
     */
    public function testProcessOutputBadResponse($data)
    {
        $response = new JsonResponse();
        $response->setData($data);

        $query = m::mock(LaravelQuery::class)->makePartial();
        $foo = new DummyBulkQuery($query);

        $this->expectException(ODataException::class);
        $this->expectExceptionMessage('at least one of id, status and/or errors fields.');

        $foo->createUpdateDeleteProcessOutput($response);
    }
}
