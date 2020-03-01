<?php

declare(strict_types=1);

namespace Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models;

use AlgoWeb\PODataLaravel\Models\MetadataTrait;
use Illuminate\Database\Eloquent\Model as Model;

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
        $this->name       = 'Name';
        $this->added_at   = new \DateTime();
        $this->weight     = 42;
        $this->code       = 'ABC';
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

    public function setCasts(array $casts)
    {
        $this->casts = $casts;
    }
}
