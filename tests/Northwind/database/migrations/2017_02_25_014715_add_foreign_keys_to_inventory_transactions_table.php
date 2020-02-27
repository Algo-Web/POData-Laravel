<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToInventoryTransactionsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->foreign('customer_order_id', 'fk_inventory_transactions_orders1')
                ->references('id')->on('orders')->onUpdate('NO ACTION')->onDelete('NO ACTION');
            $table->foreign('product_id', 'fk_inventory_transactions_products1')
                ->references('id')->on('products')->onUpdate('NO ACTION')->onDelete('NO ACTION');
            $table->foreign('purchase_order_id', 'fk_inventory_transactions_purchase_orders1')
                ->references('id')->on('purchase_orders')->onUpdate('NO ACTION')->onDelete('NO ACTION');
            $table->foreign('transaction_type', 'fk_inventory_transactions_inventory_transaction_types1')
                ->references('id')->on('inventory_transaction_types')->onUpdate('NO ACTION')->onDelete('NO ACTION');
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->dropForeign('fk_inventory_transactions_orders1');
            $table->dropForeign('fk_inventory_transactions_products1');
            $table->dropForeign('fk_inventory_transactions_purchase_orders1');
            $table->dropForeign('fk_inventory_transactions_inventory_transaction_types1');
        });
    }
}
