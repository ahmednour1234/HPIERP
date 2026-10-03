<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminSeller;
use App\Models\OrderOwnerLog;
use App\Models\Seller;
use App\Models\SellerHandover;
use App\Models\VanHandoverCount;
use App\Services\OrderOwnershipTransferService;
use App\Services\VanHandoverCountService;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * تسليم عهدة مندوب انتهت خدمته إلى من يخلفه.
 *
 * ثلاث خطوات مستقلة، كلٌّ منها تُنفَّذ وحدها:
 *   1) تسجيل التسليم: من سلّم لمن، ومتى.
 *   2) جرد العربية: ما سُلِّم فعلًا، والفرق كميةً لا مالًا.
 *   3) ترحيل الفواتير: تُنقل ملكيتها فيحصّلها الخلف من التطبيق.
 *
 * الترحيل يكتب فوق orders.owner_id، ولهذا يُسجَّل الأصل في
 * order_owner_logs قبل كل كتابة: بغيره يضيع من أصدر الفاتورة.
 */
class SellerHandoverController extends Controller
{
    public function __construct(
        private OrderOwnershipTransferService $transfers,
        private VanHandoverCountService $counts
    ) {
    }

    public function index(): View
    {
        $handovers = SellerHandover::with([
                'fromSeller:id,f_name,l_name,email,mandob_code',
                'toSeller:id,f_name,l_name,email,mandob_code',
            ])
            ->whereIn('from_seller_id', $this->visibleSellerIds())
            // القائمة أولًا: هي وحدها التي تنتظر إجراءً.
            ->orderByRaw('CASE WHEN ended_at IS NULL THEN 0 ELSE 1 END')
            ->latest('id')
            ->paginate(20);

        // عدد ما رُحِّل في كل تسليم، باستعلام واحد لا استعلام للصف.
        $moved = OrderOwnerLog::whereIn('handover_id', $handovers->pluck('id'))
            ->selectRaw('handover_id, COUNT(*) c')
            ->groupBy('handover_id')
            ->pluck('c', 'handover_id');

        return view('admin-views.handover.index', compact('handovers', 'moved'));
    }

    public function create(): View
    {
        return view('admin-views.handover.create', [
            'sellers' => $this->sellers(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'from_seller_id' => 'required|integer|exists:admins,id|different:to_seller_id',
            'to_seller_id'   => 'required|integer|exists:admins,id',
            'started_at'     => 'nullable|date',
            'note'           => 'nullable|string|max:2000',
        ], [
            'from_seller_id.different' => 'لا يُسلَّم المندوب إلى نفسه.',
        ]);

        $handover = SellerHandover::create([
            'from_seller_id' => $data['from_seller_id'],
            'to_seller_id'   => $data['to_seller_id'],
            'started_at'     => $data['started_at'] ?? now(),
            'note'           => $data['note'] ?? null,
            'created_by'     => Auth::guard('admin')->id(),
        ]);

        Toastr::success('تم تسجيل التسليم.');

        return redirect()->route('admin.handover.show', $handover->id);
    }

    public function show(int $id): View|RedirectResponse
    {
        $handover = $this->findVisible($id);

        if (!$handover) {
            Toastr::error('التسليم غير موجود.');
            return redirect()->route('admin.handover.index');
        }

        $fromSeller = (int) $handover->from_seller_id;

        return view('admin-views.handover.show', [
            'handover' => $handover,

            // الفواتير المرشَّحة للترحيل: غير المسدَّدة وغير المرتجعة.
            'transferable' => $this->transfers->transferable($fromSeller)
                ->with('customer:id,name')
                ->orderBy('id')
                ->get(['id', 'user_id', 'order_amount', 'transaction_reference', 'created_at']),

            'movedCount' => OrderOwnerLog::where('handover_id', $handover->id)->count(),
            'vanStock'   => $this->counts->vanStock($fromSeller),
            'vanCount'   => VanHandoverCount::with('items.product:id,name,product_code')
                ->where('handover_id', $handover->id)
                ->latest('id')
                ->first(),
        ]);
    }

    /** ترحيل الفواتير إلى الخلف. */
    public function transfer(Request $request, int $id): RedirectResponse
    {
        $handover = $this->findVisible($id);

        if (!$handover) {
            Toastr::error('التسليم غير موجود.');
            return redirect()->route('admin.handover.index');
        }

        $data = $request->validate([
            'order_ids'   => 'nullable|array',
            'order_ids.*' => 'integer',
            'reason'      => 'nullable|string|max:2000',
        ]);

        try {
            $moved = $this->transfers->transfer(
                (int) $handover->from_seller_id,
                (int) $handover->to_seller_id,
                $data['order_ids'] ?? null,
                $handover->id,
                Auth::guard('admin')->id(),
                $data['reason'] ?? null
            );
        } catch (\Throwable $e) {
            Toastr::error($e->getMessage());
            return redirect()->route('admin.handover.show', $handover->id);
        }

        $moved > 0
            ? Toastr::success("تم ترحيل {$moved} فاتورة.")
            : Toastr::warning('لا توجد فواتير قابلة للترحيل.');

        return redirect()->route('admin.handover.show', $handover->id);
    }

    /** التراجع عن الترحيل: الفواتير تعود إلى أصحابها. */
    public function undoTransfer(int $id): RedirectResponse
    {
        $handover = $this->findVisible($id);

        if (!$handover) {
            Toastr::error('التسليم غير موجود.');
            return redirect()->route('admin.handover.index');
        }

        $back = $this->transfers->undo($handover->id);

        Toastr::success("تمت إعادة {$back} فاتورة إلى صاحبها.");

        return redirect()->route('admin.handover.show', $handover->id);
    }

    /** فتح جرد العربية بما سُلِّم فعلًا. */
    public function storeCount(Request $request, int $id): RedirectResponse
    {
        $handover = $this->findVisible($id);

        if (!$handover) {
            Toastr::error('التسليم غير موجود.');
            return redirect()->route('admin.handover.index');
        }

        $data = $request->validate([
            'counted'   => 'nullable|array',
            'counted.*' => 'nullable|integer|min:0',
            'note'      => 'nullable|string|max:2000',
        ]);

        try {
            $this->counts->open(
                (int) $handover->from_seller_id,
                array_map('intval', $data['counted'] ?? []),
                $handover->id,
                Auth::guard('admin')->id(),
                $data['note'] ?? null
            );
        } catch (\Throwable $e) {
            Toastr::error($e->getMessage());
            return redirect()->route('admin.handover.show', $handover->id);
        }

        Toastr::success('تم تسجيل الجرد، وينتظر الاعتماد.');

        return redirect()->route('admin.handover.show', $handover->id);
    }

    /** اعتماد الجرد: المسلَّم يعود للمخزن والعربية تُفرَّغ. */
    public function approveCount(Request $request, int $id, int $countId): RedirectResponse
    {
        $data = $request->validate(['admin_note' => 'nullable|string|max:2000']);

        try {
            $this->counts->approve($countId, (int) Auth::guard('admin')->id(), $data['admin_note'] ?? null);
            Toastr::success('تم اعتماد الجرد.');
        } catch (\Throwable $e) {
            Toastr::error($e->getMessage());
        }

        return redirect()->route('admin.handover.show', $id);
    }

    public function rejectCount(Request $request, int $id, int $countId): RedirectResponse
    {
        $data = $request->validate(['admin_note' => 'nullable|string|max:2000']);

        try {
            $this->counts->reject($countId, (int) Auth::guard('admin')->id(), $data['admin_note'] ?? null);
            Toastr::success('تم رفض الجرد.');
        } catch (\Throwable $e) {
            Toastr::error($e->getMessage());
        }

        return redirect()->route('admin.handover.show', $id);
    }

    /** إنهاء التسليم: الصلاحية تنتهي والتاريخ يبقى. */
    public function end(int $id): RedirectResponse
    {
        $handover = $this->findVisible($id);

        if (!$handover) {
            Toastr::error('التسليم غير موجود.');
            return redirect()->route('admin.handover.index');
        }

        $handover->update(['ended_at' => now()]);

        Toastr::success('تم إنهاء التسليم.');

        return redirect()->route('admin.handover.index');
    }

    private function findVisible(int $id): ?SellerHandover
    {
        return SellerHandover::with([
                'fromSeller:id,f_name,l_name,email,mandob_code',
                'toSeller:id,f_name,l_name,email,mandob_code',
            ])
            ->whereIn('from_seller_id', $this->visibleSellerIds())
            ->find($id);
    }

    /** المناديب الذين يراهم هذا الأدمن. */
    private function visibleSellerIds(): \Illuminate\Support\Collection
    {
        $admin = Auth::guard('admin')->user();

        if ($admin && $admin->is_super) {
            return Seller::where('role', 'seller')->pluck('id');
        }

        return AdminSeller::where('admin_id', optional($admin)->id)->pluck('seller_id');
    }

    private function sellers()
    {
        return Seller::whereIn('id', $this->visibleSellerIds())
            ->orderBy('f_name')
            ->get(['id', 'f_name', 'l_name', 'email', 'mandob_code']);
    }
}
