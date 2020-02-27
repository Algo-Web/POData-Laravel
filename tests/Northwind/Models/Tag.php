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
        return $this->morphToMany(Employee::class,'taggable', 'taggable_pivot');
    }

    public function taggedCustomer()
    {
        return $this->morphToMany(Customer::class,'taggable', 'taggable_pivot');
    }

}