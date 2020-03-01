<?php

declare(strict_types=1);

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

        Schema::create('test_polymorph_to_many_target_models', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->string('name')->default('');
            $table->timestamp('added_at')->useCurrent();
            $table->float('weight')->default(0);
            $table->string('code')->default('');
        });

        Schema::create('test_manyables', function (Blueprint $table) {
            $table->integer('many_id')->index();
            $table->string('manyable_type')->index();
            $table->integer('manyable_id')->index();
        });

        Schema::create('test_polymorph_to_many_source_models', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->string('name')->default('');
            $table->timestamp('added_at')->useCurrent();
            $table->float('weight')->default(0);
            $table->string('code')->default('');
        });

        Schema::create('test_polymorph_to_many_source_malformed_models', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->string('name')->default('');
            $table->timestamp('added_at')->useCurrent();
            $table->float('weight')->default(0);
            $table->string('code')->default('');
        });

        Schema::create('test_morph_to_target_models', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->string('name')->default('');
            $table->timestamp('added_at')->useCurrent();
            $table->float('weight')->default(0);
            $table->string('code')->default('');
            $table->morphs('morph');
            $table->morphs('newmorph');
        });

        Schema::create('test_morph_one_models', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->string('name')->default('');
            $table->timestamp('added_at')->useCurrent();
            $table->float('weight')->nullable();
            $table->string('code')->nullable();
        });

        Schema::create('test_morph_many_models', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->string('name')->default('');
            $table->timestamp('added_at')->useCurrent();
            $table->float('weight')->nullable();
            $table->string('code')->nullable();
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
