<?php

namespace Tests\System\AlgoWeb\PODataLaravel\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Employee.
 */
class Employee extends Model
{
    use \AlgoWeb\PODataLaravel\Models\MetadataTrait;
    protected $table = 'employees';

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
        return $this->hasMany(Order::class, 'employee_id');
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class, 'created_by');
    }

    public function privileges()
    {
        return $this->belongsToMany(Privilege::class, 'employee_privileges');
    }
}
