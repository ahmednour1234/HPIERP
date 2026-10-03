@extends('layouts.admin.app')

@section('title', 'عهدة المناديب')

@push('css_or_js')
    @include('layouts.admin.partials._page_polish')
@endpush

@section('content')
<div class="content container-fluid">

    <div class="mb-4">
        <h2 class="h4 mb-1">عهدة المناديب</h2>
        <small class="text-muted">
            ما حصّله كل مندوب مقابل ما ورّده. شاشة قراءة فقط: لا تُعدِّل أي رصيد.
        </small>
    </div>

    <div class="alert alert-light border">
        <strong>كيف يُحسب المتوقع؟</strong>
        <span class="text-muted">
            الأقساط + المبيعات النقدية المحصَّلة − الإيداعات المعتمدة.
            «المسجَّل» هو العمود <code>credit</code> كما هو في قاعدة البيانات،
            وهو رقم تراكمي قديم. الفرق بينهما يحتاج مراجعة المحاسب، لا تصحيحًا آليًّا.
        </span>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover table-align-middle mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>المندوب</th>
                        <th class="text-right">أقساط</th>
                        <th class="text-right">نقدي</th>
                        <th class="text-right">إجمالي المحصَّل</th>
                        <th class="text-right">ورَّد</th>
                        <th class="text-right">قيد الاعتماد</th>
                        <th class="text-right">العهدة المتوقعة</th>
                        <th class="text-right">المسجَّل</th>
                        <th class="text-right">الفرق</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td>
                            {{ $row['name'] }}
                            @if($row['code'])
                                <small class="text-muted d-block">{{ $row['code'] }}</small>
                            @endif
                        </td>

                        <td class="text-right">{{ number_format($row['instalments'], 2) }}</td>
                        <td class="text-right">{{ number_format($row['cash'], 2) }}</td>
                        <td class="text-right"><strong>{{ number_format($row['collected'], 2) }}</strong></td>
                        <td class="text-right">{{ number_format($row['deposited'], 2) }}</td>

                        <td class="text-right">
                            @if($row['pending'] > 0)
                                <span class="text-warning">{{ number_format($row['pending'], 2) }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>

                        <td class="text-right">
                            <strong @class(['text-danger' => $row['expected'] < 0])>
                                {{ number_format($row['expected'], 2) }}
                            </strong>
                        </td>

                        <td class="text-right">
                            <span @class(['text-danger' => $row['recorded'] < 0])>
                                {{ number_format($row['recorded'], 2) }}
                            </span>
                        </td>

                        <td class="text-right">
                            @if(abs($row['gap']) < 0.01)
                                <span class="badge badge-soft-success">مطابق</span>
                            @else
                                <strong @class(['text-danger' => $row['gap'] < 0, 'text-warning' => $row['gap'] > 0])>
                                    {{ $row['gap'] > 0 ? '+' : '' }}{{ number_format($row['gap'], 2) }}
                                </strong>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-5">لا يوجد مناديب.</td>
                    </tr>
                @endforelse
                </tbody>

                @if($rows->isNotEmpty())
                    <tfoot class="thead-light">
                        <tr>
                            <th>الإجمالي</th>
                            <th class="text-right">{{ number_format($rows->sum('instalments'), 2) }}</th>
                            <th class="text-right">{{ number_format($rows->sum('cash'), 2) }}</th>
                            <th class="text-right">{{ number_format($rows->sum('collected'), 2) }}</th>
                            <th class="text-right">{{ number_format($rows->sum('deposited'), 2) }}</th>
                            <th class="text-right">{{ number_format($rows->sum('pending'), 2) }}</th>
                            <th class="text-right">{{ number_format($rows->sum('expected'), 2) }}</th>
                            <th class="text-right">{{ number_format($rows->sum('recorded'), 2) }}</th>
                            <th></th>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
@endsection
