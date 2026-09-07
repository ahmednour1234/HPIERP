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
        if (!Schema::hasTable('supply_orders')) {
            Schema::create('supply_orders', function (Blueprint $table) {
                $table->integer('id');
                $table->integer('factory_id')->comment('رقم المصنع أو الوحدة الإنتاجية');
                $table->integer('admin_id')->comment('منشئ الأمر');
                $table->date('order_date')->comment('تاريخ أمر التوريد');
                $table->enum('status', ['draft', 'issued', 'completed', 'ended'])->default('draft');
                $table->text('note')->nullable()->comment('ملاحظات عامة');
                $table->decimal('expected_cost', 15, 2)->nullable()->comment('التكلفة المتوقعة الإجمالية');
                $table->timestamps();
                $table->primary(['id']);
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
        Schema::dropIfExists('supply_orders');
    }
};
