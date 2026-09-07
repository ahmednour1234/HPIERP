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
        if (!Schema::hasTable('current_reserve_products')) {
            Schema::create('current_reserve_products', function (Blueprint $table) {
                $table->id('id');
                $table->text('data');
                $table->unsignedBigInteger('seller_id');
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->string('date', 200)->nullable();
                $table->integer('type');
                $table->timestamps();
                $table->integer('insert_flag')->default(1);
                $table->integer('update_flag');
                $table->index(['seller_id'], 'current_reserve_products_seller_id_index');
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
        Schema::dropIfExists('current_reserve_products');
    }
};
