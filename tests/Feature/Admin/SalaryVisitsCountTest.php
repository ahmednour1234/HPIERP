<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use Carbon\Carbon;
use Database\Seeders\ApiTestingSeeder;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Api\ApiTestCase;

/**
 * الزيارات الفعلية في شاشة الراتب.
 *
 * حفظ راتب الشهر يصفّر admins.result_visitors استعدادًا للشهر التالي،
 * فكانت الشاشة تقرأ صفرًا لمندوبٍ نزل زياراته فعلًا — ونسبة الزيارات
 * معه، إذ تُحسب منه.
 */
class SalaryVisitsCountTest extends ApiTestCase
{
    private const SELLER = ApiTestingSeeder::SELLER_ID;

    private function visit(string $at): void
    {
        DB::table('result_visitors')->insert([
            'admin_id'    => self::SELLER,
            'customer_id' => 90001,
            'note'        => '',
            'lat'         => '0',
            'lang'        => '0',
            'img'         => '',
            'created_at'  => $at,
            'updated_at'  => $at,
        ]);
    }

    private function payload(): array
    {
        $this->actingAs(Admin::find(ApiTestingSeeder::ADMIN_ID), 'admin');

        return json_decode(
            app(\App\Http\Controllers\Admin\SalaryController::class)
                ->showsalary(self::SELLER)->getContent(),
            true
        );
    }

    /** @test */
    public function the_visits_are_counted_from_their_own_records(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 15, 10));

        try {
            // العمود مصفَّر كما يتركه حفظ الراتب.
            DB::table('admins')->where('id', self::SELLER)->update(['result_visitors' => 0]);

            $this->visit('2026-09-02 09:00:00');
            $this->visit('2026-09-08 09:00:00');
            $this->visit('2026-09-21 09:00:00');

            $this->assertSame(3, (int) $this->payload()['result_visitors']);
        } finally {
            Carbon::setTestNow();
        }
    }

    /** زيارات شهرٍ آخر لا تدخل كشف هذا الشهر. */
    public function test_another_months_visits_are_not_counted(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 15, 10));

        try {
            DB::table('admins')->where('id', self::SELLER)->update(['result_visitors' => 0]);

            $this->visit('2026-09-02 09:00:00');
            $this->visit('2026-08-28 09:00:00');
            $this->visit('2026-10-01 09:00:00');

            $this->assertSame(1, (int) $this->payload()['result_visitors']);
        } finally {
            Carbon::setTestNow();
        }
    }

    /**
     * العمود المخزَّن لا يَحجب العدّ.
     *
     * قيمته القديمة كانت هي ما يُعرض، فيبقى رقم شهرٍ مضى.
     */
    public function test_the_stored_column_does_not_win(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 15, 10));

        try {
            DB::table('admins')->where('id', self::SELLER)->update(['result_visitors' => 209]);

            $this->visit('2026-09-02 09:00:00');

            $this->assertSame(1, (int) $this->payload()['result_visitors']);
        } finally {
            Carbon::setTestNow();
        }
    }
}
