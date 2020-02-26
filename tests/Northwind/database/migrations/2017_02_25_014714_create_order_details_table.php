<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderDetailsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_details', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('order_id')->index('fk_order_details_orders1_idx');
            $table->integer('product_id')->nullable()->index('order_details_product_id_2');
            $table->decimal('quantity', 18, 4)->default(0.0000);
            $table->decimal('unit_price', 19, 4)->nullable()->default(0.0000);
            $table->float('discount', 10, 0)->default(0);
            $table->integer('status_id')->nullable()->index('fk_order_details_order_details_status1_idx');
            $table->dateTime('date_allocated')->nullable();
            $table->integer('purchase_order_id')->nullable()->index('order_details_purchase_order_id');
            $table->integer('inventory_id')->nullable()->index('order_details_inventory_id');
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('order_details');
    }
}
