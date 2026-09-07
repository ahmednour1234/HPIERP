<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ملاحظة المندوب على طلب الحجز.
 *
 * جدول reserve_products يحمل العمود بالفعل، لكن شاشة صرف البضاعة تقرأ من
 * reserve_product_notifications وهو لا يحمله، فلا مكان تُحفظ فيه الملاحظة
 * القادمة من التطبيق.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reserve_product_notifications', function (Blueprint $table) {
            if (!Schema::hasColumn('reserve_product_notifications', 'note')) {
                $table->text('note')->nullable()->after('data');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reserve_product_notifications', function (Blueprint $table) {
            if (Schema::hasColumn('reserve_product_notifications', 'note')) {
                $table->dropColumn('note');
            }
        });
    }
};
