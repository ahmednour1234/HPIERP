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
        Schema::create('transaction_sellers', function (Blueprint $table) {
            $table->id('id');
            $table->bigInteger('seller_id');
            $table->bigInteger('account_id');
            $table->string('amount', 255);
            $table->mediumText('note')->nullable();
            $table->mediumText('img');
            $table->integer('active')->default(0);
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
        Schema::dropIfExists('transaction_sellers');
    }
};
