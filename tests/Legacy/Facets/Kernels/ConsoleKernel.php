<?php

declare(strict_types=1);

namespace Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Kernels;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Console\Kernel;

class ConsoleKernel extends Kernel
{
    protected $oldEnv = 'Illuminate\Foundation\Bootstrap\DetectEnvironment';
    protected $newEnv = 'Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables';
    protected $env;

    /**
     * The bootstrap classes for the application.
     *
     * @var array
     */
    protected $bootstrappers = [
        'Illuminate\Foundation\Bootstrap\HandleExceptions',
        'Illuminate\Foundation\Bootstrap\RegisterFacades',
        'Illuminate\Foundation\Bootstrap\SetRequestForConsole',
        'Illuminate\Foundation\Bootstrap\RegisterProviders',
        'Illuminate\Foundation\Bootstrap\BootProviders',
    ];

    /**
     * The application's route middleware.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class
    ];

    /**
     * Create a new console kernel instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application $app
     * @param  \Illuminate\Contracts\Events\Dispatcher      $events
     * @return void
     */
    public function __construct(Application $app, Dispatcher $events)
    {
        if (class_exists($this->oldEnv)) {
            $this->env = $this->oldEnv;
        } else {
            $this->env = $this->newEnv;
        }

        array_unshift($this->bootstrappers, $this->env);
        parent::__construct($app, $events);
    }
}
