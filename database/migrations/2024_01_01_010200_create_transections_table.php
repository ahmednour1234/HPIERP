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
        Schema::create('transections', function (Blueprint $table) {
            $table->id('id');
            $table->string('tran_type', 255)->nullable();
            $table->unsignedBigInteger('account_id')->nullable();
            $table->unsignedBigInteger('seller_id')->nullable();
            $table->double('amount')->nullable();
            $table->string('description', 255)->nullable();
            $table->double('debit')->nullable()->default(0);
            $table->double('credit')->nullable()->default(0);
            $table->double('balance')->nullable()->default(0);
            $table->date('date')->nullable();
            $table->unsignedInteger('customer_id')->nullable();
            $table->unsignedInteger('supplier_id')->nullable();
            $table->bigInteger('production_order_id')->nullable();
            $table->bigInteger('factory_id')->nullable();
            $table->unsignedInteger('order_id')->nullable();
            $table->timestamps();
            $table->bigInteger('company_id')->nullable()->default(1);
            $table->mediumText('img')->nullable();
            $table->integer('active')->default(1);
            $table->integer('cash')->default(1);
            $table->index(['seller_id'], 'transections_seller_id_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transections');
    }
};
