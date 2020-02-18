<?php

namespace Tests\AlgoWeb\PODataLaravel\Models;

use AlgoWeb\PODataLaravel\Models\MetadataTrait;
use Illuminate\Database\Eloquent\Model as Model;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\EntityFieldPrimitiveType;

class TestExplicitModel extends Model
{
    use MetadataTrait;

    protected $odata = [
        'id' => [
            'type' => EntityFieldPrimitiveType::INTEGER,
            'nullable' => false,
            'fillable' => false,
            'default' => null,
        ],
        'name' => [
            'type' => EntityFieldPrimitiveType::STRING,
            'nullable' => false,
            'fillable' => true,
            'default' => null,
        ],
    ];
}
