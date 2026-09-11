<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminSeller;
use App\Models\StockReturnRequest;
use App\Services\Exceptions\InsufficientStockException;
use App\Services\Exceptions\ReturnRequestException;
use App\Services\StockReturnService;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * مراجعة طلبات إرجاع البضاعة التي يرسلها المناديب من التطبيق.
 *
 * الاعتماد وحده هو ما ينقل الكميات من العربية إلى المخزن؛ الرفض لا يمس
 * المخزون. نقل الكميات يعيش في StockReturnService فالتطبيق واللوحة
 * ينفذان القاعدة نفسها.
 */
class StockReturnRequestController extends Controller
{
    public function __construct(private StockReturnService $returns)
    {
    }

    public function index(Request $request): View|Factory|Application
    {
        $requests = StockReturnRequest::with(['items.product:id,name,product_code', 'seller:id,f_name,l_name'])
            ->whereIn('seller_id', $this->visibleSellerIds())
            // المعلّقة أولًا: هي وحدها التي تنتظر إجراءً.
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->latest('id')
            ->paginate(20);

        return view('admin-views.stock-returns.index', compact('requests'));
    }

    public function show(int $id): View|Factory|Application|RedirectResponse
    {
        $returnRequest = $this->findVisible($id);

        if (!$returnRequest) {
            Toastr::error('طلب الإرجاع غير موجود.');
            return redirect()->route('admin.stock-returns.index');
        }

        return view('admin-views.stock-returns.show', compact('returnRequest'));
    }

    public function approve(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate(['admin_note' => 'nullable|string|max:2000']);

        if (!$this->findVisible($id)) {
            Toastr::error('طلب الإرجاع غير موجود.');
            return redirect()->route('admin.stock-returns.index');
        }

        try {
            $this->returns->approve($id, (int) Auth::guard('admin')->id(), $data['admin_note'] ?? null);
            Toastr::success('تم اعتماد الطلب ونقل الكميات إلى المخزن.');
        } catch (InsufficientStockException | ReturnRequestException $e) {
            // لم يُكتب شيء: المعاملة تراجعت بالكامل.
            Toastr::error($e->getMessage());
        }

        return redirect()->route('admin.stock-returns.show', $id);
    }

    public function reject(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate(['admin_note' => 'required|string|max:2000'], [
            'admin_note.required' => 'اكتب سبب الرفض.',
        ]);

        if (!$this->findVisible($id)) {
            Toastr::error('طلب الإرجاع غير موجود.');
            return redirect()->route('admin.stock-returns.index');
        }

        try {
            $this->returns->reject($id, (int) Auth::guard('admin')->id(), $data['admin_note']);
            Toastr::success('تم رفض الطلب. المخزون لم يتغير.');
        } catch (ReturnRequestException $e) {
            Toastr::error($e->getMessage());
        }

        return redirect()->route('admin.stock-returns.show', $id);
    }

    private function findVisible(int $id): ?StockReturnRequest
    {
        return StockReturnRequest::with(['items.product:id,name,product_code', 'seller:id,f_name,l_name'])
            ->whereIn('seller_id', $this->visibleSellerIds())
            ->find($id);
    }

    /**
     * المناديب الذين يراهم هذا الأدمن.
     *
     * السوبر أدمن بلا صفوف في admin_sellers، فقصر القائمة عليها وحدها
     * كان يخفي عنه كل الطلبات.
     */
    private function visibleSellerIds(): \Illuminate\Support\Collection
    {
        $admin = Auth::guard('admin')->user();

        if ($admin && $admin->is_super) {
            return StockReturnRequest::distinct()->pluck('seller_id');
        }

        return AdminSeller::where('admin_id', optional($admin)->id)->pluck('seller_id');
    }
}
