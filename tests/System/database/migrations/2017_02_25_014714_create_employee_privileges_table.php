<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeePrivilegesTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_privileges', function (Blueprint $table) {
            $table->integer('employee_id')->index('employee_id');
            $table->integer('privilege_id')->index('privilege_id_2');
            $table->primary(['employee_id','privilege_id']);
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('employee_privileges');
    }
}
