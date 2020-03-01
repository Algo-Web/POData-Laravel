<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 2/02/20
 * Time: 11:42 PM.
 */
namespace AlgoWeb\PODataLaravel\Orchestra\Tests\Functional;

use AlgoWeb\PODataLaravel\Orchestra\Tests\Models\OrchestraTestModel;
use AlgoWeb\PODataLaravel\Orchestra\Tests\TestCase;
use AlgoWeb\PODataLaravel\Providers\MetadataRouteProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\App;
use Mockery as m;

class FilterTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        putenv('APP_DISABLE_AUTH=true');
    }

    public static function tearDownAfterClass(): void
    {
        putenv('APP_DISABLE_AUTH=false');
    }

    public function testCountAndFilterWithNoMatches()
    {
        $names = ['foo', 'bar', 'simon'];

        foreach ($names as $name) {
            $foo = new OrchestraTestModel(['name' => $name]);
            $this->assertTrue($foo->save());
        }

        $url = 'odata.svc/OrchestraTestModels/$count?$filter=name eq \'bruce\'';

        $result = $this->get($url);
        if ($result instanceof TestCase) {
            $this->assertEquals(200, $result->response->getStatusCode());
        } else {
            $this->assertEquals(200, $result->getStatusCode());
            $result->assertDontSee('Error');
        }
    }
}
