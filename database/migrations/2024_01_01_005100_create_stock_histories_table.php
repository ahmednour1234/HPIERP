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
        // قاعدة الإنتاج تحمل الجداول بينما سجل migrations لا يطابقها،
        // فبدون هذا الفحص يفشل الأمر على أول جدول موجود.
        if (!Schema::hasTable('stock_histories')) {
            Schema::create('stock_histories', function (Blueprint $table) {
                $table->id('id');
                $table->unsignedBigInteger('order_id');
                $table->unsignedBigInteger('product_id');
                $table->unsignedBigInteger('seller_id');
                $table->integer('stock');
                $table->integer('main_stock');
                $table->timestamps();
                $table->index(['product_id'], 'stock_histories_stock_id_index');
                $table->index(['seller_id'], 'stock_histories_confirm_stocks_ibfk_1_index');
                $table->index(['order_id'], 'stock_histories_order_id_index');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stock_histories');
    }
};
