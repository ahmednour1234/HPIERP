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
        Schema::create('develop_sellers', function (Blueprint $table) {
            $table->id('id');
            $table->unsignedBigInteger('admin_id');
            $table->unsignedBigInteger('seller_id');
            $table->text('note');
            $table->integer('type')->default(0);
            $table->integer('active')->default(0);
            $table->date('date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('develop_sellers');
    }
};
