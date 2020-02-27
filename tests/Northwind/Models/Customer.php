<?php

namespace Tests\Northwind\AlgoWeb\PODataLaravel\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Customer.
 */
class Customer extends Model
{
    use \AlgoWeb\PODataLaravel\Models\MetadataTrait;
    protected $table = 'customers';

    public $timestamps = false;

    protected $fillable = [
        'company',
        'last_name',
        'first_name',
        'email_address',
        'job_title',
        'business_phone',
        'home_phone',
        'mobile_phone',
        'fax_number',
        'address',
        'city',
        'state_province',
        'zip_postal_code',
        'country_region',
        'web_page',
        'notes',
        'attachments'
    ];

    protected $guarded = [];

    public function orders()
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    public function invoices()
    {
        return $this->hasManyThrough(
            Invoice::class,
            Order::class,
            'customer_id',
            'order_id',
            'id',
            'id'
        );
    }

    public function photos()
    {
        return $this->morphOne(Photo::class, 'rel');
    }

    public function tags()
    {
        return $this->morphedByMany(Tag::class, 'taggable', 'taggable_pivot');
    }
}
