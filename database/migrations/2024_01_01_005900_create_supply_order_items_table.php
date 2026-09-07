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
        if (!Schema::hasTable('supply_order_items')) {
            Schema::create('supply_order_items', function (Blueprint $table) {
                $table->id('id');
                $table->bigInteger('supply_order_id')->comment('FK إلى supply_orders.id');
                $table->bigInteger('product_id')->comment('المنتَج المراد انتاجه');
                $table->decimal('product_quantity', 15, 3)->comment('كمية المنتج المراد انتاجه');
                $table->decimal('expected_cost_per_unit', 15, 2)->comment('التكلفة المتوقعة لإنتاج وحدة واحدة');
                $table->timestamps();
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
        Schema::dropIfExists('supply_order_items');
    }
};
