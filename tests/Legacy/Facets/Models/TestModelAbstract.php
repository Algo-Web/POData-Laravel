<?php

declare(strict_types=1);

namespace Tests\Legacy\AlgoWeb\PODataLaravel\Facets\Models;

use AlgoWeb\PODataLaravel\Models\MetadataTrait;
use Illuminate\Database\Eloquent\Model as Model;

abstract class TestModelAbstract extends Model
{
    use MetadataTrait;
}
