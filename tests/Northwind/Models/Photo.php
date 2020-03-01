<?php

declare(strict_types=1);


namespace Tests\Northwind\AlgoWeb\PODataLaravel\Models;

use Illuminate\Database\Eloquent\Model;

class Photo extends Model
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
