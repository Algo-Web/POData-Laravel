<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->text('supplier_ids')->nullable();
            $table->integer('id', true);
            $table->string('product_code', 25)->nullable()->index('product_code');
            $table->string('product_name', 50)->nullable();
            $table->text('description')->nullable();
            $table->decimal('standard_cost', 19, 4)->nullable()->default(0.0000);
            $table->decimal('list_price', 19, 4)->default(0.0000);
            $table->integer('reorder_level')->nullable();
            $table->integer('target_level')->nullable();
            $table->string('quantity_per_unit', 50)->nullable();
            $table->boolean('discontinued')->default(0);
            $table->integer('minimum_reorder_quantity')->nullable();
            $table->string('category', 50)->nullable();
            $table->binary('attachments')->nullable();
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('products');
    }
}
