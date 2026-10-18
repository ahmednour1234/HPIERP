<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * قيمة افتراضية لعمود update_flag.
     *
     * العمود NOT NULL بلا افتراضي بينما أخوه insert_flag له واحد، فكل
     * إدراجٍ لا يذكره صراحةً يفشل. ولأن مسار البيع من اللوحة لا يذكره،
     * كان إنشاء الفاتورة ينتهي بـ«insert into orders … NOT NULL
     * constraint failed: orders.update_flag»، فتُبتلع في catch وتظهر
     * الشاشة كأنها لم تفعل شيئًا.
     *
     * تعديل الهجرة الأصلية لا يكفي: هي نُفِّذت على الإنتاج بالفعل،
     * فالتغيير هناك يحتاج هجرةً جديدة.
     */
    private const TABLES = [
        'orders',
        'order_details',
        'order_notifications',
        'order_detail_notifications',
        'reserve_products',
        'reserve_product_notifications',
        'current_reserve_products',
        'stock_orders',
    ];

    public function up()
    {
        foreach (self::TABLES as $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'update_flag')) {
                continue;
            }

            // الصفوف الفارغة أولًا: NOT NULL مع افتراضي لا يصلح صفًّا
            // يحمل NULL أصلًا على MySQL.
            DB::table($table)->whereNull('update_flag')->update(['update_flag' => 0]);

            $this->setDefault($table);
        }
    }

    public function down()
    {
        // لا تراجع: إزالة الافتراضي تعيد كسر الإدراج، وهي ليست حالةً
        // يُرغب في العودة إليها.
    }

    /**
     * SQLite لا يدعم ALTER COLUMN، وdoctrine/dbal غير مثبَّت هنا،
     * فيُكتفى على المحرّكات الأخرى بأمر صريح.
     */
    private function setDefault(string $table): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            return;
        }

        try {
            DB::statement(
                "ALTER TABLE `{$table}` MODIFY `update_flag` INT NOT NULL DEFAULT 0"
            );
        } catch (\Throwable $e) {
            // نوع العمود قد يختلف على نسخةٍ ما؛ الفشل هنا لا يمنع
            // بقية الجداول.
        }
    }
};
