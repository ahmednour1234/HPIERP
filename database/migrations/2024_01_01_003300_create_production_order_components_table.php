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
        Schema::create('production_order_components', function (Blueprint $table) {
            $table->id('id');
            $table->bigInteger('production_order_product_id');
            $table->bigInteger('supply_order_item_id');
            $table->bigInteger('material_batch_id');
            $table->json('details')->comment('{ "used": {"qty": 12.345, "unit_id": 3}, "wasted": {"qty": 0.500, "unit_id": 3}, "returned": {"qty": 1.000, "unit_id": 3} }');
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
        Schema::dropIfExists('production_order_components');
    }
};
