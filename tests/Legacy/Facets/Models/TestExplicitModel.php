<?php

namespace Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models;

use AlgoWeb\PODataLaravel\Models\MetadataTrait;
use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\EntityFieldPrimitiveType;
use Illuminate\Database\Eloquent\Model as Model;

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
