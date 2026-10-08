<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use Database\Seeders\ApiTestingSeeder;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Api\ApiTestCase;

/**
 * ملفات CSV تبدأ بعلامة ترتيب البايتات صحيحةً.
 *
 * Excel على ويندوز يفترض الترميز المحلي ما لم يجد BOM، فتظهر العربية
 * محارفَ غريبة. وكان أحد التصديرات يكتبه كمحارف مرئية (ï»¿) فيُرمَّز
 * مرتين: ست بايتات بدل ثلاث، فلا Excel يقرؤه علامةً ولا المستخدم
 * يقرأ العربية.
 */
class CsvExportEncodingTest extends ApiTestCase
{
    /** البايتات الثلاث التي يتعرّف عليها Excel. */
    private const BOM = "\xEF\xBB\xBF";

    private function superAdmin(): Admin
    {
        DB::table('admins')->where('id', ApiTestingSeeder::ADMIN_ID)->update(['is_super' => 1]);

        return Admin::find(ApiTestingSeeder::ADMIN_ID);
    }

    private function download(string $url): string
    {
        $response = $this->actingAs($this->superAdmin(), 'admin')->get($url);

        $response->assertOk();

        return $response->streamedContent();
    }

    /** @test */
    public function the_sold_products_export_starts_with_a_real_bom(): void
    {
        $csv = $this->download(route('admin.product.getreportProducts.export'));

        $this->assertSame(
            self::BOM,
            substr($csv, 0, 3),
            'ثلاث بايتات بالضبط: ست تعني أن العلامة رُمِّزت مرتين.'
        );
    }

    /** ولا تتكرر العلامة: الست بايتات تبدأ بالثلاث نفسها. */
    public function test_the_bom_is_not_written_twice(): void
    {
        $csv = $this->download(route('admin.product.getreportProducts.export'));

        $this->assertNotSame(
            self::BOM,
            substr($csv, 3, 3),
            'علامة ثانية بعد الأولى تعني كتابةً مزدوجة.'
        );
    }

    /** والمحتوى عربيٌّ سليم لا بايتات تالفة. */
    public function test_the_body_is_valid_utf8(): void
    {
        $csv = $this->download(route('admin.product.getreportProducts.export'));

        $this->assertTrue(mb_check_encoding($csv, 'UTF-8'));
    }
}
