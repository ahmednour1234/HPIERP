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
        Schema::create('seller_prices', function (Blueprint $table) {
            $table->id('id');
            $table->bigInteger('local_id');
            $table->unsignedBigInteger('seller_id');
            $table->unsignedBigInteger('product_id');
            $table->double('price');
            $table->timestamps();
            $table->index(['product_id'], 'seller_prices_product_id_index');
            $table->index(['seller_id'], 'seller_prices_seller_id_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('seller_prices');
    }
};
