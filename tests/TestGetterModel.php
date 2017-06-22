<?php

namespace AlgoWeb\PODataLaravel\Models;

use AlgoWeb\PODataLaravel\Models\MetadataTrait;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Concerns\HasAttributes;
use Illuminate\Database\Eloquent\Model as Model;
use Mockery as m;

class TestGetterModel extends Model
{
    use MetadataTrait {
        metadata as traitmetadata; // Need to alias the trait version of the method so we can call it and
        // not bury ourselves under a stack overflow and segfault
    }

    protected $metaArray;

    public function __construct(array $meta = null, $endpoint = null)
    {
        if (isset($meta)) {
            $this->metaArray = $meta;
        }
        if (isset($endpoint)) {
            $this->endpoint = $endpoint;
        }
        $this->dateFormat = 'Y-m-d H:i:s.u';
        $this->name = 'Name';
        $this->added_at = new \DateTime();
        $this->weight = 42;
        $this->code = 'ABC';
        parent::__construct();
    }

    public function getTable()
    {
        return 'testgettermodel';
    }

    public function getConnectionName()
    {
        return 'testconnection';
    }

    public function getFillable()
    {
        return [ 'name', 'added_at', 'weight', 'code'];
    }

    public function metadata()
    {
        if (isset($this->metaArray)) {
            return $this->metaArray;
        }
        return $this->traitmetadata();
    }
    
    public function getWeightCodeAttribute()
    {
        return $this->weight . $this->code;
    }

    public function getweightAttribute()
    {
        return $this->attributes['weight'] * 10;
    }
}
