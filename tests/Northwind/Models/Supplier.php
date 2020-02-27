<?php

namespace Tests\Northwind\AlgoWeb\PODataLaravel\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Supplier.
 */
class Supplier extends Model
{
    use \AlgoWeb\PODataLaravel\Models\MetadataTrait;
    protected $table = 'suppliers';

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

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }
}
