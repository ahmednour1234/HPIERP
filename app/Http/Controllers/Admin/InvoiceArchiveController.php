<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminSeller;
use App\Models\Order;
use App\Models\Region;
use App\Services\InvoiceArchiveService;
use App\Traits\ExportsCsv;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * أرشيف الفواتير القديمة المكتملة.
 *
 * الأرشفة تُخفي الفاتورة من قوائم العمل اليومية فقط؛ التقارير والأرصدة
 * والقيود المحاسبية لا تتأثر، والتراجع متاح دائمًا.
 */
class InvoiceArchiveController extends Controller
{
    use ExportsCsv;

    public function __construct(private InvoiceArchiveService $archive) {}

    /** مناديب الإداري الحالي، لحصر ما يراه ويؤرشفه. */
    private function sellerIds(): array
    {
        $adminId = Auth::guard('admin')->id();

        $ids = AdminSeller::where('admin_id', $adminId)->pluck('seller_id')->all();
        $ids[] = $adminId;

        return array_values(array_unique($ids));
    }

    /** شاشة الأرشيف: المؤرشف فعليًا، مع عدّاد المؤهَّل للأرشفة. */
    public function index(Request $request)
    {
        $sellerIds  = $this->sellerIds();
        $minAgeDays = $request->filled('min_age_days')
            ? (int) $request->input('min_age_days')
            : InvoiceArchiveService::DEFAULT_MIN_AGE_DAYS;

        $query = Order::query()
            ->where('type', 4)
            ->archived()
            ->whereIn('owner_id', $sellerIds)
            ->with(['customer.regions', 'seller', 'archivedBy']);

        $this->applyFilters($query, $request);

        $orders = $query->latest('archived_at')
            ->paginate(25)
            ->appends($request->query());

        return view('admin-views.pos.order.archive', [
            'orders'     => $orders,
            'counts'     => $this->archive->counts($sellerIds, $minAgeDays),
            'minAgeDays' => $minAgeDays,
            'regions'    => Region::orderBy('name')->get(['id', 'name']),
            'sellers'    => \App\Models\Seller::whereIn('id', $sellerIds)->get(['id', 'email']),
        ]);
    }

    /** الفلاتر المشتركة بين العرض والتصدير. */
    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->input('from_date'));
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->input('to_date'));
        }

        // فلاتر متعددة الاختيار، على نمط باقي الشاشات.
        if ($regions = $this->multiFilter($request, 'region_id')) {
            $query->whereHas('customer', fn ($c) => $c->whereIn('region_id', $regions));
        }

        if ($sellers = $this->multiFilter($request, 'seller_id')) {
            $query->whereIn('owner_id', $sellers);
        }
    }

    /** أرشفة فاتورة واحدة. */
    public function archiveOne(Request $request, $id)
    {
        $order = Order::whereIn('owner_id', $this->sellerIds())->find($id);

        if (!$order) {
            Toastr::error(\App\CPU\translate('الفاتورة غير موجودة'));
            return back();
        }

        if ($this->archive->archive($order)) {
            Toastr::success(\App\CPU\translate('تمت أرشفة الفاتورة'));
        } else {
            // إما مؤرشفة سلفًا وإما غير مكتملة؛ الرسالة توضّح الحالة الثانية
            // لأنها هي التي تحتاج تفسيرًا.
            Toastr::warning(\App\CPU\translate(
                'لا يمكن أرشفة هذه الفاتورة: لم تُحصَّل بالكامل ولم تُرجَع بالكامل'
            ));
        }

        return back();
    }

    /** إعادة فاتورة من الأرشيف. */
    public function unarchiveOne(Request $request, $id)
    {
        $order = Order::whereIn('owner_id', $this->sellerIds())->find($id);

        if ($order && $this->archive->unarchive($order)) {
            Toastr::success(\App\CPU\translate('تمت إعادة الفاتورة من الأرشيف'));
        } else {
            Toastr::warning(\App\CPU\translate('الفاتورة غير مؤرشفة'));
        }

        return back();
    }

    /** أرشفة كل الفواتير المؤهَّلة دفعة واحدة. */
    public function archiveAll(Request $request)
    {
        $request->validate([
            'min_age_days' => 'nullable|integer|min:0|max:3650',
        ]);

        $minAgeDays = $request->filled('min_age_days')
            ? (int) $request->input('min_age_days')
            : InvoiceArchiveService::DEFAULT_MIN_AGE_DAYS;

        $count = $this->archive->archiveAllEligible($this->sellerIds(), $minAgeDays);

        $count > 0
            ? Toastr::success(\App\CPU\translate("تمت أرشفة {$count} فاتورة"))
            : Toastr::info(\App\CPU\translate('لا توجد فواتير مؤهَّلة للأرشفة'));

        return back();
    }

    /** الأرشيف كملف اكسيل، بنفس فلاتر الشاشة. */
    public function export(Request $request)
    {
        $query = Order::query()
            ->where('type', 4)
            ->archived()
            ->whereIn('owner_id', $this->sellerIds())
            ->with(['customer.regions', 'seller', 'archivedBy']);

        $this->applyFilters($query, $request);

        $rows = $query->latest('archived_at')->get()->map(fn ($o) => [
            'رقم الفاتورة'   => $o->id,
            'تاريخ الفاتورة' => optional($o->created_at)->format('Y-m-d H:i'),
            'العميل'         => $o->customer->name ?? '',
            'المنطقة'        => optional($o->customer->regions ?? null)->name ?? '',
            'المندوب'        => $o->seller->email ?? '',
            'قيمة الفاتورة'  => round((float) $o->order_amount, 2),
            'المحصل'         => round((float) $o->transaction_reference, 2),
            'سبب الأرشفة'    => $o->archive_reason,
            'تاريخ الأرشفة'  => optional($o->archived_at)->format('Y-m-d H:i'),
            'أرشفها'         => $o->archivedBy->email ?? '',
        ]);

        return $this->streamCsvRows($rows, $this->exportFilename('archived-invoices'));
    }
}
