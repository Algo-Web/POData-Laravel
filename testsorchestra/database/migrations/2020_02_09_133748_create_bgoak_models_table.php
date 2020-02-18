<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBgoakModelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('test_cities', function (Blueprint $table) {
            $table->string('cityId')->primary();
            $table->string('name');
            $table->string('postcode');
            $table->string('country');
        });

        Schema::create('test_addresses', function (Blueprint $table) {
            $table->string('addressId')->primary();
            $table->string('cityid')->index();
            $table->string('street');
        });

        Schema::create('test_people', function (Blueprint $table) {
            $table->string('personId')->primary();
            $table->string('name');
            $table->string('givenname');
            $table->string('addressid')->index();
            $table->string('companyid')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('test_people');
        Schema::dropIfExists('test_addresses');
        Schema::dropIfExists('test_cities');
    }
}
