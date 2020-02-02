<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 1/02/20
 * Time: 3:03 PM
 */

namespace AlgoWeb\PODataLaravel\Orchestra\Tests\Unit;

use AlgoWeb\PODataLaravel\Orchestra\Tests\Models\OrchestraTestModel;
use AlgoWeb\PODataLaravel\Orchestra\Tests\TestCase;
use AlgoWeb\PODataLaravel\Providers\MetadataProvider;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\App;

class InfrastructureTest extends TestCase
{
    public function testCanSaveOrchestraTestModel()
    {
        $foo = new OrchestraTestModel();
        $this->assertTrue($foo->save());

        $this->assertEquals(1, OrchestraTestModel::count());
    }

    public function testCanGetServiceDoc()
    {
        $url = 'odata.svc/';

        $result = $this->get($url);
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertContains('OrchestraTestModel', $result->getContent());
    }

    public function testCanGetServiceMetadata()
    {
        $url = 'odata.svc/$metadata';

        $result = $this->get($url);
        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testGetCandidateModels()
    {

        $app = App::make('app');
        $foo = new MetadataProvider($app);

        $reflec = new \ReflectionClass($foo);
        $prop = new \ReflectionMethod($foo, 'getCandidateModels');
        $prop->setAccessible(true);
        $cand = $prop->invoke($foo);
        $this->assertTrue(0 < count($cand), 'Candidate model list empty');
    }
}
