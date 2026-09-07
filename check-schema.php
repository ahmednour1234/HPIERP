<?php
/**
 * فحص المخطّط: يقارن ما يحتاجه الكود بما هو موجود فعلًا في قاعدة البيانات.
 *
 * يُشغَّل على السيرفر:
 *     php check-schema.php
 *
 * لا يعدّل شيئًا — يقرأ فقط ويطبع الأعمدة الناقصة مع أوامر SQL جاهزة.
 *
 * سبب وجوده: جدول migrations فارغ بينما الجداول موجودة، فأمر migrate يحاول
 * إنشاءها من جديد ويفشل. هذا الفحص يتجاوز سجل الهجرات ويسأل قاعدة البيانات
 * مباشرة عن الأعمدة.
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Schema;

/**
 * الأعمدة التي أضافها التطوير الأخير خارج سجل الهجرات.
 * table => [column => "تعريف SQL"]
 */
$required = [
    'salaries' => [
        'collection_incentive' => "VARCHAR(20) NOT NULL DEFAULT '0'  -- حافز التحصيل",
    ],
    'orders' => [
        'archived_at'    => "TIMESTAMP NULL           -- أرشفة الفواتير",
        'archived_by'    => "BIGINT NULL              -- من أرشفها",
        'archive_reason' => "VARCHAR(255) NULL        -- سبب الأرشفة",
    ],
    'reserve_products' => [
        'note' => "TEXT NULL                          -- سبب رد المخزون",
    ],
    'admins' => [
        'is_super' => "TINYINT(1) NOT NULL DEFAULT 0  -- صلاحية السوبر أدمن",
    ],
    'result_visitors' => [
        'img' => "VARCHAR(255) NULL                   -- صورة الزيارة",
    ],
];

echo "\n";
echo "قاعدة البيانات: " . config('database.connections.' . config('database.default') . '.database') . "\n";
echo str_repeat('=', 70) . "\n\n";

$missing = [];
$missingTables = [];

foreach ($required as $table => $columns) {
    if (!Schema::hasTable($table)) {
        $missingTables[] = $table;
        echo "✗ الجدول '{$table}' غير موجود إطلاقًا\n";
        continue;
    }

    foreach ($columns as $column => $definition) {
        // نفصل التعريف عن التعليق الموضِّح
        $parts = explode('--', $definition, 2);
        $sqlType = trim($parts[0]);
        $label   = isset($parts[1]) ? trim($parts[1]) : '';

        if (Schema::hasColumn($table, $column)) {
            printf("  ✓ %-18s %-24s %s\n", $table, $column, $label);
        } else {
            printf("  ✗ %-18s %-24s %s   << ناقص\n", $table, $column, $label);
            $missing[] = "ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$sqlType};";
        }
    }
}

echo "\n" . str_repeat('=', 70) . "\n";

if (empty($missing) && empty($missingTables)) {
    echo "النتيجة: كل الأعمدة موجودة. لا حاجة لأي إجراء.\n\n";
    exit(0);
}

echo "النتيجة: " . count($missing) . " عمود ناقص.\n\n";
echo "نفّذ هذه الأوامر (من phpMyAdmin أو mysql):\n\n";

foreach ($missing as $sql) {
    echo "  {$sql}\n";
}

echo "\n";

if ($missingTables) {
    echo "تنبيه: جداول غير موجودة تحتاج مراجعة يدوية: "
        . implode(', ', $missingTables) . "\n\n";
}
