@extends('layouts.admin.app')

@section('title', \App\CPU\translate('سجلات الحضور'))

@push('css_or_js')
    <style>
        .summary-box {
            background: #f1f1f1;
            padding: 15px;
            border: 1px solid #ced4da;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .table-responsive {
            margin-top: 20px;
        }
        .page-header-title {
            font-size: 2.5rem;
            font-weight: 600;
            color: #001B63;
        }
    </style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row align-items-center mb-4">
        <div class="col">
            <h1 class="page-header-title">{{ \App\CPU\translate('سجلات الحضور') }}</h1>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('admin.attendance.index') }}" method="GET" class="form-inline">
                <div class="form-group mr-2">
                    <label class="mr-2">{{ \App\CPU\translate('الموظف') }}</label>
                    <select name="employee_id" class="form-control">
                        <option value="">{{ \App\CPU\translate('اختر الموظف') }}</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" {{ request('employee_id') == $employee->id ? 'selected' : '' }}>
                                {{ $employee->email ?? $employee->email }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group mr-2">
                    <label class="mr-2">{{ \App\CPU\translate('من تاريخ') }}</label>
                    <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                </div>
                <div class="form-group mr-2">
                    <label class="mr-2">{{ \App\CPU\translate('إلى تاريخ') }}</label>
                    <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                </div>
                <button type="submit" class="btn btn-primary">{{ \App\CPU\translate('فلترة') }}</button>
            </form>
        </div>
    </div>

    @php
        // Calculate summary values
        $totalWorkedHours = $attendances->sum('worked_hours');
        $workingDays = $attendances->pluck('date')->unique()->count();
        $totalExpectedHours = $attendances->sum('expected_hours');
                $totaltime_late = $attendances->sum('time_late');
    @endphp

    <!-- Summary Box -->
    <div class="summary-box">
        <p>
            <strong>{{ \App\CPU\translate('إجمالي ساعات العمل الفعلية') }}:</strong> {{ $totalWorkedHours }} ساعة
        </p>
        <p>
            <strong>{{ \App\CPU\translate('عدد أيام العمل') }}:</strong> {{ $workingDays }} يوم
        </p>
        <p>
            <strong>{{ \App\CPU\translate('إجمالي ساعات العمل المتوقعة') }}:</strong> {{ $totalExpectedHours }} ساعة
        </p>
          <p>
            <strong>{{ \App\CPU\translate('إجمالي مدة التأخير ') }}:</strong> {{ $totaltime_late }} دقائق
        </p>
    </div>

    <!-- Attendance Table -->
    <div class="table-responsive">
        <table class="table table-bordered">
            <thead class="thead-light">
                <tr>
                    <th>#</th>
                    <th>{{ \App\CPU\translate('الموظف') }}</th>
                    <th>{{ \App\CPU\translate('التاريخ') }}</th>
                    <th>{{ \App\CPU\translate('تسجيل الدخول') }}</th>
                    <th>{{ \App\CPU\translate('تسجيل الخروج') }}</th>
                    <!--<th>{{ \App\CPU\translate('الحالة') }}</th>-->
                    <th>{{ \App\CPU\translate('ساعات العمل الفعلية') }}</th>
                    <th>{{ \App\CPU\translate('ساعات العمل المتوقعة') }}</th>
<th>مدة التأخير (بالدقائق)</th>
                  <th>{{ \App\CPU\translate('الموقع') }}</th> 
                  <th>{{ \App\CPU\translate('اسم المكان') }}</th> 

                </tr>
            </thead>
            <tbody>
                @forelse($attendances as $attendance)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $attendance->admins->email ?? '-' }}</td>
                        <td>{{ \Carbon\Carbon::parse($attendance->date)->format('Y-m-d') }}</td>
                        <td>{{ $attendance->check_in ? \Carbon\Carbon::parse($attendance->check_in)->format('H:i:s') : '-' }}</td>
                        <td>{{ $attendance->check_out ? \Carbon\Carbon::parse($attendance->check_out)->format('H:i:s') : '-' }}</td>
                        <!--<td>{{ ucfirst($attendance->status) }}</td>-->
                        <td>{{ $attendance->worked_hours ?? '-' }}</td>
                        <td>{{ $attendance->expected_hours ?? '-' }}</td>
                                                <td>{{ $attendance->time_late ?? '-' }}</td>
                        <td>
                    @if( $attendance->late && $attendance->lang)
                        <a href="https://www.google.com/maps?q={{ $attendance->lang }},{{ $attendance->late }}"
                           class="btn btn-sm btn-outline-info"
                           target="_blank">
                            {{ \App\CPU\translate('عرض الموقع') }}
                        </a>
                    @else
                        -
                    @endif
                </td>
                                        <td>{{ $attendance->note ?? '-' }}</td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center">{{ \App\CPU\translate('لا توجد سجلات') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {!! $attendances->appends(request()->query())->links() !!}
</div>
@endsection

@push('script_2')
    <script src="{{ asset('public/assets/admin/js/global.js') }}"></script>
@endpush
