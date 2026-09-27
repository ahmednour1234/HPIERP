<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use Database\Seeders\ApiTestingSeeder;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Tests\Feature\Api\ApiTestCase;

/**
 * تصدير الرواتب إلى xlsx.
 *
 * يصدّر ما تطابقه الفلاتر كلّه لا صفحة الترقيم، فالمراجعة على الشهر
 * كاملًا لا على عشرة صفوف.
 */
class SalariesExportTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // الشاشة محروسة بصلاحية salaries، والحساب المُجهَّز بلا أدوار.
        // جدول الصلاحيات فارغ في قاعدة الاختبار، فتُزرع أولًا.
        foreach (\App\Support\Permissions::all() as $name => $label) {
            Permission::firstOrCreate(
                ['name' => $name],
                ['group' => explode('.', $name)[0], 'label' => $label]
            );
        }

        $role = Role::create(['name' => 'salaries-test', 'label' => 'salaries']);
        $role->permissions()->sync(Permission::where('group', 'salaries')->pluck('id'));

        Admin::find(ApiTestingSeeder::ADMIN_ID)->roles()->sync([$role->id]);
    }

    public function test_it_downloads_a_spreadsheet(): void
    {
        Excel::fake();

        $this->actingAs(Admin::find(ApiTestingSeeder::ADMIN_ID), 'admin')
            ->get('/admin/admin/salaries/export')
            ->assertOk();

        Excel::assertDownloaded('salaries-' . now()->format('Y-m') . '.xlsx');
    }

    public function test_it_exports_every_matching_row_not_just_the_page(): void
    {
        // أكثر من صفحة واحدة: الترقيم عشرة.
        for ($i = 0; $i < 12; $i++) {
            $this->salary('2026-03');
        }

        $rows = $this->exportedRows();

        $this->assertSame(12, $rows->count(),
            'التصدير اقتصر على صفحة الترقيم بدل كل النتائج');
    }

    public function test_the_month_filter_reaches_the_export(): void
    {
        $this->salary('2026-03');
        $this->salary('2026-03');
        $this->salary('2026-04');

        $this->assertSame(2, $this->exportedRows(['month' => '2026-03'])->count());
        $this->assertSame(1, $this->exportedRows(['month' => '2026-04'])->count());
    }

    /** الصفر يُكتب رقمًا لا خانة فارغة: كشف رواتب لا يحتمل اللبس. */
    public function test_a_zero_is_written_as_a_number(): void
    {
        $this->salary('2026-05', ['discount' => '0', 'total' => '0']);

        $row = $this->exportedRows(['month' => '2026-05'])->first();

        // العمودان: الخصم (13) والمجموع (15) بترتيب الأعمدة الصفري.
        $this->assertSame(0.0, $row[12]);
        $this->assertSame(0.0, $row[14]);
    }

    /** @return \Illuminate\Support\Collection<int, array<int, mixed>> */
    private function exportedRows(array $query = [])
    {
        Excel::fake();

        $this->actingAs(Admin::find(ApiTestingSeeder::ADMIN_ID), 'admin')
            ->get('/admin/admin/salaries/export?' . http_build_query($query))
            ->assertOk();

        $captured = collect();

        Excel::assertDownloaded(
            'salaries-' . ($query['month'] ?? now()->format('Y-m')) . '.xlsx',
            function ($export) use ($captured) {
                $captured->push(...$export->collection()->all());
                return true;
            }
        );

        return $captured;
    }

    private function salary(string $month, array $attributes = []): void
    {
        $id = (int) (DB::table('salaries')->max('id') ?? 0) + 1;

        DB::table('salaries')->insert(array_merge([
            'id'         => $id,
            'seller_id'  => ApiTestingSeeder::SELLER_ID,
            'salary'     => '1000',
            'commission' => '0',
            'month'      => $month,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }
}
