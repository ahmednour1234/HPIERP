<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * صورة الزيارة المنفذة.
 *
 * التطبيق يرفع صورة مع الزيارة، لكن الجدول لم يكن يحمل عمودًا لها إطلاقًا،
 * فكانت تُهمَل بصمت عند الحفظ — لم تكن مجرد غائبة عن الرد.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('result_visitors', function (Blueprint $table) {
            if (!Schema::hasColumn('result_visitors', 'img')) {
                $table->string('img')->nullable()->after('lang');
            }
        });
    }

    public function down(): void
    {
        Schema::table('result_visitors', function (Blueprint $table) {
            if (Schema::hasColumn('result_visitors', 'img')) {
                $table->dropColumn('img');
            }
        });
    }
};
