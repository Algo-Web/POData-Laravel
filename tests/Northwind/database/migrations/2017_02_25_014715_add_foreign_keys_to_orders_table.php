<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToOrdersTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('customer_id', 'fk_orders_customers')
                ->references('id')->on('customers')->onUpdate('NO ACTION')->onDelete('NO ACTION');
            $table->foreign('employee_id', 'fk_orders_employees1')
                ->references('id')->on('employees')->onUpdate('NO ACTION')->onDelete('NO ACTION');
            $table->foreign('shipper_id', 'fk_orders_shippers1')
                ->references('id')->on('shippers')->onUpdate('NO ACTION')->onDelete('NO ACTION');
            $table->foreign('tax_status_id', 'fk_orders_orders_tax_status1')
                ->references('id')->on('orders_tax_status')->onUpdate('NO ACTION')->onDelete('NO ACTION');
            $table->foreign('status_id', 'fk_orders_orders_status1')
                ->references('id')->on('orders_status')->onUpdate('NO ACTION')->onDelete('NO ACTION');
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign('fk_orders_customers');
            $table->dropForeign('fk_orders_employees1');
            $table->dropForeign('fk_orders_shippers1');
            $table->dropForeign('fk_orders_orders_tax_status1');
            $table->dropForeign('fk_orders_orders_status1');
        });
    }
}
