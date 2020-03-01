<?php

declare(strict_types=1);

namespace Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models;

use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use Mockery as m;

class TestMorphUnexposedTarget extends Model
{
    protected $connect;

    /**
     * TestMorphUnexposedTarget constructor.
     * @param array|null      $meta
     * @param Connection|null $connect
     */
    public function __construct(Connection $connect = null)
    {
        if (isset($connect)) {
            $this->connect = $connect;
        } else {
            $connect       = m::mock(Connection::class)->makePartial();
            $this->connect = $connect;
        }
        parent::__construct();
    }

    public function getTable()
    {
        return 'testmorphunexposedtarget';
    }

    public function getConnectionName()
    {
        return 'testconnection';
    }

    public function getConnection()
    {
        return $this->connect;
    }

    public function morph()
    {
        return $this->morphTo();
    }
}
