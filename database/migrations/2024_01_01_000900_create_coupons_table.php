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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id('id');
            $table->string('title', 100)->nullable();
            $table->string('coupon_type', 255)->default('default');
            $table->integer('user_limit')->nullable();
            $table->string('code', 255)->nullable();
            $table->date('start_date')->nullable();
            $table->date('expire_date')->nullable();
            $table->decimal('min_purchase', 8, 2)->default(0.00);
            $table->decimal('max_discount', 8, 2)->default(0.00);
            $table->decimal('discount', 8, 2)->default(0.00);
            $table->string('discount_type', 15)->default('percentage');
            $table->boolean('status')->default(1);
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
        Schema::dropIfExists('coupons');
    }
};
