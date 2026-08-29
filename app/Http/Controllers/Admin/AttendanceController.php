<?php


namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Admin;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Brian2694\Toastr\Facades\Toastr;
use function App\CPU\translate;

class AttendanceController extends Controller
{
    public function showAttendances(Request $request)
    {
           $adminId = Auth::guard('admin')->id();
    $admin = DB::table('admins')->where('id', $adminId)->first();

 
        // Start building the query
        $query = Attendance::query();

        // Filter by employee ID if provided
        if ($request->filled('employee_id')) {
            $query->where('admin_id', $request->employee_id);
        }

        // Filter by date range if both start and end dates are provided
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        // الإجماليات تُحسب على كامل نتيجة الفلتر لا على الصفحة المعروضة.
        //
        // كانت تُحسب من $attendances بعد الترقيم، فتصف عشرة صفوف فقط: ظهر
        // «إجمالي ساعات العمل 27.26» و«عدد أيام العمل 2» بينما الحقيقة
        // 5466 ساعة و298 يومًا. تُحسب هنا بـ SQL قبل الترقيم.
        $totalsQuery = (clone $query);

        $totalWorkedHours   = (float) (clone $totalsQuery)->sum('worked_hours');
        $totalExpectedHours = (float) (clone $totalsQuery)->sum('expected_hours');
        $totalTimeLate      = (int) (clone $totalsQuery)->sum('time_late');
        $workingDays        = (int) (clone $totalsQuery)->distinct()->count('date');

        // سجلات بلا تسجيل خروج: تُحتسب ساعاتها المتوقعة بينما ساعات العمل
        // فيها صفر، فتبدو الفجوة بين المتوقع والفعلي أكبر من حقيقتها.
        $openShifts = (int) (clone $totalsQuery)
            ->where(function ($q) {
                $q->whereNull('check_out')->orWhere('check_out', '');
            })
            ->count();

        // Retrieve all attendance records ordered by date descending
        $attendances = $query->orderBy('date', 'desc')
            ->paginate(10)
            ->appends($request->query());

        // قائمة الموظفين للفلتر.
        $employees = Admin::orderBy('f_name')->get();

        return view('admin-views.attendances.index', compact(
            'attendances',
            'totalWorkedHours',
            'totalExpectedHours',
            'totalTimeLate',
            'workingDays',
            'openShifts',
            'employees'
        ));
    }
}
