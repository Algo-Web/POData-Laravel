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

abstract class LaravelBaseQuery
{
    /** @var AuthInterface */
    protected $auth;

    public function __construct(AuthInterface $auth = null)
    {
        $this->auth = isset($auth) ? $auth : new NullAuthProvider();
    }
}
