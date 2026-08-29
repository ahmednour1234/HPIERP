<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id('id');
            $table->bigInteger('local_id');
            $table->string('name', 255);
            $table->integer('parent_id');
            $table->integer('position');
            $table->boolean('status')->default(1);
            $table->string('image', 255);
            $table->tinyInteger('insert_flag')->nullable()->default(1);
            $table->timestamps();
            $table->tinyInteger('update_flag')->nullable()->default(0);
            $table->bigInteger('company_id')->nullable()->default(1);
            $table->integer('type')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('categories');
    }
};
