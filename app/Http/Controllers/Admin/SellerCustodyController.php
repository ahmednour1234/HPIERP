<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminSeller;
use App\Models\Seller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * عهدة المناديب: ما حصّله كلٌّ منهم مقابل ما ورّده.
 *
 * العمود admins.credit رقمٌ تراكمي تكتبه نقاط كثيرة عبر سنين، وقد
 * جانَبَ الصواب في بعض الحسابات. هذه الشاشة لا تصلحه ولا تكتب شيئًا:
 * تحسب العهدة من سجلّاتها الأصلية وتعرض الفرق، ليراه المحاسب ويقرر.
 *
 * مصادر الحساب — وهي صريحة عمدًا:
 *   التحصيل = الأقساط (installments)
 *           + المبيعات النقدية المحصَّلة على فواتيره
 *   التوريد = إيداعات المندوب المعتمدة (transaction_sellers.active = 1)
 *
 * ولا يُستعمل transections.tran_type = 26: يكتبه ستة مواضع مختلفة
 * (عميل، مصنع، مندوب، مورّد، توريد، قسط)، فمجموعه يفوق إجمالي
 * المبيعات كلها ولا يدلّ على شيء.
 */
class SellerCustodyController extends Controller
{
    /** إيداع معتمد. */
    private const DEPOSIT_APPROVED = 1;

    /** فاتورة نقدية. */
    private const CASH = 1;

    public function index(Request $request): View
    {
        $sellerIds = $this->visibleSellerIds();

        $sellers = Seller::whereIn('id', $sellerIds)
            ->orderBy('f_name')
            ->get(['id', 'f_name', 'l_name', 'email', 'mandob_code', 'credit', 'commission']);

        // ثلاثة استعلامات مجمَّعة لا ثلاثة لكل مندوب.
        $instalments = DB::table('installments')
            ->whereIn('seller_id', $sellerIds)
            ->selectRaw('seller_id, SUM(total_price + 0) total')
            ->groupBy('seller_id')
            ->pluck('total', 'seller_id');

        $cash = DB::table('orders')
            ->whereIn('owner_id', $sellerIds)
            ->where('cash', self::CASH)
            ->selectRaw('owner_id, SUM(COALESCE(transaction_reference, 0) + 0) total')
            ->groupBy('owner_id')
            ->pluck('total', 'owner_id');

        $deposits = DB::table('transaction_sellers')
            ->whereIn('seller_id', $sellerIds)
            ->where('active', self::DEPOSIT_APPROVED)
            ->selectRaw('seller_id, SUM(amount + 0) total')
            ->groupBy('seller_id')
            ->pluck('total', 'seller_id');

        $pending = DB::table('transaction_sellers')
            ->whereIn('seller_id', $sellerIds)
            ->where('active', 0)
            ->selectRaw('seller_id, SUM(amount + 0) total')
            ->groupBy('seller_id')
            ->pluck('total', 'seller_id');

        $rows = $sellers->map(function (Seller $seller) use ($instalments, $cash, $deposits, $pending) {
            $id        = (int) $seller->id;
            $collected = (float) ($instalments[$id] ?? 0) + (float) ($cash[$id] ?? 0);
            $deposited = (float) ($deposits[$id] ?? 0);
            $expected  = round($collected - $deposited, 2);
            $recorded  = round((float) $seller->credit, 2);

            return [
                'id'          => $id,
                'name'        => trim($seller->f_name . ' ' . $seller->l_name) ?: $seller->email,
                'code'        => $seller->mandob_code,
                'instalments' => round((float) ($instalments[$id] ?? 0), 2),
                'cash'        => round((float) ($cash[$id] ?? 0), 2),
                'collected'   => round($collected, 2),
                'deposited'   => $deposited,
                'pending'     => round((float) ($pending[$id] ?? 0), 2),
                'expected'    => $expected,
                'recorded'    => $recorded,
                'gap'         => round($recorded - $expected, 2),
            ];
        })
        // الأبعد عن حسابه أولًا: هو ما يستدعي النظر.
        ->sortByDesc(fn (array $row) => abs($row['gap']))
        ->values();

        return view('admin-views.handover.custody', compact('rows'));
    }

    private function visibleSellerIds(): \Illuminate\Support\Collection
    {
        $admin = Auth::guard('admin')->user();

        if ($admin && $admin->is_super) {
            return Seller::where('role', 'seller')->pluck('id');
        }

        return AdminSeller::where('admin_id', optional($admin)->id)->pluck('seller_id');
    }
}
