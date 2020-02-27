<?php declare(strict_types=1);

namespace Tests\Northwind\AlgoWeb\PODataLaravel\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class InventoryTransaction.
 */
class InventoryTransaction extends Model
{
    use \AlgoWeb\PODataLaravel\Models\MetadataTrait;
    protected $table = 'inventory_transactions';

    public $timestamps = false;

    protected $fillable = [
        'transaction_type',
        'transaction_created_date',
        'transaction_modified_date',
        'product_id',
        'quantity',
        'purchase_order_id',
        'customer_order_id',
        'comments'
    ];

    protected $guarded = [];

    public function inventoryTransactionType()
    {
        return $this->belongsTo(InventoryTransactionType::class, 'transaction_type');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function purchaseOrderDetails()
    {
        return $this->belongsTo(PurchaseOrderDetail::class, 'purchase_order_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'customer_order_id');
    }
}
