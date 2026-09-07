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
        if (!Schema::hasTable('seller_customers')) {
            Schema::create('seller_customers', function (Blueprint $table) {
                $table->integer('id');
                $table->bigInteger('local_id');
                $table->unsignedBigInteger('customer_id');
                $table->unsignedBigInteger('seller_id');
                $table->timestamps();
                $table->primary(['id']);
                $table->index(['customer_id'], 'seller_customers_customer_id_index');
                $table->index(['seller_id'], 'seller_customers_seller_id_index');
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
        Schema::dropIfExists('seller_customers');
    }
};
