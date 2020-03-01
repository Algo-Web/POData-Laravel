<?php

declare(strict_types=1);

namespace Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models;

use AlgoWeb\PODataLaravel\Models\MetadataTrait;
use Illuminate\Database\Connection as Connection;
use Illuminate\Database\Eloquent\Model;
use Mockery as m;

class TestPolymorphicDualSource extends Model
{
    use MetadataTrait {
        metadata as traitmetadata; // Need to alias the trait version of the method so we can call it and
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
            $connect       = m::mock(Connection::class)->makePartial();
            $this->connect = $connect;
        }
        parent::__construct();
    }

    public function getTable()
    {
        return 'testpolymorphicdualsource';
    }

    public function getConnectionName()
    {
        return 'testconnection';
    }

    public function getConnection()
    {
        return $this->connect;
    }

    public function metadata()
    {
        if (isset($this->metaArray)) {
            return $this->metaArray;
        }
        return $this->traitmetadata();
    }

    public function morphTarget()
    {
        return $this->morphOne(TestMorphTarget::class, 'morph');
    }

    public function morphAlternate()
    {
        return $this->morphOne(TestMorphTargetAlternate::class, 'morph');
    }
}
