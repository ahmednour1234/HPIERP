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

        // Retrieve all attendance records ordered by date descending
        $attendances = $query->orderBy('date', 'desc')->paginate(10);

        // Calculate total worked hours from all records (assuming worked_hours is stored in hours)
        $totalWorkedHours = $attendances->sum('worked_hours');

        // Calculate the number of distinct working days (using unique dates)
        $workingDays = $attendances->pluck('date')->unique()->count();

        // Retrieve all employees (admins) to populate the filter dropdown in the view
        $employees = Admin::all();

        // Return the view with the attendance data and summary information
        return view('admin-views.attendances.index', compact('attendances', 'totalWorkedHours', 'workingDays', 'employees'));
    }
}
