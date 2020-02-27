<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventoryTransactionsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('transaction_type')->index('transaction_type');
            $table->dateTime('transaction_created_date')->nullable();
            $table->dateTime('transaction_modified_date')->nullable();
            $table->integer('product_id')->index('product_id_2');
            $table->integer('quantity');
            $table->integer('purchase_order_id')->nullable()->index('purchase_order_id_2');
            $table->integer('customer_order_id')->nullable()->index('customer_order_id_2');
            $table->string('comments')->nullable();
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('inventory_transactions');
    }
}
