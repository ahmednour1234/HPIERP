<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AttendanceController extends Controller
{
public function storeAttendance(Request $request)
{
    // الحالة: 1 => دخول، 2 => خروج، 3 => منسية
    // الحارس: admin-api

    // ===== 0) تحقق أساسي من المدخلات =====
    $request->validate([
        'status' => 'required|in:1,2',
        'note'   => 'nullable|string|max:500',
        'lat'    => 'nullable|numeric',
        'lon'    => 'nullable|numeric',
    ]);

    // ===== 1) جلب المسؤول الحالي =====
    $admin = Auth::guard('admin-api')->user();
    if (!$admin) {
        return response()->json(['success' => false, 'message' => 'غير مصرح.'], 401);
    }
    $adminId = (int) $admin->id;

    // ===== 2) فكّ shift_id لأي صيغة (JSON نصّي أو JSON داخل JSON) =====
    $decoded = json_decode($admin->shift_id, true);
    if (is_string($decoded)) {
        $decoded = json_decode($decoded, true);
    }
    $shiftIds = is_array($decoded) ? array_values(array_filter($decoded, fn($v) => is_numeric($v))) : [];
    $shiftIds = array_map('intval', $shiftIds);

    // جلب الورديات
    $shifts = Shift::whereIn('id', $shiftIds)->get();
    if ($shifts->isEmpty()) {
        return response()->json([
            'success' => false,
            'message' => 'لا توجد ورديات مخصصة لهذا المستخدم.',
        ], 400);
    }

    $now        = Carbon::now();
    $today      = $now->toDateString();
    $status     = (int) $request->status;
    $cutoff1106 = $now->copy()->setTimeFromTimeString('11:06'); // حد الزيادة لأول دخول

    // ===== 3) تحديد الوردية المناسبة الآن (يدعم عبور منتصف الليل) =====
    $selectedShift = null;
    foreach ($shifts as $shift) {
        $start = Carbon::createFromFormat('H:i:s', $shift->start)->setDate($now->year, $now->month, $now->day);
        $end   = Carbon::createFromFormat('H:i:s', $shift->end)->setDate($now->year, $now->month, $now->day);

        // لو النهاية <= البداية يبقى الوردية عابرة لمنتصف الليل
        if ($end->lte($start)) {
            $end->addDay();
        }

        $thresholdEnd = (clone $end)->addMinutes((int) $shift->max_minutes);

        // يعتبر صالحًا للدخول قبل البدء، وصالحًا للخروج حتى نهاية نافذة السماح
        if ($now->lt($start) || $now->between($start, $thresholdEnd)) {
            $selectedShift = (object)[
                'model'        => $shift,
                'start'        => $start,
                'end'          => $end,
                'thresholdEnd' => $thresholdEnd,
                'expectedMins' => $start->diffInMinutes($end),
            ];
            break;
        }
    }

    if (!$selectedShift) {
        return response()->json([
            'success' => false,
            'message' => 'أنت خارج نافذة أي من وردياتك المحددة.',
        ], 400);
    }

    $S     = $selectedShift;
    $shift = $S->model;

    // تجهيز الإحداثيات (مطابقة أسمائك الحالية في الجدول: lang ← lon, late ← lat)
    $lat = $request->input('lat');
    $lon = $request->input('lon');

    // ===== 4) دخول =====
    if ($status === 1) {
        // هل في جلسة مفتوحة اليوم؟
        $open = Attendance::where('admin_id', $adminId)
            ->whereDate('date', $today)
            ->whereNull('check_out')
            ->latest()
            ->first();

        if ($open) {
            // لو انتهت نافذة السماح نوسمها "منسية" ونسمح بإنشاء جديدة
            if ($now->gt($S->thresholdEnd)) {
                $open->update(['status' => 3]); // منسية
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'لديك جلسة مفتوحة بالفعل.',
                    'data'    => $open,
                ], 409);
            }
        }

        // أول زيارة لليوم قبل 11:06 ؟ زوّد number_of_days
        $hasAnyToday = Attendance::where('admin_id', $adminId)->whereDate('date', $today)->exists();
        if (!$hasAnyToday && $now->lt($cutoff1106)) {
            // لو عندك عمود number_of_days في admins
            \DB::table('admins')->where('id', $adminId)->increment('number_of_days');
        }

        // حساب التأخير بالدقائق (لو دخل بعد بداية الوردية)
        $timeLate = $now->gt($S->start) ? $now->diffInMinutes($S->start) : 0;

        $attendance = Attendance::create([
            'admin_id'       => $adminId,
            'shift_id'       => $shift->id ?? null,
            'date'           => $today,
            'check_in'       => $now,
            'check_out'      => null,
            'status'         => 1, // دخول
            'expected_hours' => round($S->expectedMins / 60, 2),
            'time_late'      => $timeLate,
            'worked_hours'   => 0,
            'note'           => $request->input('note'),
            // مطابق لأسماء أعمدتك الحالية
            'lang'           => $lon, // longitude
            'late'           => $lat, // latitude (مطابقة لتسمية جدولك)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الدخول بنجاح.',
            'data'    => $attendance,
        ], 201);
    }

    // ===== 5) خروج =====
    if ($status === 2) {
        $open = Attendance::where('admin_id', $adminId)
            ->whereDate('date', $today)
            ->whereNull('check_out')
            ->latest()
            ->first();

        if (!$open) {
            return response()->json([
                'success' => false,
                'message' => 'لا توجد جلسة مفتوحة للخروج.',
            ], 404);
        }

        // لا يسمح بالخروج بعد نافذة السماح → تُعتبر منسية
        if ($now->gt($S->thresholdEnd)) {
            $open->update(['status' => 3]); // منسية
            return response()->json([
                'success' => false,
                'message' => 'انتهت فترة الخروج المسموح بها. تم اعتبار الجلسة منسية.',
                'data'    => $open,
            ], 200);
        }

        $workedMinutes = Carbon::parse($open->check_in)->diffInMinutes($now);

        $open->update([
            'check_out'    => $now,
            'status'       => 2, // خروج
            'worked_hours' => round($workedMinutes / 60, 2),
            'note'         => $request->input('note', $open->note), // إن أردت تحديث الملاحظة
            // حدّث الإحداثيات وقت الخروج إن أحببت
            'lang'         => $lon ?? $open->lang,
            'late'         => $lat ?? $open->late,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الخروج بنجاح.',
            'data'    => $open,
        ], 200);
    }

    // ===== 6) حالة غير صالحة =====
    return response()->json([
        'success' => false,
        'message' => 'حالة غير صحيحة. استخدم 1 للدخول أو 2 للخروج.',
    ], 400);
}

    /**
     * Display all attendance records.
     *
     * This method retrieves all attendance records, ordered by date in descending order.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function indexAttendance(Request $request)
    {
        try {
                $adminId = Auth::guard('admin-api')->user()->id;

            // Retrieve all attendance records ordered by date descending
            $records = Attendance::where('admin_id',$adminId)->With('admins')->orderBy('date', 'desc')->get();

            return response()->json([
                'success' => true,
                'data'    => $records
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error retrieving attendance records: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving attendance records.'
            ], 500);
        }
    }
}
