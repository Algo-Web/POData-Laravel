<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToEmployeePrivilegesTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee_privileges', function (Blueprint $table) {
            $table->foreign('employee_id', 'fk_employee_privileges_employees1')
                ->references('id')->on('employees')->onUpdate('NO ACTION')->onDelete('NO ACTION');
            $table->foreign('privilege_id', 'fk_employee_privileges_privileges1')
                ->references('id')->on('privileges')->onUpdate('NO ACTION')->onDelete('NO ACTION');
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employee_privileges', function (Blueprint $table) {
            $table->dropForeign('fk_employee_privileges_employees1');
            $table->dropForeign('fk_employee_privileges_privileges1');
        });
    }
}
