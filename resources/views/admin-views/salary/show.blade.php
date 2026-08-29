@extends('layouts.admin.app')

@section('title', 'تفاصيل المرتب')

@section('content')
<div class="content container-fluid" dir="rtl">

    <div class="page-header">
        <h1 class="page-header-title">
            {{ \App\CPU\translate('تفاصيل المرتب') }}
            @if($salary->seller)
                — {{ $salary->seller->f_name ?? '' }} {{ $salary->seller->l_name ?? '' }}
            @endif
        </h1>
        <p class="text-muted mb-0">{{ \App\CPU\translate('شهر') }}: {{ $salary->month }}</p>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-bordered table-align-middle">
                <tbody>
                    @php
                        // نفس بنود شاشة الإدخال وبنفس ترتيبها، حتى تُقرأ الشاشتان
                        // على نحو واحد.
                        $rows = [
                            'المرتب الأساسي'   => $salary->salary,
                            'إجمالي التحصيلات' => $salary->commission,
                            'حافز البيع'       => $salary->transport_amount,
                            'حافز التحصيل'     => $salary->collection_incentive,
                            'مكافأة الالتزام'  => $salary->salary_of_visitors,
                            'بدلات أخرى'       => $salary->other,
                            'خصم'              => $salary->discount,
                            'عدد أيام العمل'   => $salary->number_of_days,
                            'عدد الزيارات'     => $salary->number_of_visitors,
                            'الزيارات المنفذة' => $salary->result_of_visitors,
                            'التقييم'          => $salary->score,
                        ];
                    @endphp

                    @foreach($rows as $label => $value)
                        <tr>
                            <th style="width:40%;">{{ \App\CPU\translate($label) }}</th>
                            <td>{{ $value }}</td>
                        </tr>
                    @endforeach

                    <tr class="table-active">
                        <th>{{ \App\CPU\translate('الإجمالي') }}</th>
                        <td><strong>{{ $salary->total }}</strong></td>
                    </tr>

                    <tr>
                        <th>{{ \App\CPU\translate('ملاحظات') }}</th>
                        <td>{{ $salary->note }}</td>
                    </tr>
                    <tr>
                        <th>{{ \App\CPU\translate('ملاحظات المدير') }}</th>
                        <td>{{ $salary->notemanager }}</td>
                    </tr>
                </tbody>
            </table>

            <a href="{{ route('admin.salaries.index') }}" class="btn btn-light border px-4">
                {{ \App\CPU\translate('رجوع') }}
            </a>
        </div>
    </div>
</div>
@endsection
