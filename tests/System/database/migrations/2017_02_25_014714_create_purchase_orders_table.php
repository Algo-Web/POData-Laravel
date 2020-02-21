<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchaseOrdersTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('supplier_id')->nullable()->index('supplier_id_2');
            $table->integer('created_by')->nullable()->index('created_by');
            $table->dateTime('submitted_date')->nullable();
            $table->dateTime('creation_date')->nullable();
            $table->integer('status_id')->nullable()->default(0)->index('status_id');
            $table->dateTime('expected_date')->nullable();
            $table->decimal('shipping_fee', 19, 4)->default(0.0000);
            $table->decimal('taxes', 19, 4)->default(0.0000);
            $table->dateTime('payment_date')->nullable();
            $table->decimal('payment_amount', 19, 4)->nullable()->default(0.0000);
            $table->string('payment_method', 50)->nullable();
            $table->text('notes')->nullable();
            $table->integer('approved_by')->nullable();
            $table->dateTime('approved_date')->nullable();
            $table->integer('submitted_by')->nullable();
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('purchase_orders');
    }
}
