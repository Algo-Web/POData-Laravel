<?php

declare(strict_types=1);

namespace Tests\Northwind\AlgoWeb\PODataLaravel\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class SalesReport.
 */
class SalesReport extends Model
{
    use \AlgoWeb\PODataLaravel\Models\MetadataTrait;
    protected $table = 'sales_reports';

    protected $primaryKey = 'group_by';

    public $timestamps = false;

    protected $fillable = [
        'display',
        'title',
        'filter_row_source',
        'default'
    ];

    protected $guarded = [];
}
