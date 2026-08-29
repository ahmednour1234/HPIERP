<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * أرشفة الفواتير القديمة المكتملة.
 *
 * الأرشفة وسم على الفاتورة لا حذفًا ولا نقلًا إلى جدول آخر: الفاتورة تبقى
 * مرتبطة بتفاصيلها وتحصيلاتها ومرتجعاتها وقيودها المحاسبية، فتظل التقارير
 * والأرصدة صحيحة تمامًا كما كانت. الأرشفة تُخفيها من قوائم العمل اليومية فقط.
 *
 * الفهرس على (type, archived_at) لأن كل قوائم الفواتير سترشّح بهما معًا.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('active');
            }

            if (!Schema::hasColumn('orders', 'archived_by')) {
                $table->unsignedBigInteger('archived_by')->nullable()->after('archived_at');
            }

            if (!Schema::hasColumn('orders', 'archive_reason')) {
                $table->string('archive_reason', 255)->nullable()->after('archived_by');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->index(['type', 'archived_at'], 'orders_type_archived_idx');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_type_archived_idx');
            $table->dropColumn(['archived_at', 'archived_by', 'archive_reason']);
        });
    }
};
