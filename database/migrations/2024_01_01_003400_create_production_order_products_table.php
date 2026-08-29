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
        Schema::create('production_order_products', function (Blueprint $table) {
            $table->id('id');
            $table->bigInteger('production_order_id');
            $table->bigInteger('product_id');
            $table->decimal('target_quantity', 15, 3)->comment('الكمية المستهدفة للإنتاج');
            $table->decimal('produced_quantity', 15, 3)->comment('الكمية المنتجة بالفعل');
            $table->decimal('cost_price', 15, 4)->comment('سعر التكلفة للوحدة');
            $table->decimal('additional_cost_price', 15, 4)->comment('سعر التكلفة الإضافية للوحدة');
            $table->date('production_date')->comment('تاريخ بدء الإنتاج');
            $table->date('end_date')->comment('تاريخ انتهاء الإنتاج');
            $table->string('batch_number', 100)->comment('رقم التشغيل');
            $table->string('code', 100)->comment('كود داخلي أو خارجي للمنتج');
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
        Schema::dropIfExists('production_order_products');
    }
};
