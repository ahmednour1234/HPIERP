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
        Schema::create('purchase_details', function (Blueprint $table) {
            $table->id('id');
            $table->unsignedBigInteger('purchase_id');
            $table->unsignedBigInteger('material_id');
            $table->decimal('quantity', 15, 3);
            $table->string('unit', 50);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('discount', 15, 2)->default(0.00);
            $table->decimal('tax_amount', 15, 2)->default(0.00);
            $table->decimal('total', 15, 2);
            $table->date('expiration_date')->nullable();
            $table->string('unique_code', 50)->nullable();
            $table->timestamps();
            $table->index(['purchase_id'], 'purchase_details_purchase_id_index');
            $table->index(['material_id'], 'purchase_details_material_id_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('purchase_details');
    }
};
