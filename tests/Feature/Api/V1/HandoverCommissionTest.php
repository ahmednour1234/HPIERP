<?php

namespace Tests\Feature\Api\V1;

use App\Models\Seller;
use Database\Seeders\ApiTestingSeeder;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Api\ApiTestCase;

/**
 * العمولة لمن باع، والعهدة لمن قبض.
 *
 * بعد ترحيل عهدة مندوبٍ انتهت خدمته تصير فواتيره باسم خلفه فيحصّلها.
 * المال في يده فيدخل عهدته ليورّده، أما العمولة فتبقى لصاحب البيعة.
 */
class HandoverCommissionTest extends ApiTestCase
{
    private const SELLER = ApiTestingSeeder::SELLER_ID;
    private const OTHER  = 900078;

    /** فاتورة آجلة، owner_id يحدد صاحب البيعة. */
    private function order(int $id, int $owner, float $amount = 1000): void
    {
        DB::table('orders')->updateOrInsert(['id' => $id], [
            'user_id'               => 90001,
            'owner_id'              => $owner,
            'order_amount'          => $amount,
            'transaction_reference' => 0,
            'collected_cash'        => 0,
            'total_tax'             => 0,
            'update_flag'           => 0,
            'type'                  => 4,
            'cash'                  => 2,
            'created_at'            => now(),
            'updated_at'            => now(),
        ]);
    }

    private function otherSeller(): void
    {
        DB::table('admins')->updateOrInsert(['id' => self::OTHER], [
            'local_id'    => self::OTHER,
            'f_name'      => 'Former',
            'l_name'      => 'Seller',
            'email'       => 'former.commission@example.test',
            'password'    => \Illuminate\Support\Facades\Hash::make('password'),
            'mandob_code' => 'FORMERC',
            'role'        => 'seller',
            'type'        => 'mandob',
            'company_id'  => 1,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    private function collect(int $orderId, float $amount)
    {
        $account = DB::table('accounts')->first();

        if (!$account) {
            $this->markTestSkipped('لا يوجد حساب في البيانات.');
        }

        return $this->asSeller()->postJson('/api/v1/pos/place/installment', [
            'order_id'   => $orderId,
            'user_id'    => 90001,
            'price'      => $amount,
            'payment_id' => $account->id,
            'note'       => 'اختبار',
        ]);
    }

    /**
     * فاتورته هو: العمولة والعهدة كلتاهما تزيدان.
     *
     * @test
     */
    public function collecting_own_invoice_pays_commission(): void
    {
        $this->order(930001, self::SELLER);

        $before = Seller::find(self::SELLER);
        $comm   = (float) $before->commission;
        $credit = (float) $before->credit;

        $response = $this->collect(930001, 100);

        $response->assertOk();

        $after = Seller::find(self::SELLER);

        $this->assertSame(round($comm + 100, 2), round((float) $after->commission, 2));
        $this->assertSame(round($credit + 100, 2), round((float) $after->credit, 2));
    }

    /**
     * فاتورة عهدة: العهدة تزيد والعمولة لا.
     *
     * @test
     */
    public function collecting_a_handed_over_invoice_pays_no_commission(): void
    {
        $this->otherSeller();

        // الفاتورة رُحِّلت، لكنها في الأصل بيعة الآخر.
        $this->order(930002, self::SELLER);
        DB::table('orders')->where('id', 930002)->update(['owner_id' => self::OTHER]);

        $before = Seller::find(self::SELLER);
        $comm   = (float) $before->commission;
        $credit = (float) $before->credit;

        $response = $this->collect(930002, 100);

        $response->assertOk();

        $after = Seller::find(self::SELLER);

        $this->assertSame(
            round($comm, 2),
            round((float) $after->commission, 2),
            'تحصيل فاتورة غيره يجب ألّا يزيد عمولته.'
        );

        $this->assertSame(
            round($credit + 100, 2),
            round((float) $after->credit, 2),
            'المال في يده فيدخل عهدته ليورّده.'
        );
    }
}
