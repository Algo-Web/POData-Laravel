<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 24/02/20
 * Time: 3:53 PM.
 */
namespace AlgoWeb\PODataLaravel\Orchestra\Tests\Unit\Providers;

use AlgoWeb\PODataLaravel\Orchestra\Tests\Controllers\OrchestraBadTrait;
use AlgoWeb\PODataLaravel\Orchestra\Tests\Controllers\OrchestraBallastController;
use AlgoWeb\PODataLaravel\Orchestra\Tests\Controllers\OrchestraNonTraitController;
use AlgoWeb\PODataLaravel\Orchestra\Tests\Controllers\OrchestraTestController;
use AlgoWeb\PODataLaravel\Orchestra\Tests\Models\OrchestraTestModel;
use AlgoWeb\PODataLaravel\Orchestra\Tests\Providers\DummyMetadataControllerProvider;
use AlgoWeb\PODataLaravel\Orchestra\Tests\TestCase;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\App;
use Illuminate\Tests\Integration\Queue\Role;
use Mockery as m;
use POData\Common\InvalidOperationException;

class MetadataControllerProviderTest extends TestCase
{
    public function testDontFilterActiveControllers()
    {
        $names    = [OrchestraBallastController::class, OrchestraTestController::class];
        $expected = [new OrchestraBallastController(), new OrchestraTestController()];

        $app = m::mock(Application::class);
        $app->shouldReceive('runningInConsole')->andReturn(true);
        $app->shouldReceive('make')->withArgs([OrchestraTestController::class])
            ->andReturn(new OrchestraTestController());
        $app->shouldReceive('make')->withArgs([OrchestraBallastController::class])
            ->andReturn(new OrchestraBallastController());

        $foo = new DummyMetadataControllerProvider($app);

        $actual = $foo->getCandidateControllers($names);
        $this->assertEquals($expected, $actual);
    }

    public function testFilterControllerWithoutTrait()
    {
        $names    = [OrchestraNonTraitController::class, OrchestraTestController::class];
        $expected = [new OrchestraBallastController(), new OrchestraTestController()];

        $app = m::mock(Application::class);
        $app->shouldReceive('runningInConsole')->andReturn(true);
        $app->shouldReceive('make')->withArgs([OrchestraTestController::class])
            ->andReturn(new OrchestraTestController());
        $app->shouldReceive('make')->withArgs([OrchestraBallastController::class])
            ->andReturn(new OrchestraBallastController());
        $foo = new DummyMetadataControllerProvider($app);

        $actual = $foo->getCandidateControllers($names);
        $this->assertEquals($expected, $actual);
    }

    public function testFilterFromOutsideNamespace()
    {
        $names    = [Role::class, OrchestraTestController::class];
        $expected = [new OrchestraBallastController(), new OrchestraTestController()];

        $app = m::mock(Application::class);
        $app->shouldReceive('runningInConsole')->andReturn(true);
        $app->shouldReceive('make')->withArgs([OrchestraTestController::class])
            ->andReturn(new OrchestraTestController());
        $app->shouldReceive('make')->withArgs([OrchestraBallastController::class])
            ->andReturn(new OrchestraBallastController());

        $foo = new DummyMetadataControllerProvider($app);

        $actual = $foo->getCandidateControllers($names);
        $this->assertEquals($expected, $actual);
    }
}
