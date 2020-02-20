<?php

namespace Tests\System\AlgoWeb\PODataLaravel\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Product
 */
class Product extends Model
{
    use \AlgoWeb\PODataLaravel\Models\MetadataTrait;
    protected $table = 'products';

    public $timestamps = false;

    protected $fillable = [
        'supplier_ids',
        'product_code',
        'product_name',
        'description',
        'standard_cost',
        'list_price',
        'reorder_level',
        'target_level',
        'quantity_per_unit',
        'discontinued',
        'minimum_reorder_quantity',
        'category',
        'attachments'
    ];

    protected $guarded = [];

    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class, 'product_id');
    }

    public function purchaseOrderDetails()
    {
        return $this->hasMany(PurchaseOrderDetail::class, 'product_id');
    }

    public function inventoryTransactions()
    {
        return $this->hasMany(InventoryTransaction::class, 'product_id');
    }
}
