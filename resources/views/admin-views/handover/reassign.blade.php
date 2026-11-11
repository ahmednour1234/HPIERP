@extends('layouts.admin.app')

@section('title', 'نقل العملاء والمناطق')

@push('css_or_js')
    @include('layouts.admin.partials._page_polish')
@endpush

@section('content')
@php
    $nameOf = function ($id) use ($sellers) {
        $s = $sellers->firstWhere('id', $id);
        return $s ? (trim($s->f_name . ' ' . $s->l_name) ?: $s->email) : '—';
    };
@endphp

<div class="content container-fluid">

    <div class="d-flex justify-content-between align-items-center flex-wrap mb-4" style="gap:.75rem;">
        <div>
            <h2 class="h4 mb-1">نقل العملاء والمناطق والأقسام</h2>
            <small class="text-muted">من مندوب لآخر مباشرةً، بلا تسليم عهدة. لا يُنقل إلا ما تختاره.</small>
        </div>
        <a href="{{ route('admin.handover.index') }}" class="btn btn-secondary">رجوع</a>
    </div>

    {{-- اختيار المندوبين: GET فيُعاد تحميل القوائم بتعيينات الأول. --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.handover.reassign') }}" id="pick-form">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label class="input-label">من</label>
                        <select name="from" class="form-control" onchange="this.form.submit()">
                            <option value="">— اختر —</option>
                            @foreach($sellers as $seller)
                                <option value="{{ $seller->id }}" @selected($from === (int) $seller->id)>
                                    {{ trim($seller->f_name . ' ' . $seller->l_name) ?: $seller->email }}
                                    @if($seller->mandob_code) ({{ $seller->mandob_code }}) @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label class="input-label">إلى</label>
                        <select name="to" class="form-control" onchange="this.form.submit()">
                            <option value="">— اختر —</option>
                            @foreach($sellers as $seller)
                                <option value="{{ $seller->id }}" @selected($to === (int) $seller->id)>
                                    {{ trim($seller->f_name . ' ' . $seller->l_name) ?: $seller->email }}
                                    @if($seller->mandob_code) ({{ $seller->mandob_code }}) @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if($from && $assign)
        <div class="card">
            <div class="card-body">
                @if(collect($assign)->every(fn ($list) => $list->isEmpty()))
                    <p class="text-muted mb-0">لا عملاء ولا مناطق ولا أقسام معيّنة لـ {{ $nameOf($from) }}.</p>
                @elseif(!$to)
                    <p class="text-muted mb-3">اختر المندوب المنقول إليه لتفعيل النقل.</p>
                    @include('admin-views.handover._assign_picker', ['assign' => $assign, 'fromName' => $nameOf($from), 'toName' => '…'])
                    <script>document.querySelectorAll('.as-grid ~ div button[type=submit]').forEach(function (b) { b.disabled = true; });</script>
                @else
                    <form method="POST" action="{{ route('admin.handover.reassign.store') }}"
                          onsubmit="return confirm('نقل المحدَّد؟');">
                        @csrf
                        <input type="hidden" name="from" value="{{ $from }}">
                        <input type="hidden" name="to" value="{{ $to }}">
                        @include('admin-views.handover._assign_picker', ['assign' => $assign, 'fromName' => $nameOf($from), 'toName' => $nameOf($to)])
                    </form>
                @endif
            </div>
        </div>
    @endif
</div>
@endsection
