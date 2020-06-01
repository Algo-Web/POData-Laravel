<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 7/03/20
 * Time: 10:55 AM
 */

namespace AlgoWeb\PODataLaravel\Orchestra\Tests\Unit\Query;

use AlgoWeb\PODataLaravel\Orchestra\Tests\Models\OrchestraTestModel;
use AlgoWeb\PODataLaravel\Orchestra\Tests\TestCase;
use AlgoWeb\PODataLaravel\Query\LaravelWriteQuery;
use Illuminate\Http\JsonResponse;
use Mockery as m;
use POData\Common\ODataException;
use POData\Providers\Metadata\ResourceSet;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\KeyDescriptor;

class LaravelWriteQueryTest extends TestCase
{
    public function testVerifyDeleteResourceGetsCorrectParms()
    {
        $model = new OrchestraTestModel();
        $model->id = 42;

        $resourceSet = m::mock(ResourceSet::class)->makePartial();
        $resourceSet->shouldReceive('getResourceType->getInstanceType->getName')
            ->andReturn(OrchestraTestModel::class);

        $foo = m::mock(LaravelWriteQuery::class)->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('createUpdateDeleteCore')
            ->with(m::type(OrchestraTestModel::class), ['id' => 42], OrchestraTestModel::class, 'delete')
            ->andReturn(['id' => 42])->once();
        $foo->shouldReceive('deleteResource')->passthru();
        $foo->shouldReceive('unpackSourceEntity')->andReturn($model);

        $result = $foo->deleteResource($resourceSet, $model);
        $this->assertTrue($result);
    }

    public function testDefaultShouldUpdateIsFalse()
    {
        $resourceSet = m::mock(ResourceSet::class)->makePartial();
        $model = new OrchestraTestModel();
        $keyDesc = m::mock(KeyDescriptor::class)->makePartial();
        $data = [];

        $foo = m::mock(LaravelWriteQuery::class)->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('createUpdateMainWrapper')->passthru()->once();
        $foo->shouldReceive('unpackSourceEntity')->passthru()->once();
        $foo->shouldReceive('createUpdateCoreWrapper')
            ->with(m::any(), m::any(), 'update', m::any(), false)->once();
        $foo->shouldReceive('updateResource')->passthru();

        $foo->updateResource($resourceSet, $model, $keyDesc, $data);
    }

    public function testDefaultShouldUpdateIsFalseTopLevel()
    {
        $resourceSet = m::mock(ResourceSet::class)->makePartial();
        $model = new OrchestraTestModel();
        $keyDesc = m::mock(KeyDescriptor::class)->makePartial();
        $data = [];

        $foo = m::mock(LaravelWriteQuery::class)->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('createUpdateMainWrapper')
            ->with(m::any(), m::any(), m::any(), 'update', false)->once();
        $foo->shouldReceive('updateResource')->passthru();

        $foo->updateResource($resourceSet, $model, $keyDesc, $data);
    }

    public function processOutputProvider(): array
    {
        $result = [];
        $result[] = [ [], false];
        $result[] = [ ['id'], false];
        $result[] = [ ['status'], false];
        $result[] = [ ['errors'], false];
        $result[] = [ ['id', 'status'], false];
        $result[] = [ ['id', 'errors'], false];
        $result[] = [ ['status', 'errors'], false];
        $result[] = [ ['id', 'status', 'errors'], true];

        return $result;
    }

    /**
     * @dataProvider processOutputProvider
     *
     * @param array $keys
     * @param bool $pass
     * @throws \ReflectionException
     */
    public function testCreateUpdateDeleteProcessOutput(array $keys, bool $pass)
    {
        $query = new LaravelWriteQuery();

        $reflec = new \ReflectionClass($query);
        $method = $reflec->getMethod('createUpdateDeleteProcessOutput');
        $method->setAccessible(true);

        if (!$pass) {
            $this->expectException(ODataException::class);
            $this->expectExceptionMessage('Controller response array missing at least one of id, status and/or errors fields.');
        }

        $data = array_flip($keys);
        $response = m::mock(JsonResponse::class)->makePartial();
        $response->shouldReceive('getData')->withArgs(['true'])->andReturn($data);

        $method->invokeArgs($query, [$response]);

        if ($pass) {
            $this->assertTrue(true);
        }
    }
}
