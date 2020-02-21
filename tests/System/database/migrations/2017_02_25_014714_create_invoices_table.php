<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvoicesTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('order_id')->nullable()->index('fk_invoices_orders1_idx');
            $table->dateTime('invoice_date')->nullable();
            $table->dateTime('due_date')->nullable();
            $table->decimal('tax', 19, 4)->nullable()->default(0.0000);
            $table->decimal('shipping', 19, 4)->nullable()->default(0.0000);
            $table->decimal('amount_due', 19, 4)->nullable()->default(0.0000);
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('invoices');
    }
}
