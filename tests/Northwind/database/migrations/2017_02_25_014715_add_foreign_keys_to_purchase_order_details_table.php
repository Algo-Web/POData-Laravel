<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToPurchaseOrderDetailsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('purchase_order_details', function (Blueprint $table) {
            $table->foreign('inventory_id', 'fk_purchase_order_details_inventory_transactions1')
                ->references('id')->on('inventory_transactions')->onUpdate('NO ACTION')->onDelete('NO ACTION');
            $table->foreign('product_id', 'fk_purchase_order_details_products1')
                ->references('id')->on('products')->onUpdate('NO ACTION')->onDelete('NO ACTION');
            $table->foreign('purchase_order_id', 'fk_purchase_order_details_purchase_orders1')
                ->references('id')->on('purchase_orders')->onUpdate('NO ACTION')->onDelete('NO ACTION');
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('purchase_order_details', function (Blueprint $table) {
            $table->dropForeign('fk_purchase_order_details_inventory_transactions1');
            $table->dropForeign('fk_purchase_order_details_products1');
            $table->dropForeign('fk_purchase_order_details_purchase_orders1');
        });
    }
}
