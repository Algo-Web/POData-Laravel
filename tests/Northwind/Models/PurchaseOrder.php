<?php declare(strict_types=1);

namespace Tests\Northwind\AlgoWeb\PODataLaravel\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class PurchaseOrder.
 */
class PurchaseOrder extends Model
{
    use \AlgoWeb\PODataLaravel\Models\MetadataTrait;
    protected $table = 'purchase_orders';

    public $timestamps = false;

    protected $fillable = [
        'supplier_id',
        'created_by',
        'submitted_date',
        'creation_date',
        'status_id',
        'expected_date',
        'shipping_fee',
        'taxes',
        'payment_date',
        'payment_amount',
        'payment_method',
        'notes',
        'approved_by',
        'approved_date',
        'submitted_by'
    ];

    protected $guarded = [];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseOrderStatus()
    {
        return $this->belongsTo(PurchaseOrderStatus::class, 'status_id');
    }

    public function purchaseOrderDetails()
    {
        return $this->hasMany(PurchaseOrderDetail::class, 'purchase_order_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }

    public function inventoryTransactions()
    {
        return $this->hasMany(InventoryTransaction::class, 'purchase_order_id');
    }
}
