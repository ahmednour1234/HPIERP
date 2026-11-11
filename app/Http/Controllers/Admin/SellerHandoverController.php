<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminSeller;
use App\Models\OrderOwnerLog;
use App\Models\Seller;
use App\Models\SellerHandover;
use App\Models\VanHandoverCount;
use App\Services\OrderOwnershipTransferService;
use App\Services\SellerAssignmentTransferService;
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
        private VanHandoverCountService $counts,
        private SellerAssignmentTransferService $assignments
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

    /**
     * معاينة التسليم قبل تسجيله، للقراءة فقط.
     *
     * تعرض ما سيُنقل فعلًا في الخطوتين التاليتين: الفواتير غير المسدَّدة
     * (الترحيل) ومخزون العربية (الجرد)، وما يصير مع المستلم بعدهما.
     */
    public function preview(Request $request)
    {
        $data = $request->validate([
            'from'  => 'required|integer',
            'to'    => 'nullable|integer',
            'stock' => 'nullable|in:0,1',
        ]);
        $includeStock = ($data['stock'] ?? '1') === '1';

        $visible = $this->visibleSellerIds()->map(fn ($id) => (int) $id);
        $from    = (int) $data['from'];
        $to      = isset($data['to']) && (int) $data['to'] !== $from ? (int) $data['to'] : null;

        if (!$visible->contains($from) || ($to && !$visible->contains($to))) {
            abort(403);
        }

        $side = function (int $sellerId) {
            $orders = $this->transfers->transferable($sellerId)
                ->with('customer:id,name')
                ->orderBy('id')
                ->get(['id', 'user_id', 'order_amount', 'transaction_reference', 'created_at']);

            return [
                'orders'    => $orders,
                'remaining' => round($orders->sum(
                    fn ($o) => max(0, (float) $o->order_amount - (float) $o->transaction_reference)
                ), 2),
                'stock'     => $this->counts->vanStock($sellerId),
            ];
        };

        $fromSide = $side($from);
        $toSide   = $to ? $side($to) : null;

        // جدول الأصناف موحَّد: كل صنف عند أيٍّ منهما، بكميته عند كلٍّ.
        $products = collect();
        foreach ([['from', $fromSide], ['to', $toSide]] as [$key, $s]) {
            foreach ($s['stock'] ?? [] as $row) {
                $p = $products->get($row->product_id, [
                    'name' => optional($row->product)->name ?? ('#' . $row->product_id),
                    'code' => optional($row->product)->product_code,
                    'from' => 0,
                    'to'   => 0,
                ]);
                $p[$key] += (int) $row->stock;
                $products->put($row->product_id, $p);
            }
        }

        return view('admin-views.handover._preview', [
            'fromSide'     => $fromSide,
            'toSide'       => $toSide,
            'products'     => $products->sortBy('name')->values(),
            'includeStock' => $includeStock,
        ]);
    }

    /**
     * نقل العملاء والمناطق والأقسام المختارة إلى المستلم.
     *
     * لا يُنقل شيء لم يُختر صراحةً، وكل نقل يُسجَّل في assignments.
     */
    public function moveAssignments(Request $request, int $id): RedirectResponse
    {
        $handover = $this->findVisible($id);

        if (!$handover) {
            Toastr::error('التسليم غير موجود.');
            return redirect()->route('admin.handover.index');
        }

        $moved = $this->assignments->move(
            (int) $handover->from_seller_id,
            (int) $handover->to_seller_id,
            $this->validatedSelection($request),
            $request->boolean('remove')
        );

        if (!$moved) {
            Toastr::warning('لم يُختر شيء للنقل.');
            return back();
        }

        $this->log($handover, array_merge(['remove' => $request->boolean('remove')], $moved));
        Toastr::success($this->movedMessage($moved));

        return back();
    }

    /** بضاعة العربية: تصفير وإرجاع للمخزن، أو نقل لعربية المستلم. */
    public function stock(Request $request, int $id): RedirectResponse
    {
        $handover = $this->findVisible($id);

        if (!$handover) {
            Toastr::error('التسليم غير موجود.');
            return redirect()->route('admin.handover.index');
        }

        $action = $request->validate(['action' => 'required|in:return,move'])['action'];

        if ($action === 'move' && !$handover->include_stock) {
            Toastr::error('هذا التسليم بلا بضاعة للمستلم.');
            return back();
        }

        try {
            $result = $action === 'return'
                ? $this->counts->returnAllToWarehouse((int) $handover->from_seller_id)
                : $this->counts->moveAllTo((int) $handover->from_seller_id, (int) $handover->to_seller_id);
        } catch (\Throwable $e) {
            Toastr::error($e->getMessage());
            return back();
        }

        if ($result['units'] === 0) {
            Toastr::warning('العربية فارغة بالفعل.');
            return back();
        }

        $this->log($handover, ['stock' => $action] + $result);

        Toastr::success(sprintf(
            $action === 'return'
                ? 'صُفِّرت العربية وعاد %d صنف (%d وحدة) للمخزن.'
                : 'نُقل %d صنف (%d وحدة) لعربية المستلم.',
            $result['products'],
            $result['units']
        ));

        return back();
    }

    /** شاشة النقل المستقلة: عملاء ومناطق وأقسام من مندوب لآخر، بلا تسليم. */
    public function reassign(Request $request): View
    {
        $visible = $this->visibleSellerIds()->map(fn ($v) => (int) $v);
        $from    = (int) $request->input('from');
        $to      = (int) $request->input('to');

        $from = $visible->contains($from) ? $from : 0;
        $to   = $visible->contains($to) && $to !== $from ? $to : 0;

        return view('admin-views.handover.reassign', [
            'sellers' => $this->sellers(),
            'from'    => $from,
            'to'      => $to,
            'assign'  => $from ? $this->assignments->listFor($from, $to ?: null) : null,
        ]);
    }

    public function storeReassign(Request $request): RedirectResponse
    {
        $request->validate([
            'from' => 'required|integer|different:to',
            'to'   => 'required|integer',
        ], ['from.different' => 'اختر مندوبين مختلفين.']);

        $visible = $this->visibleSellerIds()->map(fn ($v) => (int) $v);
        $from    = (int) $request->input('from');
        $to      = (int) $request->input('to');

        if (!$visible->contains($from) || !$visible->contains($to)) {
            abort(403);
        }

        $moved = $this->assignments->move($from, $to, $this->validatedSelection($request), $request->boolean('remove'));

        $moved
            ? Toastr::success($this->movedMessage($moved))
            : Toastr::warning('لم يُختر شيء للنقل.');

        return redirect()->route('admin.handover.reassign', ['from' => $from, 'to' => $to]);
    }

    /** @return array<string, array<int,int>> */
    private function validatedSelection(Request $request): array
    {
        $data = $request->validate([
            'customers'    => 'nullable|array',
            'customers.*'  => 'integer',
            'regions'      => 'nullable|array',
            'regions.*'    => 'integer',
            'categories'   => 'nullable|array',
            'categories.*' => 'integer',
            'remove'       => 'nullable|boolean',
        ]);

        return array_intersect_key($data, SellerAssignmentTransferService::SETS);
    }

    private function movedMessage(array $moved): string
    {
        return sprintf(
            'تم النقل: %d عميل، %d منطقة، %d قسم.',
            count($moved['customers'] ?? []),
            count($moved['regions'] ?? []),
            count($moved['categories'] ?? [])
        );
    }

    /** يضيف حدثًا لسجلّ التسليم (assignments). */
    private function log(SellerHandover $handover, array $entry): void
    {
        $log   = $handover->assignments ?? [];
        $log[] = ['at' => now()->toDateTimeString(), 'by' => Auth::guard('admin')->id()] + $entry;
        $handover->assignments = $log;
        $handover->save();
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
            // مربع غير معلَّم لا يُرسَل، فغيابه يعني «لا تُسلَّم البضاعة».
            'include_stock'  => $request->boolean('include_stock'),
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

            // ما زال مع صاحب العهدة من تعيينات، والمستلم يملك منها ماذا.
            'assign' => $this->assignments->listFor($fromSeller, (int) $handover->to_seller_id),
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
