<?php

namespace AlgoWeb\PODataLaravel\Models;

use AlgoWeb\PODataLaravel\Models\MetadataTrait;
use Illuminate\Database\Eloquent\Model as Model;
use Illuminate\Database\Schema\Builder as SchemaBuilder;

class TestModel extends Model
{
    use MetadataTrait;

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
        return ['id' => 0, 'name' => '', 'added_at' => '', 'weight' => '', 'code' => ''];
    }

    public function getFillable()
    {
        return [ 'name', 'added_at', 'weight', 'code'];
    }
}
