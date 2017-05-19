<?php

namespace AlgoWeb\PODataLaravel\Models;

use Illuminate\Database\Eloquent\Model as Model;
use Illuminate\Database\Connection as Connection;

class TestMorphManyToManySource extends Model
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

    public function manySource()
    {
        return $this->morphToMany(
            TestMorphManyToManyTarget::class,
            'manyable',
            'manyables',
            'source_id',
            'target_id'
        );
    }

    public function getRelationshipsFromMethods($biDir = false)
    {
        return $this->getRel($biDir);
    }
}
