@extends('layouts.admin.app')

@section('title', 'تسليم عهدة مندوب')

@push('css_or_js')
    @include('layouts.admin.partials._page_polish')
@endpush

@section('content')
<div class="content container-fluid">

    <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
        <div>
            <h2 class="h4 mb-1">تسليم عهدة مندوب</h2>
            <small class="text-muted">
                نقل فواتير مندوبٍ انتهت خدمته إلى من يخلفه، وجرد عربيته.
            </small>
        </div>

        <a href="{{ route('admin.handover.create') }}" class="btn btn-primary">
            <i class="tio-add"></i> تسليم جديد
        </a>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover table-align-middle mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>صاحب العهدة</th>
                        <th>المستلم</th>
                        <th class="text-center">الفواتير المرحَّلة</th>
                        <th>بدأ في</th>
                        <th class="text-center">الحالة</th>
                        <th class="text-center">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($handovers as $handover)
                    <tr>
                        <td>{{ $handover->id }}</td>

                        <td>
                            {{ trim(optional($handover->fromSeller)->f_name . ' ' . optional($handover->fromSeller)->l_name)
                               ?: optional($handover->fromSeller)->email ?: '—' }}
                        </td>

                        <td>
                            {{ trim(optional($handover->toSeller)->f_name . ' ' . optional($handover->toSeller)->l_name)
                               ?: optional($handover->toSeller)->email ?: '—' }}
                        </td>

                        <td class="text-center">{{ $moved[$handover->id] ?? 0 }}</td>

                        <td>{{ optional($handover->started_at)->format('Y-m-d') ?: '—' }}</td>

                        <td class="text-center">
                            @if($handover->ended_at)
                                <span class="badge badge-soft-secondary">منتهٍ</span>
                            @else
                                <span class="badge badge-soft-success">قائم</span>
                            @endif
                        </td>

                        <td class="text-center">
                            <a href="{{ route('admin.handover.show', $handover->id) }}"
                               class="btn btn-sm btn-outline-primary">عرض</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            لا توجد عمليات تسليم.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if($handovers->hasPages())
            <div class="card-footer">
                {{ $handovers->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
