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
        Schema::create('current_orders', function (Blueprint $table) {
            $table->id('id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('stock_id')->nullable();
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->string('owner_role', 10)->default('admin');
            $table->double('order_amount')->default(0);
            $table->double('total_tax');
            $table->double('collected_cash')->nullable();
            $table->double('extra_discount')->nullable();
            $table->string('coupon_code', 255)->nullable();
            $table->double('coupon_discount_amount')->default(0);
            $table->string('coupon_discount_title', 255)->nullable();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->string('transaction_reference', 255)->nullable();
            $table->timestamps();
            $table->bigInteger('company_id')->nullable()->default(1);
            $table->boolean('type')->nullable()->default(4);
            $table->integer('cash')->default(1);
            $table->index(['stock_id'], 'current_orders_stock_id_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('current_orders');
    }
};
