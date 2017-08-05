<?php

namespace AlgoWeb\PODataLaravel\Models;

use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;

class TestMorphOneSourceAlternate extends Model
{
    use MetadataTrait {
        metadata as traitmetadata; // Need to alias the trait version of the method so we can call it and
        // not bury ourselves under a stack overflow and segfault
        getRelationshipsFromMethods as getRel;
    }
    protected $metaArray;
    protected $connect;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['*'];

    public $primaryKey = 'alternate_id';

    public function __construct(array $meta = null, Connection $connect = null)
    {
        if (isset($meta)) {
            $this->metaArray = $meta;
        }
        if (isset($connect)) {
            $this->connect = $connect;
        } else {
            $connect = \Mockery::mock(Connection::class)->makePartial();
            $this->connect = $connect;
        }
        parent::__construct();
    }

    public function getTable()
    {
        return 'testmorphonesourcealternate';
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

    public function getRelationshipsFromMethods($biDir = false)
    {
        return $this->getRel($biDir);
    }

    public function morphTarget()
    {
        return $this->morphOne('AlgoWeb\PODataLaravel\Models\TestMorphTarget', 'morph');
    }
}
