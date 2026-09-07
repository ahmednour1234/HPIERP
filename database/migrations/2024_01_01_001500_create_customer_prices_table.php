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
        if (!Schema::hasTable('customer_prices')) {
            Schema::create('customer_prices', function (Blueprint $table) {
                $table->id('id');
                $table->bigInteger('local_id');
                $table->unsignedBigInteger('customer_id');
                $table->unsignedBigInteger('product_id');
                $table->double('price');
                $table->tinyInteger('insert_flag')->nullable()->default(1);
                $table->timestamps();
                $table->tinyInteger('update_flag')->nullable()->default(0);
                $table->index(['product_id'], 'customer_prices_product_id_index');
                $table->index(['customer_id'], 'customer_prices_customer_id_index');
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
        Schema::dropIfExists('customer_prices');
    }
};
