<?php

namespace Tests\Northwind\AlgoWeb\PODataLaravel\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class PurchaseOrderStatus.
 */
class PurchaseOrderStatus extends Model
{
    use \AlgoWeb\PODataLaravel\Models\MetadataTrait;
    protected $table = 'purchase_order_status';

    public $timestamps = false;

    protected $fillable = [
        'status'
    ];

    protected $guarded = [];

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class, 'status_id');
    }
}
