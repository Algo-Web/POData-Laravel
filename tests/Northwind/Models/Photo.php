<?php


namespace Tests\Northwind\AlgoWeb\PODataLaravel\Models;


class Photo
{
    use \AlgoWeb\PODataLaravel\Models\MetadataTrait;
    protected $table = 'photos';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'content',
        'rel_type',
        'rel_id'
    ];

    protected $guarded = [];

    public function orders()
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    public function photoOf()
    {
        return $this->morphTo('rel');
    }
}