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
        Schema::create('order_details', function (Blueprint $table) {
            $table->id('id');
            $table->bigInteger('product_id')->nullable();
            $table->bigInteger('order_id')->nullable();
            $table->double('price')->default(0);
            $table->text('product_details')->nullable();
            $table->double('discount_on_product')->nullable();
            $table->string('discount_type', 20)->default('amount');
            $table->integer('quantity')->default(1);
            $table->integer('quantity_returned')->default(0);
            $table->double('tax_amount')->default(1);
            $table->integer('insert_flag')->default(1);
            $table->integer('update_flag');
            $table->timestamps();
            $table->bigInteger('company_id')->nullable()->default(1);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_details');
    }
};
