<?php

namespace Tests\Feature\Api\V2;

use App\Services\CustomerService;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Api\ApiTestCase;

/**
 * حجم صفحة العملاء وفهارس عدّاداتها.
 *
 * التطبيق يطلب الصفحة الأولى ولا يطلب ما بعدها، فكان المندوب يرى 25
 * عميلًا من 2350 عند إنشاء فاتورة ويظنّ الباقي غير موجود.
 *
 * رفع الحدّ وحده كان يجعل الطلب يستغرق أكثر من عشرين ثانية: القائمة
 * تحسب لكل عميل عدد فواتيره وزياراته باستعلامٍ فرعي، بلا فهرس على
 * عمودي الربط. الفهارس هي ما يجعل الحدّ الجديد عمليًّا.
 */
class CustomerPageSizeTest extends ApiTestCase
{
    /** @test */
    public function the_default_page_holds_a_thousand_customers(): void
    {
        $this->assertSame(1000, CustomerService::DEFAULT_PAGE_SIZE);
    }

    /** @test */
    public function the_listing_reports_the_new_page_size(): void
    {
        $this->asSeller()->getJson('/api/v2/customers')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 1000);
    }

    /**
     * الترقيم باقٍ لمن يمرّر limit.
     *
     * @test
     */
    public function an_explicit_limit_is_still_honoured(): void
    {
        $this->asSeller()->getJson('/api/v2/customers?limit=5')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 5);
    }

    /**
     * الأعمدة التي تُمسح لكل صف مفهرسة.
     *
     * بدونها يصير الحدّ الجديد عبئًا لا حلًّا، وهو ما قيس فعلًا:
     * 22 ثانية قبل الفهرسة و64 مللي ثانية بعدها على البيانات نفسها.
     *
     * @test
     */
    public function the_counter_columns_are_indexed(): void
    {
        foreach ([
            'result_visitors'  => 'customer_id',
            'orders'           => 'user_id',
            'seller_customers' => 'customer_id',
        ] as $table => $column) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
                continue;
            }

            $this->assertTrue(
                $this->isIndexed($table, $column),
                "{$table}.{$column} يجب أن يكون مفهرسًا: القائمة تمسحه لكل عميل."
            );
        }
    }

    /** هل العمود أولَ عمودٍ في أي فهرس على الجدول؟ */
    private function isIndexed(string $table, string $column): bool
    {
        $connection = Schema::getConnection();

        if ($connection->getDriverName() !== 'sqlite') {
            // MySQL/MariaDB
            return collect($connection->select("SHOW INDEX FROM `{$table}`"))
                ->contains(fn ($row) => $row->Column_name === $column && (int) $row->Seq_in_index === 1);
        }

        foreach ($connection->select("PRAGMA index_list(`{$table}`)") as $index) {
            $columns = $connection->select("PRAGMA index_info(`{$index->name}`)");

            if (!empty($columns) && $columns[0]->name === $column) {
                return true;
            }
        }

        return false;
    }
}
