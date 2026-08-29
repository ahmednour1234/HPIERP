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
        Schema::create('installments', function (Blueprint $table) {
            $table->id('id');
            $table->unsignedBigInteger('seller_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->bigInteger('supplier_id')->nullable();
            $table->unsignedBigInteger('order_id')->default(0);
            $table->integer('total_price')->default(0);
            $table->mediumText('note');
            $table->integer('active')->default(1);
            $table->integer('notification')->default(0);
            $table->integer('insert_flag')->default(1);
            $table->integer('update_flag');
            $table->timestamps();
            $table->mediumText('img')->nullable();
            $table->index(['seller_id'], 'installments_seller_id_index');
            $table->index(['customer_id'], 'installments_customer_id_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('installments');
    }
};
