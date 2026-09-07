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
        if (!Schema::hasTable('reserve_product_notifications')) {
            Schema::create('reserve_product_notifications', function (Blueprint $table) {
                $table->id('id');
                $table->text('data');
                $table->unsignedBigInteger('seller_id');
                $table->unsignedBigInteger('customer_id');
                $table->string('date', 200)->nullable();
                $table->string('type', 255)->nullable();
                $table->integer('active')->default(0);
                $table->timestamps();
                $table->integer('insert_flag')->default(1);
                $table->integer('update_flag');
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
        Schema::dropIfExists('reserve_product_notifications');
    }
};
