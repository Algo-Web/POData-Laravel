<?php

namespace Tests\Northwind\AlgoWeb\PODataLaravel\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class EmployeePrivilege.
 */
class EmployeePrivilege extends Model
{
//    use \AlgoWeb\PODataLaravel\Models\MetadataTrait;
    protected $table = 'employee_privileges';

    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = null;

    protected $fillable = [
        'employee_id',
        'privilege_id'
    ];

    protected $guarded = [];
}
