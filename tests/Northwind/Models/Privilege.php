<?php

namespace Tests\Northwind\AlgoWeb\PODataLaravel\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Privilege.
 */
class Privilege extends Model
{
    use \AlgoWeb\PODataLaravel\Models\MetadataTrait;
    protected $table = 'privileges';

    public $timestamps = false;

    protected $fillable = [
        'privilege_name'
    ];

    protected $guarded = [];

    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'employee_privileges');
    }
}
