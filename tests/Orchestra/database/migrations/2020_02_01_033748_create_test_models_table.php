<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTestModelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('test_models', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->string('name')->default('');
            $table->timestamp('added_at')->useCurrent();
            $table->float('weight')->default(0);
            $table->string('code')->default('');
        });

        Schema::create('relation_test_dummy_models', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
        });

        Schema::create('test_has_many_through_models', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->string('name')->default('');
            $table->timestamp('added_at')->useCurrent();
            $table->float('weight')->default(0);
            $table->string('code')->default('');
        });

        Schema::create('test_has_many_models', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->string('name')->default('');
            $table->timestamp('added_at')->useCurrent();
            $table->float('weight')->default(0);
            $table->string('code')->default('');
            $table->integer('parent_id')->index()->nullable();
        });

        Schema::create('test_belongs_to_models', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->string('name')->default('');
            $table->timestamp('added_at')->useCurrent();
            $table->float('weight')->default(0);
            $table->string('code')->default('');
            $table->integer('parent_id')->index()->nullable();
        });

        Schema::create('test_belongs_to_many_models', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->string('name')->default('');
            $table->timestamp('added_at')->useCurrent();
            $table->float('weight')->default(0);
            $table->string('code')->default('');
        });

        Schema::create('test_belongs_to_many_pivot', function (Blueprint $table) {
            $table->integer('left_id')->index();
            $table->integer('right_id')->index();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('relation_test_dummy_models');
        Schema::dropIfExists('test_models');
    }
}
