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
        Schema::create('reserve_products', function (Blueprint $table) {
            $table->id('id');
            $table->text('data');
            $table->unsignedBigInteger('seller_id');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('date', 200)->nullable();
            $table->string('type', 255)->nullable();
            $table->timestamps();
            $table->integer('active')->default(1);
            $table->integer('notification')->default(1);
            $table->integer('insert_flag')->default(1);
            $table->integer('update_flag');
            $table->index(['seller_id'], 'reserve_products_seller_id_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reserve_products');
    }
};
