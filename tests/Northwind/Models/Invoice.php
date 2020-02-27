<?php declare(strict_types=1);

namespace Tests\Northwind\AlgoWeb\PODataLaravel\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Invoice.
 */
class Invoice extends Model
{
    use \AlgoWeb\PODataLaravel\Models\MetadataTrait;
    protected $table = 'invoices';

    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'invoice_date',
        'due_date',
        'tax',
        'shipping',
        'amount_due'
    ];

    protected $guarded = [];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
    public function customer()
    {
        return $this->hasManyThrough(
            Customer::class,
            Order::class,
            'id',
            'id',
            'order_id',
            'customer_id'
        );
    }
}
