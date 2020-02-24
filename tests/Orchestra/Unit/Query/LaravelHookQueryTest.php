<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 24/02/20
 * Time: 2:08 PM
 */

namespace AlgoWeb\PODataLaravel\Orchestra\Tests\Unit\Query;

use AlgoWeb\PODataLaravel\Orchestra\Tests\Models\OrchestraBelongsToManyTestModel;
use AlgoWeb\PODataLaravel\Orchestra\Tests\Models\OrchestraBelongsToTestModel;
use AlgoWeb\PODataLaravel\Orchestra\Tests\Models\OrchestraHasManyTestModel;
use AlgoWeb\PODataLaravel\Orchestra\Tests\Models\OrchestraHasManyThroughTestModel;
use AlgoWeb\PODataLaravel\Orchestra\Tests\Query\DummyQuery;
use AlgoWeb\PODataLaravel\Orchestra\Tests\TestCase;
use AlgoWeb\PODataLaravel\Query\LaravelHookQuery;
use Mockery as m;
use POData\Providers\Metadata\ResourceSet;

class LaravelHookQueryTest extends TestCase
{
    public function testVerifyHookSingleModelQueuesSourceAndTargetFromParent()
    {
        $over = new DummyQuery();
        $this->assertEquals(0, count($over->getTouchList()));
        $over->startTransaction(true);
        $source = m::mock(ResourceSet::class)->makePartial();

        $sourceInstance = new OrchestraHasManyTestModel();
        $targetInstance = new OrchestraBelongsToTestModel();
        $propName = 'children';

        $foo = new LaravelHookQuery();

        $result = $foo->hookSingleModel($source, $sourceInstance, $source, $targetInstance, $propName);

        $this->assertEquals(2, count($over->getTouchList()));
    }

    public function testVerifyHookSingleModelQueuesSourceAndTargetFromChild()
    {
        $over = new DummyQuery();
        $this->assertEquals(0, count($over->getTouchList()));
        $over->startTransaction(true);
        $source = m::mock(ResourceSet::class)->makePartial();

        $sourceInstance = new OrchestraBelongsToTestModel();
        $targetInstance = new OrchestraHasManyTestModel();
        $propName = 'parent';

        $foo = new LaravelHookQuery();

        $result = $foo->hookSingleModel($source, $sourceInstance, $source, $targetInstance, $propName);

        $this->assertEquals(2, count($over->getTouchList()));
    }

    public function testVerifyUnHookSingleModelQueuesSourceAndTargetFromChild()
    {
        $over = new DummyQuery();
        $this->assertEquals(0, count($over->getTouchList()));
        $over->startTransaction(true);
        $source = m::mock(ResourceSet::class)->makePartial();

        $sourceInstance = new OrchestraBelongsToTestModel();
        $targetInstance = new OrchestraHasManyTestModel();
        $propName = 'parent';

        $foo = new LaravelHookQuery();

        $result = $foo->unhookSingleModel($source, $sourceInstance, $source, $targetInstance, $propName);

        $this->assertEquals(2, count($over->getTouchList()));
    }

    public function testVerifyUnHookSingleModelDoesNotQueueSourceAndTargetFromGrandparent()
    {
        $over = new DummyQuery();
        $this->assertEquals(0, count($over->getTouchList()));
        $over->startTransaction(true);
        $source = m::mock(ResourceSet::class)->makePartial();

        $sourceInstance = new OrchestraHasManyThroughTestModel();
        $targetInstance = new OrchestraBelongsToTestModel();
        $propName = 'grandchildren';

        $foo = new LaravelHookQuery();

        $result = $foo->unhookSingleModel($source, $sourceInstance, $source, $targetInstance, $propName);

        $this->assertEquals(0, count($over->getTouchList()));
    }

    public function testVerifyUnHookSingleModelDoesNotQueueSourceAndTargetFromManyToMany()
    {
        $over = new DummyQuery();
        $this->assertEquals(0, count($over->getTouchList()));
        $over->startTransaction(true);
        $source = m::mock(ResourceSet::class)->makePartial();

        $sourceInstance = new OrchestraBelongsToManyTestModel();
        $targetInstance = new OrchestraBelongsToManyTestModel();
        $propName = 'children';

        $foo = new LaravelHookQuery();

        $result = $foo->unhookSingleModel($source, $sourceInstance, $source, $targetInstance, $propName);

        $this->assertEquals(2, count($over->getTouchList()));
    }
}
