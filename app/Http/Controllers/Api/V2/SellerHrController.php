<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\CourseSeller;
use App\Models\DevelopSeller;
use App\Models\Salary;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * شؤون المندوب: التقييم، التطوير، الكورسات، الطلبات، الإجازات.
 *
 * هذه البنود كانت متاحة في لوحة الإدارة فقط، فلا يراها المندوب في التطبيق.
 * كلها للقراءة عدا الطلبات والإجازات: المندوب يقدّمها من التطبيق ويراجعها
 * مديره من اللوحة.
 *
 * جدول develop_sellers يخدم ثلاثة بنود يفرّق بينها عمود type:
 *   0 = تطوير موظف (ملاحظات المدير)
 *   1 = طلبات موظف
 *   2 = طلبات إجازة
 *
 * وعمود active هو حالة الطلب: 0 معلّق، 1 مقبول، 2 مرفوض.
 */
class SellerHrController extends Controller
{
    use ApiResponse;

    private const TYPE_DEVELOPMENT = 0;
    private const TYPE_REQUEST     = 1;
    private const TYPE_LEAVE       = 2;

    /** تقييم المندوب الشهري: الدرجة وملاحظة المدير. */
    public function ratings(Request $request): JsonResponse
    {
        $request->validate([
            'month' => ['nullable', 'string', 'max:20'],
        ]);

        $rows = Salary::where('seller_id', $request->user()->id)
            ->when($request->input('month'), fn ($q, $m) => $q->where('month', $m))
            ->latest('id')
            ->paginate(...$this->paging($request));

        $rows->getCollection()->transform(fn ($r) => [
            'id'            => $r->id,
            'month'         => $r->month,
            'score'         => $r->score,
            'manager_note'  => $r->notemanager,
            'note'          => $r->note,
            'visits_target' => $r->number_of_visitors,
            'visits_done'   => $r->result_of_visitors,
            'work_days'     => $r->number_of_days,
            'created_at'    => optional($r->created_at)->toIso8601String(),
        ]);

        return $this->ok($rows, 'Ratings retrieved');
    }

    /** ملاحظات تطوير المندوب المكتوبة من مديره. */
    public function development(Request $request): JsonResponse
    {
        return $this->ok(
            $this->notes($request, self::TYPE_DEVELOPMENT),
            'Development notes retrieved'
        );
    }

    /** الكورسات المسندة للمندوب. */
    public function courses(Request $request): JsonResponse
    {
        $rows = CourseSeller::where('seller_id', $request->user()->id)
            ->latest('id')
            ->paginate(...$this->paging($request));

        $rows->getCollection()->transform(fn ($c) => [
            'id'         => $c->id,
            'name'       => $c->name,
            'link'       => $c->link,
            'image'      => $c->img,
            'image_url'  => $c->img ? asset('storage/' . $c->img) : null,
            'created_at' => optional($c->created_at)->toIso8601String(),
        ]);

        return $this->ok($rows, 'Courses retrieved');
    }

    public function requests(Request $request): JsonResponse
    {
        return $this->ok($this->notes($request, self::TYPE_REQUEST), 'Requests retrieved');
    }

    public function leaves(Request $request): JsonResponse
    {
        return $this->ok($this->notes($request, self::TYPE_LEAVE), 'Leave requests retrieved');
    }

    /** تقديم طلب موظف من التطبيق. */
    public function storeRequest(Request $request): JsonResponse
    {
        return $this->submit($request, self::TYPE_REQUEST, 'Request submitted');
    }

    /** تقديم طلب إجازة من التطبيق. */
    public function storeLeave(Request $request): JsonResponse
    {
        return $this->submit($request, self::TYPE_LEAVE, 'Leave request submitted');
    }

    // ------------------------------------------------------------------

    /** صفوف develop_sellers لنوع بعينه، تخصّ المندوب الحالي وحده. */
    private function notes(Request $request, int $type)
    {
        $request->validate([
            'status' => ['nullable', 'integer', 'in:0,1,2'],
            'from'   => ['nullable', 'date'],
            'to'     => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $rows = DevelopSeller::where('seller_id', $request->user()->id)
            ->where('type', $type)
            // status قد تكون 0 وهي قيمة صالحة، فلا يصلح فحص الامتلاء وحده.
            ->when(
                $request->input('status') !== null,
                fn ($q) => $q->where('active', (int) $request->input('status'))
            )
            ->when($request->input('from'), fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->input('to'), fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->with('admins:id,f_name,l_name')
            ->latest('id')
            ->paginate(...$this->paging($request));

        $rows->getCollection()->transform(fn ($r) => [
            'id'          => $r->id,
            'note'        => $r->note,
            'type'        => (int) $r->type,
            'status'      => (int) $r->active,
            'status_text' => $this->statusText((int) $r->active),
            'date'        => $r->date,
            'manager'     => $r->admins
                ? trim(($r->admins->f_name ?? '') . ' ' . ($r->admins->l_name ?? ''))
                : null,
            'created_at'  => optional($r->created_at)->toIso8601String(),
        ]);

        return $rows;
    }

    /** إنشاء طلب جديد باسم المندوب الحالي. */
    private function submit(Request $request, int $type, string $message): JsonResponse
    {
        $data = $request->validate([
            'note' => ['required', 'string', 'max:5000'],
            'date' => ['nullable', 'date'],
        ]);

        $row = DevelopSeller::create([
            'seller_id' => $request->user()->id,
            // admin_id يُملأ من اللوحة عند المراجعة؛ الطلب يبدأ بلا مراجِع.
            'admin_id'  => null,
            'note'      => $data['note'],
            'date'      => $data['date'] ?? null,
            'type'      => $type,
            'active'    => 0, // معلّق حتى يبتّ فيه المدير
        ]);

        return $this->created([
            'id'          => $row->id,
            'note'        => $row->note,
            'type'        => (int) $row->type,
            'status'      => 0,
            'status_text' => $this->statusText(0),
            'date'        => $row->date,
            'created_at'  => optional($row->created_at)->toIso8601String(),
        ], $message);
    }

    private function statusText(int $status): string
    {
        return match ($status) {
            1 => 'مقبول',
            2 => 'مرفوض',
            default => 'قيد المراجعة',
        };
    }

    /** ترقيم موحّد: limit + offset (رقم الصفحة) كباقي مسارات v2. */
    private function paging(Request $request): array
    {
        return [
            (int) $request->input('limit', 25),
            ['*'],
            'page',
            (int) $request->input('offset', 1),
        ];
    }
}
