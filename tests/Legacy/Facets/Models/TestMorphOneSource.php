<?php

declare(strict_types=1);

namespace Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models;

use AlgoWeb\PODataLaravel\Models\MetadataTrait;
use Illuminate\Database\Connection as Connection;
use Illuminate\Database\Eloquent\Model as Model;

class TestMorphOneSource extends Model
{
    use MetadataTrait {
        fetchMetadata as traitmetadata; // Need to alias the trait version of the method so we can call it and
        // not bury ourselves under a stack overflow and segfault
    }
    protected $metaArray;
    protected $connect;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['*'];

    public function __construct(array $meta = null, Connection $connect = null)
    {
        if (isset($meta)) {
            $this->metaArray = $meta;
        }
        if (isset($connect)) {
            $this->connect = $connect;
        } else {
            $connect       = \Mockery::mock(Connection::class)->makePartial();
            $this->connect = $connect;
        }
        parent::__construct();
    }

    public function getTable()
    {
        return 'testmorphonesource';
    }

    public function getConnectionName()
    {
        return 'testconnection';
    }

    public function getConnection()
    {
        return $this->connect;
    }

    public function fetchMetadata()
    {
        if (isset($this->metaArray)) {
            return $this->metaArray;
        }
        return $this->traitmetadata();
    }

    public function morphTarget()
    {
        return $this->morphOne(\Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models\TestMorphTarget::class, 'morph');
    }
}
