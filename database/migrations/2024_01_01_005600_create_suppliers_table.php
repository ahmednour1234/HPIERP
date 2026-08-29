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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id('id');
            $table->bigInteger('local_id');
            $table->string('name', 255);
            $table->string('mobile', 255);
            $table->string('email', 255)->nullable();
            $table->string('image', 255)->nullable();
            $table->string('state', 255)->nullable();
            $table->string('city', 255)->nullable();
            $table->string('zip_code', 255)->nullable();
            $table->string('address', 255)->nullable();
            $table->double('due_amount')->nullable();
            $table->timestamps();
            $table->bigInteger('company_id')->nullable()->default(1);
            $table->integer('type')->default(0);
            $table->double('credit')->default(0);
            $table->integer('active')->default(1);
            $table->double('limit')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('suppliers');
    }
};
