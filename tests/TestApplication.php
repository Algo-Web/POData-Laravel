<?php

namespace AlgoWeb\PODataLaravel\Models;

use Illuminate\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\ProviderRepository;

class TestApplication extends Application
{
    protected $filesys;
    protected $inConsole = true;

    public function __construct(Filesystem $file)
    {
        parent::__construct(dirname(__DIR__));
        $this->filesys = $file;
    }

    /**
     * Register all of the configured providers.
     *
     * @return void
     */
    public function registerConfiguredProviders()
    {
        $this->basePath = dirname(__DIR__);
        $manifestPath = $this->getCachedServicesPath();

        (new ProviderRepository($this, $this->filesys, $manifestPath))
            ->load($this->config['app.providers']);
    }

    public function runningInConsole()
    {
        return $this->inConsole;
    }

    public function setConsole($flag)
    {
        $this->inConsole = true === $flag;
    }
}
