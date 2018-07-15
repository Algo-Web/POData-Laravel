<?php

namespace AlgoWeb\PODataLaravel\Facades;

class Schema extends \Illuminate\Support\Facades\Schema
{
    /**
     * Get a schema builder instance for the default connection.
     *
     * @return \Illuminate\Database\Schema\Builder
     */
    protected static function getFacadeAccessor()
    {
        return 'schema';
    }
}
