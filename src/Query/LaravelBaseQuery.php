<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 29/09/19
 * Time: 4:55 PM
 */

namespace AlgoWeb\PODataLaravel\Query;

use AlgoWeb\PODataLaravel\Auth\NullAuthProvider;
use AlgoWeb\PODataLaravel\Interfaces\AuthInterface;
use AlgoWeb\PODataLaravel\Providers\MetadataProvider;
use Illuminate\Support\Facades\App;

abstract class LaravelBaseQuery
{
    /** @var AuthInterface */
    protected $auth;
    protected $metadataProvider;

    public function __construct(AuthInterface $auth = null)
    {
        $this->auth = isset($auth) ? $auth : new NullAuthProvider();
        $this->metadataProvider = new MetadataProvider(App::make('app'));
    }

    /**
     * Dig out local copy of POData-Laravel metadata provider.
     *
     * @return MetadataProvider
     */
    public function getMetadataProvider()
    {
        return $this->metadataProvider;
    }

    protected function getAuth()
    {
        return $this->auth;
    }
}
