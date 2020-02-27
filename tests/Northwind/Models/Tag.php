<?php


namespace Tests\Northwind\AlgoWeb\PODataLaravel\Models;


use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use \AlgoWeb\PODataLaravel\Models\MetadataTrait;
    protected $table = 'tags';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'tag_name',
    ];

    public function taggedEmployees()
    {
        return $this->morphedByMany(Employee::class,'taggable', 'taggable_pivot');
    }

    public function taggedCustomer()
    {
        return $this->morphedByMany(Customer::class,'taggable', 'taggable_pivot');
    }

}