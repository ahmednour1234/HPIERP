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
        Schema::create('seller_categories', function (Blueprint $table) {
            $table->integer('id');
            $table->unsignedBigInteger('cat_id');
            $table->unsignedBigInteger('seller_id');
            $table->primary(['id']);
            $table->index(['cat_id'], 'seller_categories_cat_id_index');
            $table->index(['seller_id'], 'seller_categories_ibfk_2_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('seller_categories');
    }
};
