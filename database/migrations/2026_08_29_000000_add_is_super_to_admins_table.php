<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * علامة Super Admin.
 *
 * تعديل بيانات المندوب صار مقصورًا على هذه الحسابات. لم يكن في الجدول ما
 * يميّز الـ Super Admin، وrole = 'admin' يشمل ستة حسابات بينها أمين المخزن
 * والمحاسب، فلا يصلح معيارًا.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            if (!Schema::hasColumn('admins', 'is_super')) {
                $table->boolean('is_super')->default(0)->after('role');
            }
        });

        // الحساب المسمّى Super Admin هو المالك الحالي لهذه الصلاحية.
        // أي حساب آخر يُمنح الصلاحية يدويًا من قاعدة البيانات.
        DB::table('admins')->where('email', 'hpieg63@gmail.com')->update(['is_super' => 1]);
    }

    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            if (Schema::hasColumn('admins', 'is_super')) {
                $table->dropColumn('is_super');
            }
        });
    }
};
