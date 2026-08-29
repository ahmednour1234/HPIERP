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
        Schema::create('stocks', function (Blueprint $table) {
            $table->id('id');
            $table->bigInteger('local_id');
            $table->unsignedBigInteger('seller_id')->default(1);
            $table->integer('store_id')->nullable();
            $table->unsignedBigInteger('product_id');
            $table->integer('main_stock');
            $table->integer('stock');
            $table->integer('active')->default(1);
            $table->timestamps();
            $table->index(['product_id'], 'stocks_product_id_index');
            $table->index(['seller_id'], 'stocks_ibfk_1_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stocks');
    }
};
