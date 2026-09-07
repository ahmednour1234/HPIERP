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
        if (!Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
                $table->id('id');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->bigInteger('supplier_id')->nullable();
                $table->unsignedBigInteger('stock_id')->nullable();
                $table->unsignedBigInteger('owner_id')->nullable();
                $table->bigInteger('parent_id')->nullable();
                $table->string('owner_role', 10)->default('admin');
                $table->double('order_amount')->default(0);
                $table->double('total_tax');
                $table->double('collected_cash')->nullable();
                $table->double('extra_discount')->nullable();
                $table->string('coupon_code', 255)->nullable();
                $table->double('coupon_discount_amount')->default(0);
                $table->string('coupon_discount_title', 255)->nullable();
                $table->unsignedBigInteger('payment_id')->nullable();
                $table->string('transaction_reference', 255)->nullable();
                $table->integer('insert_flag')->default(1);
                $table->integer('update_flag');
                $table->timestamps();
                $table->bigInteger('company_id')->nullable()->default(1);
                $table->string('type', 222)->nullable()->default('4');
                $table->integer('cash')->default(1);
                $table->integer('notification')->default(1);
                $table->integer('active')->default(1);
                $table->mediumText('img')->nullable();
                $table->index(['stock_id'], 'orders_stock_id_index');
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
        Schema::dropIfExists('orders');
    }
};
