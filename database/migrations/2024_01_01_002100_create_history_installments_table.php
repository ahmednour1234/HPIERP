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
        if (!Schema::hasTable('history_installments')) {
            Schema::create('history_installments', function (Blueprint $table) {
                $table->id('id');
                $table->unsignedBigInteger('seller_id')->nullable();
                $table->unsignedBigInteger('customer_id');
                $table->bigInteger('supplier_id')->nullable();
                $table->unsignedBigInteger('order_id')->default(0);
                $table->integer('total_price')->default(0);
                $table->mediumText('note');
                $table->integer('notification')->default(1);
                $table->timestamps();
                $table->mediumText('img')->nullable();
                $table->index(['seller_id'], 'history_installments_seller_id_index');
                $table->index(['customer_id'], 'history_installments_customer_id_index');
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
        Schema::dropIfExists('history_installments');
    }
};
