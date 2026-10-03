<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * فهارس أعمدة العدّادات في قائمة العملاء.
     *
     * القائمة تحسب لكل عميل عدد فواتيره وعدد زياراته باستعلامٍ فرعي،
     * فصفحةٌ من ألف عميل تعني ألفَي مسحٍ كامل للجدولين. القياس على
     * البيانات: الاستعلام الأساسي 18ms، ومع العدّادين يتجاوز 20 ثانية،
     * منها 7.5 ثانية لـ result_visitors وحده إذ لا فهرس عليه إطلاقًا.
     *
     * الفهرسة هي الحل لا تصغير الصفحة: المندوب يحتاج عملاءه كلهم
     * لاختيار واحدٍ منهم عند إنشاء فاتورة.
     */
    public function up()
    {
        if (Schema::hasTable('result_visitors')) {
            $this->addIndex('result_visitors', 'customer_id', 'result_visitors_customer_id_index');
        }

        // orders.user_id هو العميل لا المستخدم. الفهرس القائم على
        // (type, archived_at) لا يخدم البحث بالعميل.
        if (Schema::hasTable('orders')) {
            $this->addIndex('orders', 'user_id', 'orders_user_id_index');
        }

        if (Schema::hasTable('seller_customers')) {
            $this->addIndex('seller_customers', 'customer_id', 'seller_customers_customer_id_index');
        }
    }

    public function down()
    {
        $this->dropIndex('result_visitors', 'result_visitors_customer_id_index');
        $this->dropIndex('orders', 'orders_user_id_index');
        $this->dropIndex('seller_customers', 'seller_customers_customer_id_index');
    }

    /** الفهرس قد يكون موجودًا على نسخةٍ دون أخرى، فلا تفشل الهجرة. */
    private function addIndex(string $table, string $column, string $name): void
    {
        if (!Schema::hasColumn($table, $column)) {
            return;
        }

        try {
            Schema::table($table, fn (Blueprint $t) => $t->index($column, $name));
        } catch (\Throwable) {
            // موجود بالفعل.
        }
    }

    private function dropIndex(string $table, string $name): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        try {
            Schema::table($table, fn (Blueprint $t) => $t->dropIndex($name));
        } catch (\Throwable) {
            // غير موجود.
        }
    }
};
