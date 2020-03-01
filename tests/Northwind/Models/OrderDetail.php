<?php

declare(strict_types=1);

namespace Tests\Northwind\AlgoWeb\PODataLaravel\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class OrderDetail.
 */
class OrderDetail extends Model
{
    use \AlgoWeb\PODataLaravel\Models\MetadataTrait;
    protected $table = 'order_details';

    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'unit_price',
        'discount',
        'status_id',
        'date_allocated',
        'purchase_order_id',
        'inventory_id'
    ];

    protected $guarded = [];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function orderDetailsStatus()
    {
        return $this->belongsTo(OrderDetailsStatus::class, 'status_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
