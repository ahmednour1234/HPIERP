<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use Database\Seeders\ApiTestingSeeder;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Api\ApiTestCase;

/**
 * إنشاء فاتورة من اللوحة.
 *
 * orders.update_flag و order_details.update_flag كلاهما NOT NULL بلا
 * افتراضي، ولم يكن هذا المسار يرسلهما، فيفشل الإدراج ويُبتلع الخطأ في
 * catch: تعود الشاشة كأن شيئًا لم يكن ولا تُحفظ فاتورة. مسار v2 يرسل
 * العمود في كل إدراج، ولهذا كان البيع من التطبيق يعمل ومن الويب لا.
 */
class PosPlaceOrderTest extends ApiTestCase
{
    private function placeOrder(array $overrides = [])
    {
        $admin = Admin::find(ApiTestingSeeder::ADMIN_ID);

        $this->actingAs($admin, 'admin');

        $cartKey = 'sc-90001';

        session([
            'current_user' => $cartKey,
            $cartKey       => [[
                'id'         => 90001,
                'quantity'   => 1,
                'price'      => 100,
                'discount'   => 0,
                'tax'        => 0,
                'unit_value' => 1,
            ]],
        ]);

        return $this->post(route('admin.pos.order'), array_merge([
            'type'                  => 4,
            'cash'                  => 1,
            'payment_id'            => DB::table('accounts')->value('id') ?? 1,
            'transaction_reference' => 100,
        ], $overrides));
    }

    /** @test */
    public function placing_an_order_from_the_panel_stores_it(): void
    {
        $before = DB::table('orders')->count();

        $this->placeOrder();

        $this->assertSame(
            $before + 1,
            DB::table('orders')->count(),
            'الفاتورة يجب أن تُحفظ، لا أن تعود الشاشة صامتة.'
        );
    }

    /** وسطورها معها. */
    public function test_the_order_lines_are_stored_too(): void
    {
        $this->placeOrder();

        $order = DB::table('orders')->latest('id')->first();

        $this->assertNotNull($order);
        $this->assertGreaterThan(
            0,
            DB::table('order_details')->where('order_id', $order->id)->count(),
            'سطور الفاتورة يجب أن تُحفظ مع رأسها.'
        );
    }

    /**
     * العمود الذي كان يُسقط الإدراج يُرسَل الآن.
     *
     * @test
     */
    public function the_update_flag_is_written(): void
    {
        $this->placeOrder();

        $order = DB::table('orders')->latest('id')->first();

        $this->assertNotNull($order->update_flag, 'orders.update_flag لا يُترك فارغًا.');

        $line = DB::table('order_details')->where('order_id', $order->id)->first();

        if ($line) {
            $this->assertNotNull($line->update_flag, 'order_details.update_flag كذلك.');
        }
    }
}
