<?php declare(strict_types=1);

namespace Tests\Northwind\AlgoWeb\PODataLaravel\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class InventoryTransactionType.
 */
class InventoryTransactionType extends Model
{
    use \AlgoWeb\PODataLaravel\Models\MetadataTrait;
    protected $table = 'inventory_transaction_types';

    public $timestamps = false;

    protected $fillable = [
        'type_name'
    ];

    protected $guarded = [];

    public function inventoryTransactions()
    {
        return $this->hasMany(InventoryTransaction::class, 'transaction_type', 'id');
    }
}
