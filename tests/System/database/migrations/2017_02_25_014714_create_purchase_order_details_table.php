<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchaseOrderDetailsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('purchase_order_details', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('purchase_order_id')->index('purchase_order_details_id_2');
            $table->integer('product_id')->nullable()->index('purchase_order_details_product_id_2');
            $table->decimal('quantity', 18, 4);
            $table->decimal('unit_cost', 19, 4);
            $table->dateTime('date_received')->nullable();
            $table->boolean('posted_to_inventory')->default(0);
            $table->integer('inventory_id')->nullable()->index('purchase_order_details_inventory_id_2');
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('purchase_order_details');
    }
}
