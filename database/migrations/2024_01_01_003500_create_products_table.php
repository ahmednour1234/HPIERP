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
        if (!Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table) {
                $table->id('id');
                $table->bigInteger('local_id');
                $table->string('name', 255);
                $table->string('name_en', 255)->default('Default product');
                $table->string('product_code', 255);
                $table->unsignedInteger('unit_type')->nullable();
                $table->double('unit_value', 8, 2)->nullable();
                $table->string('brand', 255)->nullable();
                $table->string('category_id', 255)->nullable();
                $table->double('purchase_price')->nullable();
                $table->double('purchase_price1');
                $table->double('purchase_price2');
                $table->double('purchase_price3');
                $table->double('purchase_price4');
                $table->double('selling_price')->nullable();
                $table->double('selling_price1');
                $table->double('selling_price2');
                $table->double('selling_price3');
                $table->double('selling_price4');
                $table->string('discount_type', 255)->nullable();
                $table->double('discount', 8, 2)->nullable();
                $table->integer('limit_stock')->default(10);
                $table->integer('limit_web')->default(2);
                $table->double('tax', 8, 2)->nullable();
                $table->bigInteger('quantity')->nullable();
                $table->string('image', 255)->nullable();
                $table->unsignedInteger('order_count')->nullable();
                $table->integer('refund_count')->default(0);
                $table->integer('purchase_count')->default(0);
                $table->integer('repurchase_count')->default(0);
                $table->unsignedInteger('supplier_id')->nullable();
                $table->tinyInteger('insert_flag')->nullable()->default(1);
                $table->timestamps();
                $table->tinyInteger('update_flag')->nullable()->default(0);
                $table->timestamp('expiry_date')->default(DB::raw('CURRENT_TIMESTAMP'));
                $table->bigInteger('company_id')->nullable()->default(1);
                $table->string('type', 255);
                $table->integer('tax_id')->nullable();
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
        Schema::dropIfExists('products');
    }
};
