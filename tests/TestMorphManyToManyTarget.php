<?php

namespace AlgoWeb\PODataLaravel\Models;

use Illuminate\Database\Eloquent\Model as Model;
use Illuminate\Database\Connection as Connection;

class TestMorphManyToManyTarget extends Model
{
    use MetadataTrait {
        metadata as traitmetadata; // Need to alias the trait version of the method so we can call it and
        // not bury ourselves under a stack overflow and segfault
        getRelationshipsFromMethods as getRel;
    }
    protected $metaArray;
    protected $connect;

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

    public function manyTarget()
    {
        return $this->morphedByMany(
            TestMorphManyToManySource::class,
            'manyable',
            'manyables',
            'target_id',
            'source_id'
        );
    }

    public function getRelationshipsFromMethods($biDir = false)
    {
        return $this->getRel($biDir);
    }
}
