<?php

namespace AlgoWeb\PODataLaravel\Models;

use AlgoWeb\PODataLaravel\Models\MetadataTrait;
use Illuminate\Database\Concerns\BuildsQueries;
use Illuminate\Database\Eloquent\Model as Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Schema\Builder as SchemaBuilder;

class TestCastModel extends Model
{
    use MetadataTrait {
        metadata as traitmetadata; // Need to alias the trait version of the method so we can call it and
        // not bury ourselves under a stack overflow and segfault
    }

    protected $metaArray;

    protected $casts = ['is_bool' => 'boolean'];

    public function __construct(array $meta = null, $endpoint = null)
    {
        if (isset($meta)) {
            $this->metaArray = $meta;
        }
        if (isset($endpoint)) {
            $this->endpoint = $endpoint;
        }
        parent::__construct();
    }

    public function getTable()
    {
        return 'testmodel';
    }

    public function getConnectionName()
    {
        return 'testconnection';
    }

    protected function getAllAttributes()
    {
        return ['id' => 0, 'name' => '', 'added_at' => '', 'weight' => '', 'code' => '', 'is_bool' => 0];
    }

    public function getFillable()
    {
        return [ 'name', 'added_at', 'weight', 'code'];
    }

    public static function findOrFail($id, $columns = ['*'])
    {
        if (!is_numeric($id) || !is_int($id) || 0 >= $id) {
            throw (new ModelNotFoundException)->setModel(TestModel::class, $id);
        } else {
            return new self;
        }
    }

    public function metadata()
    {
        if (isset($this->metaArray)) {
            return $this->metaArray;
        }
        return $this->traitmetadata();
    }
}
