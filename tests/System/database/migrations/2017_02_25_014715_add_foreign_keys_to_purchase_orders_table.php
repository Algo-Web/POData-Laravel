<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToPurchaseOrdersTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->foreign('created_by', 'fk_purchase_orders_employees1')
                ->references('id')->on('employees')->onUpdate('NO ACTION')->onDelete('NO ACTION');
            $table->foreign('status_id', 'fk_purchase_orders_purchase_order_status1')
                ->references('id')->on('purchase_order_status')->onUpdate('NO ACTION')->onDelete('NO ACTION');
            $table->foreign('supplier_id', 'fk_purchase_orders_suppliers1')
                ->references('id')->on('suppliers')->onUpdate('NO ACTION')->onDelete('NO ACTION');
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign('fk_purchase_orders_employees1');
            $table->dropForeign('fk_purchase_orders_purchase_order_status1');
            $table->dropForeign('fk_purchase_orders_suppliers1');
        });
    }
}
