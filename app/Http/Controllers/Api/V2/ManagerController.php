<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AdminSeller;
use App\Models\Attendance;
use App\Models\CourseSeller;
use App\Models\DevelopSeller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * واجهة المدير: مناديبه وبصماتهم وملاحظاته عليهم.
 *
 * المدير يُميَّز بـ admins.type = 'manager' لا بعمود role — الأخير يبقى
 * 'seller' لحسابات المديرين، فالاعتماد عليه وحده يجعل التطبيق يراهم
 * مناديب عاديين.
 *
 * كل مسار هنا مقصور على مناديب المدير الحالي عبر admin_sellers، فلا يرى
 * مدير بيانات مناديب مدير آخر.
 */
class ManagerController extends Controller
{
    use ApiResponse;

    /**
     * قائمة/خريطة المناديب: آخر موقع وحالة اليوم لكل مندوب.
     */
    public function sellers(Request $request): JsonResponse
    {
        $request->validate([
            'region_id' => ['nullable'],
            'search'    => ['nullable', 'string', 'max:100'],
        ]);

        if (!$this->isManager($request)) {
            return $this->fail('This account is not a manager', 403);
        }

        $sellerIds = $this->mySellerIds($request);

        if (empty($sellerIds)) {
            return $this->ok([], 'No sellers assigned');
        }

        $query = Admin::whereIn('id', $sellerIds);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('f_name', 'like', "%{$search}%")
                  ->orWhere('l_name', 'like', "%{$search}%")
                  ->orWhere('mandob_code', 'like', "%{$search}%");
            });
        }

        if ($regionIds = array_filter((array) $request->input('region_id', []))) {
            $query->whereIn('id', function ($q) use ($regionIds) {
                $q->select('seller_id')->from('seller_regions')->whereIn('region_id', $regionIds);
            });
        }

        $sellers = $query->get([
            'id', 'f_name', 'l_name', 'phone', 'image', 'mandob_code', 'latitude', 'longitude',
            'score', 'note',
        ]);

        // بصمة اليوم لكل المناديب في استعلام واحد بدل استعلام لكل مندوب.
        $today = Attendance::whereIn('admin_id', $sellers->pluck('id'))
            ->whereDate('date', now()->toDateString())
            ->get()
            ->keyBy('admin_id');

        $rows = $sellers->map(function ($s) use ($today) {
            $att = $today->get($s->id);

            return [
                'id'          => $s->id,
                'name'        => trim(($s->f_name ?? '') . ' ' . ($s->l_name ?? '')),
                'mandob_code' => $s->mandob_code,
                'phone'       => $s->phone,
                'image'       => $s->image,
                'image_url'   => $s->image ? asset('storage/' . $s->image) : null,

                // آخر موقع معروف: أعمدة الموقع على حساب المندوب نفسه.
                'last_location' => $this->location($s->latitude, $s->longitude, $s->updated_at ?? null),

                // التقييم الحالي كما تكتبه شاشة التقييم في اللوحة.
                'rating'      => (float) $s->score,
                'rating_note' => $s->note,

                'today_status'    => $this->todayStatus($att),
                'today_check_in'  => $att->check_in ?? null,
                'today_check_out' => $att->check_out ?? null,
            ];
        });

        return $this->ok($rows, 'Sellers retrieved');
    }

    /** سجل بصمة مندوب بعينه، بشرط أن يكون تابعًا للمدير الحالي. */
    public function sellerAttendance(Request $request, int $sellerId): JsonResponse
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to'   => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        if (!$this->owns($request, $sellerId)) {
            return $this->fail('This seller is not assigned to you', 403);
        }

        $rows = Attendance::where('admin_id', $sellerId)
            ->when($request->input('from'), fn ($q, $d) => $q->whereDate('date', '>=', $d))
            ->when($request->input('to'), fn ($q, $d) => $q->whereDate('date', '<=', $d))
            ->latest('date')
            ->paginate(
                (int) $request->input('limit', 25),
                ['*'],
                'page',
                (int) $request->input('offset', 1)
            );

        $rows->getCollection()->transform(fn ($a) => [
            'id'         => $a->id,
            'date'       => $a->date,
            'check_in'   => $a->check_in,
            'check_out'  => $a->check_out,
            'status'     => (int) $a->status,
            // العمودان late/lang يحملان خط العرض والطول رغم تسميتهما.
            'location'   => $this->location($a->late, $a->lang, $a->created_at),
            'time_late'  => (int) $a->time_late,
            'worked_hours'   => (float) $a->worked_hours,
            'expected_hours' => (float) $a->expected_hours,
            'note'       => $a->note,
            'created_at' => optional($a->created_at)->toIso8601String(),
        ]);

        return $this->ok($rows, 'Attendance retrieved');
    }

    /** المدير يكتب ملاحظة على مندوبه. */
    public function storeNote(Request $request, int $sellerId): JsonResponse
    {
        $data = $request->validate([
            'note' => ['required', 'string', 'max:5000'],
            'date' => ['nullable', 'date'],
        ]);

        if (!$this->owns($request, $sellerId)) {
            return $this->fail('This seller is not assigned to you', 403);
        }

        // النوع 0 في develop_sellers هو "تطوير موظف" أي ملاحظات المدير،
        // وهو ما يقرأه المندوب من GET /hr/development.
        $row = DevelopSeller::create([
            'admin_id'  => (int) $request->user()->id,
            'seller_id' => $sellerId,
            'note'      => $data['note'],
            'date'      => $data['date'] ?? null,
            'type'      => 0,
            'active'    => 1,
        ]);

        return $this->created($this->noteRow($row, $request->user()), 'Note saved');
    }

    /** ملاحظات المدير على مندوب بعينه. */
    public function sellerNotes(Request $request, int $sellerId): JsonResponse
    {
        if (!$this->owns($request, $sellerId)) {
            return $this->fail('This seller is not assigned to you', 403);
        }

        $rows = DevelopSeller::where('seller_id', $sellerId)
            ->where('type', 0)
            ->with('admins:id,f_name,l_name')
            ->latest('id')
            ->paginate(
                (int) $request->input('limit', 25),
                ['*'],
                'page',
                (int) $request->input('offset', 1)
            );

        $rows->getCollection()->transform(fn ($r) => $this->noteRow($r, $r->admins));

        return $this->ok($rows, 'Notes retrieved');
    }

    // ------------------------------------------------------------------
    // التقييم: درجة المندوب الحالية وملاحظتها.
    //
    // تُخزَّن على حساب المندوب نفسه (admins.score / admins.note)، وهو ما
    // تكتبه شاشة التقييم في اللوحة، فالتطبيق واللوحة يقرآن ويكتبان نفس
    // القيمة. تقييم كشف الراتب الشهري شيء آخر يعيش في salaries.score.
    // ------------------------------------------------------------------

    /** تقييم مندوب: الدرجة والملاحظة. */
    public function sellerRating(Request $request, int $sellerId): JsonResponse
    {
        if (!$this->owns($request, $sellerId)) {
            return $this->fail('This seller is not assigned to you', 403);
        }

        $seller = Admin::find($sellerId, ['id', 'f_name', 'l_name', 'score', 'note', 'updated_at']);

        if (!$seller) {
            return $this->fail('Seller not found', 404);
        }

        return $this->ok($this->ratingRow($seller), 'Rating retrieved');
    }

    /** كتابة تقييم المندوب. */
    public function storeRating(Request $request, int $sellerId): JsonResponse
    {
        $data = $request->validate([
            'score' => ['required', 'numeric', 'between:0,100'],
            'note'  => ['nullable', 'string', 'max:5000'],
        ]);

        if (!$this->owns($request, $sellerId)) {
            return $this->fail('This seller is not assigned to you', 403);
        }

        $seller = Admin::find($sellerId);

        if (!$seller) {
            return $this->fail('Seller not found', 404);
        }

        // العمودان varchar في هذا المخطط، فنكتب نصًا كما تفعل اللوحة.
        $seller->score = (string) $data['score'];

        // note اختيارية هنا بعكس اللوحة، فلا نمسح ملاحظة قائمة بإغفالها.
        if (array_key_exists('note', $data)) {
            $seller->note = $data['note'];
        }

        $seller->save();

        return $this->ok($this->ratingRow($seller), 'Rating saved');
    }

    private function ratingRow($seller): array
    {
        return [
            'seller' => [
                'id'   => $seller->id,
                'name' => trim(($seller->f_name ?? '') . ' ' . ($seller->l_name ?? '')),
            ],
            'score'      => (float) $seller->score,
            'note'       => $seller->note,
            'updated_at' => optional($seller->updated_at)->toIso8601String(),
        ];
    }

    // ------------------------------------------------------------------
    // الكورسات: المدير يسندها لمناديبه من التطبيق.
    //
    // كانت تُدار من اللوحة وحدها؛ المندوب يقرأ كورساته من GET /hr/courses.
    // الملكية مزدوجة هنا: الكورس يخص المدير الذي أنشأه (admin_id)، ولا
    // يُسند إلا لمندوب مسند له فعلًا، فلا يكتب مدير على مناديب غيره.
    // ------------------------------------------------------------------

    /** كورسات مناديب هذا المدير. */
    public function courses(Request $request): JsonResponse
    {
        $request->validate([
            'seller_id' => ['nullable', 'integer'],
            'search'    => ['nullable', 'string', 'max:100'],
        ]);

        if (!$this->isManager($request)) {
            return $this->fail('This account is not a manager', 403);
        }

        $query = CourseSeller::where('admin_id', (int) $request->user()->id)
            ->with('sellers:id,f_name,l_name');

        if (($sellerId = $request->input('seller_id')) !== null) {
            if (!$this->owns($request, (int) $sellerId)) {
                return $this->fail('This seller is not assigned to you', 403);
            }

            $query->where('seller_id', (int) $sellerId);
        }

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $rows = $query->latest('id')->paginate(
            (int) $request->input('limit', 25),
            ['*'],
            'page',
            (int) $request->input('offset', 1)
        );

        $rows->getCollection()->transform(fn ($c) => $this->courseRow($c));

        return $this->ok($rows, 'Courses retrieved');
    }

    /** كورس واحد. */
    public function showCourse(Request $request, int $id): JsonResponse
    {
        $course = $this->findOwnCourse($request, $id);

        if (!$course) {
            return $this->fail('Course not found', 404);
        }

        return $this->ok($this->courseRow($course), 'Course retrieved');
    }

    /** إسناد كورس لمندوب. */
    public function storeCourse(Request $request): JsonResponse
    {
        $data = $request->validate($this->courseRules());

        if (!$this->owns($request, (int) $data['seller_id'])) {
            return $this->fail('This seller is not assigned to you', 403);
        }

        $course = CourseSeller::create([
            'admin_id'  => (int) $request->user()->id,
            'seller_id' => (int) $data['seller_id'],
            'name'      => $data['name'],
            'link'      => $data['link'] ?? null,
            'img'       => $this->courseImage($request),
        ]);

        return $this->created($this->courseRow($course->load('sellers:id,f_name,l_name')), 'Course created');
    }

    /** تعديل كورس. */
    public function updateCourse(Request $request, int $id): JsonResponse
    {
        $course = $this->findOwnCourse($request, $id);

        if (!$course) {
            return $this->fail('Course not found', 404);
        }

        $data = $request->validate($this->courseRules(partial: true));

        if (array_key_exists('seller_id', $data)) {
            if (!$this->owns($request, (int) $data['seller_id'])) {
                return $this->fail('This seller is not assigned to you', 403);
            }

            $course->seller_id = (int) $data['seller_id'];
        }

        if (array_key_exists('name', $data)) {
            $course->name = $data['name'];
        }

        // link يقبل null صراحةً لمسح الرابط، فنفحص وجود المفتاح لا امتلاءه.
        if (array_key_exists('link', $data)) {
            $course->link = $data['link'];
        }

        if ($image = $this->courseImage($request)) {
            $course->img = $image;
        }

        $course->save();

        return $this->ok($this->courseRow($course->load('sellers:id,f_name,l_name')), 'Course updated');
    }

    /** حذف كورس. */
    public function destroyCourse(Request $request, int $id): JsonResponse
    {
        $course = $this->findOwnCourse($request, $id);

        if (!$course) {
            return $this->fail('Course not found', 404);
        }

        $course->delete();

        return $this->ok(['id' => $id], 'Course deleted');
    }

    /**
     * كورس يملكه هذا المدير، أو null.
     *
     * نرد 404 لا 403 على كورس مدير آخر حتى لا يكشف الرد وجوده.
     */
    private function findOwnCourse(Request $request, int $id): ?CourseSeller
    {
        return CourseSeller::where('admin_id', (int) $request->user()->id)
            ->with('sellers:id,f_name,l_name')
            ->find($id);
    }

    private function courseRules(bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return [
            'seller_id' => [$required, 'integer', 'exists:admins,id'],
            'name'      => [$required, 'string', 'max:500'],
            'link'      => ['nullable', 'url', 'max:65535'],
            'image'     => ['nullable', 'image', 'max:4096'],
        ];
    }

    /** يحفظ الصورة إن أُرسلت ويرجع مسارها المخزَّن. */
    private function courseImage(Request $request): ?string
    {
        if (!$request->hasFile('image')) {
            return null;
        }

        // Helpers::upload تحفظ داخل المجلد وترجع اسم الملف وحده، فنضيف
        // المجلد ليكون المخزَّن صالحًا لبناء الرابط منه مباشرة.
        return 'course/' . \App\CPU\Helpers::upload('course/', 'png', $request->file('image'));
    }

    private function courseRow(CourseSeller $c): array
    {
        // العمود من نوع json في المخطط، فقد يعود مصفوفة من صفوف قديمة.
        $img = is_array($c->img) ? ($c->img[0] ?? null) : $c->img;

        return [
            'id'         => $c->id,
            'name'       => $c->name,
            'link'       => $c->link,
            'image'      => $img,
            'image_url'  => $img ? asset('storage/' . $img) : null,
            'seller'     => $c->sellers ? [
                'id'   => $c->sellers->id,
                'name' => trim($c->sellers->f_name . ' ' . $c->sellers->l_name),
            ] : null,
            'created_at' => optional($c->created_at)->toIso8601String(),
        ];
    }

    /**
     * ملاحظات المدير كما يراها المندوب المسجّل.
     *
     * مسار منفصل عن /hr/development ليطابق ما يتوقعه التطبيق، ويعرض اسم
     * المدير صراحةً.
     */
    public function myManagerNotes(Request $request): JsonResponse
    {
        $rows = DevelopSeller::where('seller_id', $request->user()->id)
            ->where('type', 0)
            ->with('admins:id,f_name,l_name')
            ->latest('id')
            ->paginate(
                (int) $request->input('limit', 25),
                ['*'],
                'page',
                (int) $request->input('offset', 1)
            );

        $rows->getCollection()->transform(fn ($r) => $this->noteRow($r, $r->admins));

        return $this->ok($rows, 'Manager notes retrieved');
    }

    // ------------------------------------------------------------------

    private function noteRow($row, $manager): array
    {
        return [
            'id'           => $row->id,
            'seller_id'    => $row->seller_id,
            'manager_id'   => $row->admin_id,
            'manager_name' => $manager
                ? trim(($manager->f_name ?? '') . ' ' . ($manager->l_name ?? ''))
                : null,
            'note'         => $row->note,
            'date'         => $row->date,
            'created_at'   => optional($row->created_at)->toIso8601String(),
        ];
    }

    /** إحداثيات بصيغة موحّدة، أو null حين لا تكون مسجَّلة. */
    private function location($lat, $lng, $at): ?array
    {
        if (blank($lat) || blank($lng) || (float) $lat === 0.0) {
            return null;
        }

        return [
            'latitude'   => (float) $lat,
            'longitude'  => (float) $lng,
            'updated_at' => $at ? \Carbon\Carbon::parse($at)->toIso8601String() : null,
        ];
    }

    /** حالة اليوم للتلوين على الخريطة. */
    private function todayStatus($attendance): string
    {
        if (!$attendance || blank($attendance->check_in)) {
            return 'absent';
        }

        return blank($attendance->check_out) ? 'present' : 'checked_out';
    }

    private function isManager(Request $request): bool
    {
        // المدير يُعرَّف بـ type = 'manager'؛ عمود role يبقى 'seller' لهذه
        // الحسابات. نقبل أيضًا الأدمن الكامل ومن لديه مناديب مسندون.
        $user = $request->user();

        return $user->type === 'manager'
            || $user->role === 'admin'
            || AdminSeller::where('admin_id', $user->id)->exists();
    }

    private function mySellerIds(Request $request): array
    {
        return AdminSeller::where('admin_id', $request->user()->id)
            ->pluck('seller_id')
            ->map(fn ($v) => (int) $v)
            ->all();
    }

    private function owns(Request $request, int $sellerId): bool
    {
        return in_array($sellerId, $this->mySellerIds($request), true);
    }
}
