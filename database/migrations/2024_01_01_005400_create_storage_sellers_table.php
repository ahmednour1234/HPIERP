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
        Schema::create('storage_sellers', function (Blueprint $table) {
            $table->id('id');
            $table->unsignedBigInteger('storage_id');
            $table->unsignedBigInteger('seller_id');
            $table->timestamps();
            $table->index(['storage_id'], 'storage_sellers_storage_id_index');
            $table->index(['seller_id'], 'storage_sellers_seller_id_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('storage_sellers');
    }
};
