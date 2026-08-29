<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * سبب رد المخزون المصروف.
 *
 * عملية الرد تحتاج تعليلًا يُراجَع لاحقًا (خطأ في الصرف، إرجاع من المندوب،
 * تسوية جرد). الجدول لم يكن يحمل حقل ملاحظات.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reserve_products', function (Blueprint $table) {
            if (!Schema::hasColumn('reserve_products', 'note')) {
                $table->string('note', 500)->nullable()->after('data');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reserve_products', function (Blueprint $table) {
            if (Schema::hasColumn('reserve_products', 'note')) {
                $table->dropColumn('note');
            }
        });
    }
};
