<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * نطاق التسليم.
 *
 * include_stock: قد لا تُسلَّم بضاعة العربية للمستلم، فتُغلق خطوة الجرد.
 * assignments:   ما نُقل من عملاء ومناطق وأقسام، يُسجَّل ليُعرض ويُعرف
 *                ما كان مع صاحب العهدة قبل النقل.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('seller_handovers', function (Blueprint $table) {
            if (!Schema::hasColumn('seller_handovers', 'include_stock')) {
                $table->boolean('include_stock')->default(true)->after('to_seller_id');
            }
            if (!Schema::hasColumn('seller_handovers', 'assignments')) {
                $table->json('assignments')->nullable()->after('include_stock');
            }
        });
    }

    public function down()
    {
        Schema::table('seller_handovers', function (Blueprint $table) {
            foreach (['assignments', 'include_stock'] as $column) {
                if (Schema::hasColumn('seller_handovers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
