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
use Illuminate\Foundation\Testing\DatabaseMigrations;

class InfrastructureTest extends TestCase
{
    use DatabaseMigrations;

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
    }
}
