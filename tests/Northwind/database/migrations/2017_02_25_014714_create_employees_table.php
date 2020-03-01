<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeesTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('company', 50)->nullable()->index('employee_company');
            $table->string('last_name', 50)->nullable()->index('employee_last_name');
            $table->string('first_name', 50)->nullable()->index('employee_first_name');
            $table->string('email_address', 50)->nullable();
            $table->string('job_title', 50)->nullable();
            $table->string('business_phone', 25)->nullable();
            $table->string('home_phone', 25)->nullable();
            $table->string('mobile_phone', 25)->nullable();
            $table->string('fax_number', 25)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 50)->nullable()->index('employee_city');
            $table->string('state_province', 50)->nullable()->index('employee_state_province');
            $table->string('zip_postal_code', 15)->nullable()->index('employee_zip_postal_code');
            $table->string('country_region', 50)->nullable();
            $table->text('web_page')->nullable();
            $table->text('notes')->nullable();
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
        Schema::drop('employees');
    }
}
