<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use Database\Seeders\ApiTestingSeeder;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Api\ApiTestCase;

/**
 * نافذة الفاتورة في اللوحة.
 *
 * زرّ «الفاتورة» في تقرير المنتجات ينادي print_invoice، وهي دالة لم
 * تكن معرَّفة في تلك الصفحة — معرَّفةٌ في خمس صفحات أخرى لا فيها —
 * فالنقر لا يفعل شيئًا سوى خطأ في الكونسول.
 */
class InvoiceModalTest extends ApiTestCase
{
    private function admin(): Admin
    {
        DB::table('admins')->where('id', ApiTestingSeeder::ADMIN_ID)->update(['is_super' => 1]);

        return Admin::find(ApiTestingSeeder::ADMIN_ID);
    }

    /** @test */
    public function the_report_page_defines_the_function_its_buttons_call(): void
    {
        $html = $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.product.getreportProducts'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('print_invoice(', $html, 'الأزرار موجودة.');
        $this->assertStringContainsString(
            'function print_invoice',
            $html,
            'الدالة التي تناديها الأزرار يجب أن تكون معرَّفة في الصفحة.'
        );
    }

    /** والنافذة التي تعرض فيها. */
    public function test_the_report_page_carries_the_modal(): void
    {
        $html = $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.product.getreportProducts'))
            ->getContent();

        $this->assertStringContainsString('id="print-invoice"', $html);
        $this->assertStringContainsString('printableArea', $html);
    }

    /**
     * فاتورة خارج صلاحية الأدمن تعود 404 لا 500.
     *
     * الاستعلام يعيد null، وكان القالب يُعرض عليها فيسقط عند أول
     * $order['id'] — خطأ خادم عن حالةٍ هي «غير مسموح».
     *
     * @test
     */
    public function an_invoice_outside_the_admins_scope_is_not_a_server_error(): void
    {
        DB::table('admins')->where('id', ApiTestingSeeder::ADMIN_ID)->update(['is_super' => 0]);
        DB::table('admin_sellers')->where('admin_id', ApiTestingSeeder::ADMIN_ID)->delete();

        DB::table('orders')->updateOrInsert(['id' => 960001], [
            'user_id'      => 90001,
            'owner_id'     => 900077,
            'type'         => 4,
            'order_amount' => 100,
            'total_tax'    => 0,
            'update_flag'  => 0,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $response = $this->actingAs(Admin::find(ApiTestingSeeder::ADMIN_ID), 'admin')
            ->get('/admin/pos/invoice/960001');

        $this->assertNotSame(500, $response->status(), 'لا خطأ خادم على فاتورة خارج النطاق.');
    }
}
