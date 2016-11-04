<?php

namespace AlgoWeb\PODataLaravel\Models;

use AlgoWeb\PODataLaravel\Models\MetadataTrait;
use Illuminate\Database\Eloquent\Model as Model;

class TestMorphTarget extends Model
{
    use MetadataTrait {
        metadata as traitmetadata; // Need to alias the trait version of the method so we can call it and
        // not bury ourselves under a stack overflow and segfault
        getRelationshipsFromMethods as getRel;
    }
    protected $metaArray;

    public function __construct(array $meta = null)
    {
        if (isset($meta)) {
            $this->metaArray = $meta;
        }
        parent::__construct();
    }

    public function metadata()
    {
        if (isset($this->metaArray)) {
            return $this->metaArray;
        }
        return $this->traitmetadata();
    }

    public function getRelationshipsFromMethods()
    {
        return $this->getRel();
    }

    public function morph()
    {
        return $this->morphTo();
    }
}
