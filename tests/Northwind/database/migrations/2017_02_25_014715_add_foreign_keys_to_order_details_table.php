<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToOrderDetailsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_details', function (Blueprint $table) {
            $table->foreign('order_id', 'fk_order_details_orders1')
                ->references('id')->on('orders')->onUpdate('NO ACTION')->onDelete('NO ACTION');
            $table->foreign('product_id', 'fk_order_details_products1')
                ->references('id')->on('products')->onUpdate('NO ACTION')->onDelete('NO ACTION');
            $table->foreign('status_id', 'fk_order_details_order_details_status1')
                ->references('id')->on('order_details_status')->onUpdate('NO ACTION')->onDelete('NO ACTION');
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order_details', function (Blueprint $table) {
            $table->dropForeign('fk_order_details_orders1');
            $table->dropForeign('fk_order_details_products1');
            $table->dropForeign('fk_order_details_order_details_status1');
        });
    }
}
