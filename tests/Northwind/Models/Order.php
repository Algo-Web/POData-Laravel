<?php declare(strict_types=1);

namespace Tests\Northwind\AlgoWeb\PODataLaravel\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Order.
 */
class Order extends Model
{
    use \AlgoWeb\PODataLaravel\Models\MetadataTrait;
    protected $table = 'orders';

    public $timestamps = false;

    protected $fillable = [
        'employee_id',
        'customer_id',
        'order_date',
        'shipped_date',
        'shipper_id',
        'ship_name',
        'ship_address',
        'ship_city',
        'ship_state_province',
        'ship_zip_postal_code',
        'ship_country_region',
        'shipping_fee',
        'taxes',
        'payment_type',
        'paid_date',
        'notes',
        'tax_rate',
        'tax_status_id',
        'status_id'
    ];

    protected $guarded = [];

    public function orderStatus()
    {
        return $this->belongsTo(OrdersStatus::class, 'status_id');
    }

    public function orderTaxStatus()
    {
        return $this->belongsTo(OrdersTaxStatus::class, 'tax_status_id');
    }

    public function shipper()
    {
        return $this->belongsTo(Shipper::class, 'shipper_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }

    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function inventoryTransactions()
    {
        return $this->hasMany(InventoryTransaction::class, 'customer_order_id');
    }
}
