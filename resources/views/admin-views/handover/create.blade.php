@extends('layouts.admin.app')

@section('title', 'تسليم جديد')

@push('css_or_js')
    @include('layouts.admin.partials._page_polish')
@endpush

@section('content')
<div class="content container-fluid">

    <div class="mb-4">
        <h2 class="h4 mb-1">تسليم عهدة مندوب</h2>
        <small class="text-muted">
            التسجيل وحده لا ينقل شيئًا: الترحيل والجرد خطوتان تاليتان.
        </small>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.handover.store') }}">
                @csrf

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label class="input-label">صاحب العهدة (من انتهت خدمته)</label>
                        <select name="from_seller_id" id="from_seller_id" class="form-control" required>
                            <option value="">— اختر —</option>
                            @foreach($sellers as $seller)
                                <option value="{{ $seller->id }}" @selected(old('from_seller_id') == $seller->id)>
                                    {{ trim($seller->f_name . ' ' . $seller->l_name) ?: $seller->email }}
                                    @if($seller->mandob_code) ({{ $seller->mandob_code }}) @endif
                                </option>
                            @endforeach
                        </select>
                        @error('from_seller_id')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>

                    <div class="form-group col-md-6">
                        <label class="input-label">المستلم</label>
                        <select name="to_seller_id" id="to_seller_id" class="form-control" required>
                            <option value="">— اختر —</option>
                            @foreach($sellers as $seller)
                                <option value="{{ $seller->id }}" @selected(old('to_seller_id') == $seller->id)>
                                    {{ trim($seller->f_name . ' ' . $seller->l_name) ?: $seller->email }}
                                    @if($seller->mandob_code) ({{ $seller->mandob_code }}) @endif
                                </option>
                            @endforeach
                        </select>
                        @error('to_seller_id')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label class="input-label">تاريخ البدء</label>
                        <input type="date" name="started_at" class="form-control"
                               value="{{ old('started_at', now()->format('Y-m-d')) }}">
                    </div>

                    <div class="form-group col-md-8">
                        <label class="input-label">ملاحظة</label>
                        <input type="text" name="note" class="form-control"
                               maxlength="2000" value="{{ old('note') }}">
                    </div>
                </div>

                {{-- قد لا تُسلَّم البضاعة للمستلم: تبقى، أو تُصفَّر وتعود للمخزن. --}}
                <div class="form-group">
                    <label class="d-inline-flex align-items-center mb-0" style="gap:.5rem; cursor:pointer; font-weight:700;">
                        <input type="checkbox" name="include_stock" id="include_stock" value="1"
                               @checked(old('include_stock', '1') === '1')>
                        تسليم بضاعة العربية للمستلم
                    </label>
                    <small class="d-block text-muted">
                        بدونها تقدر تصفّر العربية وترجّع بضاعتها للمخزن من صفحة التسليم.
                        والعملاء والمناطق والأقسام تختار ما يُنقل منها هناك أيضًا.
                    </small>
                </div>

                <div class="d-flex justify-content-end" style="gap:.5rem;">
                    <a href="{{ route('admin.handover.index') }}" class="btn btn-secondary">إلغاء</a>
                    <button type="submit" class="btn btn-primary">تسجيل التسليم</button>
                </div>
            </form>
        </div>
    </div>

    {{-- مراجعة قبل التسجيل: تُملأ من admin.handover.preview عند اختيار المندوبين. --}}
    <div class="card mt-4" id="handover-preview-card" hidden>
        <div class="card-body">
            <h5 class="mb-3">مراجعة العهدة</h5>
            <div id="handover-preview"></div>
        </div>
    </div>
</div>
@endsection

@push('script_2')
<style>
    .hp-grid { display: grid; gap: 1rem; grid-template-columns: repeat(3, minmax(0, 1fr)); margin-bottom: 1.25rem; }
    .hp-stat { border: 1px solid #e3ebf4; border-radius: 14px; padding: 1rem; background: #fff; }
    .hp-stat--from  { background: #fff8ec; border-color: #f6dfb4; }
    .hp-stat--after { background: #ecfbf4; border-color: #bfe9d4; }
    .hp-stat--hint  { display: flex; align-items: center; color: #7a8aa5; grid-column: span 2; }
    .hp-stat-label { display: block; font-weight: 800; color: #11245a; margin-bottom: .6rem; font-size: .85rem; }
    .hp-stat-row { display: flex; gap: 1rem; flex-wrap: wrap; }
    .hp-stat-row > div { min-width: 0; }
    .hp-stat-row b { display: block; font-size: 1.15rem; color: #11245a; }
    .hp-stat-row small { color: #6b7a93; }
    .hp-section { margin-top: 1.25rem; }
    .hp-section h6 { font-weight: 800; color: #11245a; margin-bottom: .6rem; }
    .hp-table thead th { background: #f4f8fc; color: #5b6b86; font-weight: 800; white-space: nowrap; }
    .hp-scroll { max-height: 360px; overflow-y: auto; }
    @media (max-width: 991.98px) { .hp-grid { grid-template-columns: 1fr; } .hp-stat--hint { grid-column: auto; } }
</style>
<script>
    (function () {
        var from = document.getElementById('from_seller_id');
        var to   = document.getElementById('to_seller_id');
        var stock = document.getElementById('include_stock');
        var card = document.getElementById('handover-preview-card');
        var box  = document.getElementById('handover-preview');
        var url  = @json(route('admin.handover.preview'));
        var seq  = 0;

        function load() {
            if (!from.value) { card.hidden = true; return; }

            var mine = ++seq;
            card.hidden = false;
            box.innerHTML = '<p class="text-muted mb-0">جارٍ التحميل…</p>';

            var q = '?from=' + encodeURIComponent(from.value)
                  + (to.value ? '&to=' + encodeURIComponent(to.value) : '')
                  + '&stock=' + (stock.checked ? '1' : '0');

            fetch(url + q, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
                .then(function (r) { if (!r.ok) throw r; return r.text(); })
                .then(function (html) { if (mine === seq) box.innerHTML = html; })
                .catch(function () {
                    if (mine === seq) box.innerHTML = '<p class="text-danger mb-0">تعذّر تحميل المعاينة.</p>';
                });
        }

        from.addEventListener('change', load);
        to.addEventListener('change', load);
        stock.addEventListener('change', load);
        load();
    })();
</script>
@endpush
