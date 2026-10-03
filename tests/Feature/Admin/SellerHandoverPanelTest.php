<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Order;
use App\Models\OrderOwnerLog;
use App\Models\SellerHandover;
use App\Models\Stock;
use App\Models\VanHandoverCount;
use Database\Seeders\ApiTestingSeeder;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Api\ApiTestCase;

/**
 * شاشات تسليم العهدة في اللوحة.
 *
 * الشاشة هي ما يُشغّل السجلّ والترحيل والجرد، فبغير اختبارها يبقى
 * المنطق صحيحًا ولا أحد يستطيع استعماله.
 */
class SellerHandoverPanelTest extends ApiTestCase
{
    private const FROM = ApiTestingSeeder::SELLER_ID;
    private const TO   = 900078;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('admins')->updateOrInsert(['id' => self::TO], [
            'local_id'    => self::TO,
            'f_name'      => 'Successor',
            'l_name'      => 'Seller',
            'email'       => 'successor@example.test',
            'password'    => \Illuminate\Support\Facades\Hash::make('password'),
            'mandob_code' => 'SUCC',
            'role'        => 'seller',
            'type'        => 'mandob',
            'company_id'  => 1,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        // القسم محمي بصلاحيته، فيُمنح الأدمن صلاحية كاملة هنا: الاختبار
        // عن الشاشات لا عن نظام الصلاحيات، ولهذا اختباره وحده.
        DB::table('admins')->where('id', ApiTestingSeeder::ADMIN_ID)->update(['is_super' => 1]);

        $this->actingAs(Admin::find(ApiTestingSeeder::ADMIN_ID), 'admin');
    }

    private function handover(): SellerHandover
    {
        return SellerHandover::create([
            'from_seller_id' => self::FROM,
            'to_seller_id'   => self::TO,
            'started_at'     => now()->subDay(),
        ]);
    }

    private function order(int $id, float $amount = 500, float $paid = 0): void
    {
        DB::table('orders')->updateOrInsert(['id' => $id], [
            'user_id'               => 90001,
            'owner_id'              => self::FROM,
            'order_amount'          => $amount,
            'transaction_reference' => $paid,
            'total_tax'             => 0,
            'update_flag'           => 0,
            'type'                  => 4,
            'created_at'            => now(),
            'updated_at'            => now(),
        ]);
    }

    /** @test */
    public function the_listing_opens(): void
    {
        $this->handover();

        $this->get(route('admin.handover.index'))
            ->assertOk()
            ->assertSee('تسليم عهدة مندوب');
    }

    /** @test */
    public function the_create_form_opens(): void
    {
        $this->get(route('admin.handover.create'))->assertOk();
    }

    /** @test */
    public function a_handover_can_be_recorded(): void
    {
        $this->post(route('admin.handover.store'), [
            'from_seller_id' => self::FROM,
            'to_seller_id'   => self::TO,
        ])->assertRedirect();

        $this->assertDatabaseHas('seller_handovers', [
            'from_seller_id' => self::FROM,
            'to_seller_id'   => self::TO,
        ]);
    }

    /** @test */
    public function handing_a_seller_to_themselves_is_refused(): void
    {
        $this->post(route('admin.handover.store'), [
            'from_seller_id' => self::FROM,
            'to_seller_id'   => self::FROM,
        ])->assertSessionHasErrors('from_seller_id');

        $this->assertSame(0, SellerHandover::count());
    }

    /** @test */
    public function the_detail_page_lists_the_transferable_invoices(): void
    {
        $this->order(920001);
        $handover = $this->handover();

        $this->get(route('admin.handover.show', $handover->id))
            ->assertOk()
            ->assertSee('ترحيل الفواتير')
            ->assertSee('جرد العربية')
            ->assertSee('920001');
    }

    /** @test */
    public function invoices_transfer_from_the_panel(): void
    {
        $this->order(920001);
        $handover = $this->handover();

        $this->post(route('admin.handover.transfer', $handover->id), [
            'order_ids' => [920001],
            'reason'    => 'انتهاء الخدمة',
        ])->assertRedirect();

        $this->assertSame(self::TO, (int) Order::find(920001)->owner_id);
        $this->assertSame(self::FROM, OrderOwnerLog::originalOwnerOf(920001));
    }

    /** @test */
    public function a_transfer_can_be_undone_from_the_panel(): void
    {
        $this->order(920001);
        $handover = $this->handover();

        $this->post(route('admin.handover.transfer', $handover->id), ['order_ids' => [920001]]);
        $this->post(route('admin.handover.transfer.undo', $handover->id))->assertRedirect();

        $this->assertSame(self::FROM, (int) Order::find(920001)->owner_id);
    }

    /** @test */
    public function the_van_count_is_filed_and_approved_from_the_panel(): void
    {
        DB::table('stocks')->where('seller_id', self::FROM)
            ->where('product_id', 90001)->update(['stock' => 40]);

        $handover = $this->handover();

        $this->post(route('admin.handover.count.store', $handover->id), [
            'counted' => [90001 => 35],
        ])->assertRedirect();

        $count = VanHandoverCount::with('items')->where('handover_id', $handover->id)->first();

        $this->assertNotNull($count);
        $this->assertSame(5, $count->shortage());

        // المخزون لم يتحرك بعد.
        $this->assertSame(40, (int) Stock::where('seller_id', self::FROM)
            ->where('product_id', 90001)->value('stock'));

        $this->post(route('admin.handover.count.approve', [$handover->id, $count->id]))
            ->assertRedirect();

        $this->assertSame(
            VanHandoverCount::STATUS_APPROVED,
            VanHandoverCount::find($count->id)->status
        );

        $this->assertSame(0, (int) Stock::where('seller_id', self::FROM)
            ->where('product_id', 90001)->value('stock'));
    }

    /** @test */
    public function ending_a_handover_keeps_its_record(): void
    {
        $handover = $this->handover();

        $this->post(route('admin.handover.end', $handover->id))->assertRedirect();

        $this->assertNotNull(SellerHandover::find($handover->id)->ended_at);
        $this->assertDatabaseHas('seller_handovers', ['id' => $handover->id]);
    }

    /**
     * الشاشة محمية بصلاحية قسمها.
     *
     * @test
     */
    public function the_section_is_registered_for_permissions(): void
    {
        $this->assertArrayHasKey('handover', \App\Support\Permissions::groups());
    }

    /**
     * ولا تُفتح لمن لا يملك الصلاحية.
     *
     * التسجيل في القائمة وحده لا يحمي: لو غاب القسم عن خريطة
     * EnforceSectionPermission لفُتحت الشاشة للجميع.
     *
     * @test
     */
    public function the_section_is_closed_without_the_permission(): void
    {
        DB::table('admins')->updateOrInsert(['id' => 900079], [
            'local_id'   => 900079,
            'f_name'     => 'Plain',
            'l_name'     => 'Admin',
            'email'      => 'plain.admin@example.test',
            'password'   => \Illuminate\Support\Facades\Hash::make('password'),
            'role'       => 'admin',
            'is_super'   => 0,
            'company_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs(Admin::find(900079), 'admin')
            ->get(route('admin.handover.index'))
            ->assertForbidden();
    }

    /**
     * أدمنٌ يملك صلاحية القسم وحدها يدخل.
     *
     * is_super يتخطّى كل شيء فلا يثبت شيئًا؛ هذا الاختبار وحده هو
     * ما يثبت أن القسم مُصنَّف في خريطة EnforceSectionPermission:
     * بغير المدخل يسقط المسار في خانة "غير مصنَّف" فيُمنع حتى على
     * من يملك صلاحيته.
     *
     * @test
     */
    public function an_admin_with_only_the_section_permission_gets_in(): void
    {
        // القسم جديد، فصلاحيته قد لا تكون مزروعة في قاعدة الاختبار.
        $permission = \App\Models\Permission::firstOrCreate(
            ['name' => 'handover.view'],
            ['label' => 'عرض تسليم العهدة', 'group' => 'handover']
        );

        $role = \App\Models\Role::create(['name' => 'handover-only', 'label' => 'handover-only']);
        $role->permissions()->sync([$permission->id]);

        DB::table('admins')->updateOrInsert(['id' => 900080], [
            'local_id'   => 900080,
            'f_name'     => 'Handover',
            'l_name'     => 'Clerk',
            'email'      => 'handover.clerk@example.test',
            'password'   => \Illuminate\Support\Facades\Hash::make('password'),
            'role'       => 'admin',
            'is_super'   => 0,
            'company_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $clerk = Admin::find(900080);
        $clerk->roles()->sync([$role->id]);

        $this->actingAs($clerk, 'admin')
            ->get(route('admin.handover.index'))
            ->assertOk();
    }
}
