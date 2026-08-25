@extends('layouts.admin.app')

@section('title', \App\CPU\translate('قائمة الرواتب'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/select2.min.css') }}">
    <style>
        .table td, .table th {
            vertical-align: middle;
            text-align: center;
        }
        .score-cell {
            position: relative;
            text-align: center;
        }
        .score-chart {
            display: block;
            margin: 0 auto;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <h3 class="mt-4 mb-4">{{ \App\CPU\translate('قائمة الرواتب') }}</h3>

        <!-- Add Salary Button -->
        <div class="d-flex justify-content-end mb-4">
            <a href="{{ route('admin.salaries.create') }}" class="btn btn-primary">
                {{ \App\CPU\translate('إضافة راتب') }}
            </a>
        </div>

        <!-- Search Form -->
        <form method="GET" action="{{ route('admin.salaries.index') }}" class="mb-4">
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="seller_id">{{ \App\CPU\translate('اختر البائع') }}</label>
                    <select id="seller_id" name="seller_id" class="form-control select2">
                        <option value="">{{ \App\CPU\translate('اختر البائع') }}</option>
                        @foreach($sellers as $seller)
                            <option value="{{ $seller->id }}" {{ request('seller_id') == $seller->id ? 'selected' : '' }}>
                                {{ $seller->email }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="month">{{ \App\CPU\translate('الشهر') }}</label>
                    <input type="month" id="month" name="month" class="form-control" value="{{ request('month') }}">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-secondary w-100">{{ \App\CPU\translate('بحث') }}</button>
                </div>
            </div>
        </form>

        <!-- Salaries Table -->
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th>{{ \App\CPU\translate('معرف البائع') }}</th>
                        <th>{{ \App\CPU\translate('اسم البائع') }}</th>
                        <th>{{ \App\CPU\translate('الراتب') }}</th>
                        <th>{{ \App\CPU\translate('اجمال التحصيلات') }}</th>
                            <th>{{ \App\CPU\translate('ملاحظة') }}</th>
                        <th>{{ \App\CPU\translate('ملاحظة المدير') }}</th>
                        <th>{{ \App\CPU\translate('عدد ايام العمل') }}</th>
                        <th>{{ \App\CPU\translate('عدد الزوار') }}</th>
                        <th>{{ \App\CPU\translate('نتيجة الزوار') }}</th>
                        <th>{{ \App\CPU\translate('مبلغ النقل') }}</th>
                        <th>{{ \App\CPU\translate('بدل التزام') }}</th>
                        <th>{{ \App\CPU\translate('بدلات أخري') }}</th>
                        <th>{{ \App\CPU\translate('الخصم') }}</th>
                        <th>{{ \App\CPU\translate('النقاط') }}</th>
                        <th>{{ \App\CPU\translate('المجموع') }}</th>
                        <th>{{ \App\CPU\translate('الشهر') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($salaries as $salary)
                        <tr>
                            <td>{{ $salary->seller->id??'' }}</td>
                            <td>{{ $salary->seller->email??'' }}</td>
                            <td>{{ $salary->salary }}</td>
                            <td>{{ $salary->commission }}</td>
                              <td>{{ $salary->note }}</td>
                            <td>{{ $salary->notemanager }}</td>
                            <td>{{ $salary->number_of_days }}</td>
                            <td>{{ $salary->number_of_visitors }}</td>
                            <td>{{ $salary->result_of_visitors }}</td>
                            <td>{{ $salary->salary_of_visitors }}</td>
                            <td>{{ $salary->transport_amount }}</td>
                                                        <td>{{ $salary->other }}</td>
                            <td>{{ $salary->discount }}</td>
                            <td class="score-cell">
                                {{ $salary->score }}%
                                <canvas id="scoreChart{{ $salary->id }}" class="score-chart" width="40" height="40"></canvas>
                            </td>
                            <td>{{ $salary->total }}</td>
                            <td>{{ \Carbon\Carbon::parse($salary->month)->format('Y-m') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="text-center">{{ \App\CPU\translate('لا توجد رواتب') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Links -->
        <div class="d-flex justify-content-center">
            {{ $salaries->links() }}
        </div>
    </div>
@endsection

@push('script_2')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="{{ asset('public/assets/admin/js/select2.min.js') }}"></script>
<script>
    $(document).ready(function() {
        $('.select2').select2();
    });

    document.addEventListener('DOMContentLoaded', function () {
        @foreach($salaries as $salary)
            const ctx{{ $salary->id }} = document.getElementById('scoreChart{{ $salary->id }}').getContext('2d');
            
            new Chart(ctx{{ $salary->id }}, {
                type: 'doughnut',
                data: {
                    datasets: [{
                        data: [{{ $salary->score }}, 100 - {{ $salary->score }}],
                        backgroundColor: ['rgba(54, 162, 235, 0.7)', 'rgba(220, 220, 220, 0.2)'],
                        borderColor: ['rgba(54, 162, 235, 1)', 'rgba(220, 220, 220, 0.1)'],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    cutout: '80%', // Thickness of the circle
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(tooltipItem) {
                                    return 'النقاط: ' + tooltipItem.raw + '%';
                                }
                            }
                        }
                    }
                }
            });
        @endforeach
    });
</script>
@endpush
