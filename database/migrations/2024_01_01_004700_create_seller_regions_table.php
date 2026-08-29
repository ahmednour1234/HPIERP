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
        Schema::create('seller_regions', function (Blueprint $table) {
            $table->integer('id');
            $table->unsignedBigInteger('seller_id');
            $table->unsignedBigInteger('region_id');
            $table->primary(['id']);
            $table->index(['region_id'], 'seller_regions_region_id_index');
            $table->index(['seller_id'], 'seller_regions_ibfk_1_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('seller_regions');
    }
};
