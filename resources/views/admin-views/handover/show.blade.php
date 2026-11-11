@extends('layouts.admin.app')

@section('title', 'تسليم عهدة #' . $handover->id)

@push('css_or_js')
    @include('layouts.admin.partials._page_polish')
@endpush

@section('content')
@php
    $fromName = trim(optional($handover->fromSeller)->f_name . ' ' . optional($handover->fromSeller)->l_name)
                ?: optional($handover->fromSeller)->email ?: '—';
    $toName   = trim(optional($handover->toSeller)->f_name . ' ' . optional($handover->toSeller)->l_name)
                ?: optional($handover->toSeller)->email ?: '—';
@endphp

<div class="content container-fluid">

    <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
        <div>
            <h2 class="h4 mb-1">تسليم عهدة #{{ $handover->id }}</h2>
            <small class="text-muted">
                من <strong>{{ $fromName }}</strong> إلى <strong>{{ $toName }}</strong>
                @if($handover->started_at) — منذ {{ $handover->started_at->format('Y-m-d') }} @endif
            </small>
        </div>

        <div class="d-flex" style="gap:.5rem;">
            <a href="{{ route('admin.handover.index') }}" class="btn btn-secondary">رجوع</a>

            @if(!$handover->ended_at)
                <form method="POST" action="{{ route('admin.handover.end', $handover->id) }}"
                      onsubmit="return confirm('إنهاء التسليم؟ التاريخ يبقى محفوظًا.');">
                    @csrf
                    <button class="btn btn-outline-danger">إنهاء التسليم</button>
                </form>
            @endif
        </div>
    </div>

    {{-- ===== ترحيل الفواتير ===== --}}
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h5 class="mb-0">ترحيل الفواتير</h5>
            <small class="text-muted">
                تُنقل الملكية فيحصّلها المستلم من التطبيق. الأصل يُحفظ في السجلّ.
            </small>
        </div>

        <div class="card-body">
            @if($movedCount > 0)
                <div class="alert alert-info d-flex justify-content-between align-items-center flex-wrap">
                    <span>رُحِّلت <strong>{{ $movedCount }}</strong> فاتورة في هذا التسليم.</span>

                    <form method="POST" action="{{ route('admin.handover.transfer.undo', $handover->id) }}"
                          onsubmit="return confirm('إعادة الفواتير إلى صاحبها الأصلي؟');">
                        @csrf
                        <button class="btn btn-sm btn-outline-danger">تراجع عن الترحيل</button>
                    </form>
                </div>
            @endif

            @if($transferable->isEmpty())
                <p class="text-muted mb-0">
                    لا توجد فواتير قابلة للترحيل.
                    <small>(المسدَّدة بالكامل والمرتجعات لا تُرحَّل.)</small>
                </p>
            @else
                <form method="POST" action="{{ route('admin.handover.transfer', $handover->id) }}"
                      onsubmit="return confirm('ترحيل الفواتير المحددة إلى {{ $toName }}؟');">
                    @csrf

                    <div class="table-responsive mb-3">
                        <table class="table table-sm table-hover table-align-middle mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width:2.5rem;">
                                        <input type="checkbox" id="check-all" checked>
                                    </th>
                                    <th>الفاتورة</th>
                                    <th>العميل</th>
                                    <th class="text-right">المبلغ</th>
                                    <th class="text-right">المحصَّل</th>
                                    <th class="text-right">المتبقّي</th>
                                    <th>التاريخ</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($transferable as $order)
                                @php
                                    $paid = (float) $order->transaction_reference;
                                    $rest = max(0, (float) $order->order_amount - $paid);
                                @endphp
                                <tr>
                                    <td>
                                        <input type="checkbox" name="order_ids[]"
                                               value="{{ $order->id }}" class="order-check" checked>
                                    </td>
                                    <td>#{{ $order->id }}</td>
                                    <td>{{ optional($order->customer)->name ?: '—' }}</td>
                                    <td class="text-right">{{ number_format((float) $order->order_amount, 2) }}</td>
                                    <td class="text-right">{{ number_format($paid, 2) }}</td>
                                    <td class="text-right"><strong>{{ number_format($rest, 2) }}</strong></td>
                                    <td>{{ optional($order->created_at)->format('Y-m-d') }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="form-row align-items-end">
                        <div class="form-group col-md-8">
                            <label class="input-label">سبب الترحيل</label>
                            <input type="text" name="reason" class="form-control" maxlength="2000">
                        </div>
                        <div class="form-group col-md-4 text-left">
                            <button type="submit" class="btn btn-primary">
                                ترحيل المحدد إلى {{ $toName }}
                            </button>
                        </div>
                    </div>
                </form>
            @endif
        </div>
    </div>

    {{-- ===== نقل العملاء والمناطق والأقسام ===== --}}
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h5 class="mb-0">نقل العملاء والمناطق والأقسام</h5>
            <small class="text-muted">لا يُنقل إلا ما تختاره. ما عند المستلم أصلًا لا يتكرّر.</small>
        </div>

        <div class="card-body">
            @if(collect($assign)->every(fn ($list) => $list->isEmpty()))
                <p class="text-muted mb-0">لا عملاء ولا مناطق ولا أقسام معيّنة لصاحب العهدة.</p>
            @else
                <form method="POST" action="{{ route('admin.handover.assignments', $handover->id) }}"
                      onsubmit="return confirm('نقل المحدَّد إلى المستلم؟');">
                    @csrf

                    <div class="as-grid">
                        @foreach(['customers' => 'العملاء', 'regions' => 'المناطق', 'categories' => 'الأقسام'] as $key => $title)
                            <div class="as-col" data-group="{{ $key }}">
                                <div class="as-head">
                                    <strong>{{ $title }}</strong>
                                    <span class="badge badge-soft-secondary">{{ $assign[$key]->count() }}</span>
                                    @if($assign[$key]->isNotEmpty())
                                        <label class="as-all mb-0">
                                            <input type="checkbox" class="as-check-all"> الكل
                                        </label>
                                    @endif
                                </div>

                                @if($assign[$key]->isEmpty())
                                    <p class="text-muted small mb-0">لا يوجد.</p>
                                @else
                                    @if($assign[$key]->count() > 8)
                                        <input type="search" class="form-control form-control-sm mb-2 as-filter" placeholder="بحث…">
                                    @endif
                                    <div class="as-list">
                                        @foreach($assign[$key] as $item)
                                            <label class="as-item">
                                                <input type="checkbox" name="{{ $key }}[]" value="{{ $item['id'] }}" class="as-check">
                                                <span>{{ $item['name'] }}</span>
                                                @if($item['to_has'])
                                                    <small class="text-success">مع المستلم</small>
                                                @endif
                                            </label>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <div class="d-flex justify-content-between align-items-center flex-wrap mt-3" style="gap:.75rem;">
                        <label class="mb-0 d-inline-flex align-items-center" style="gap:.5rem; cursor:pointer;">
                            <input type="checkbox" name="remove" value="1" checked>
                            إزالة المنقول من {{ $fromName }} (نقل لا نسخ)
                        </label>
                        <button type="submit" class="btn btn-primary">نقل المحدَّد إلى {{ $toName }}</button>
                    </div>
                </form>
            @endif

            @if(!empty($handover->assignments))
                <hr>
                <h6 class="mb-2">ما نُقل سابقًا</h6>
                <ul class="small mb-0">
                    @foreach($handover->assignments as $entry)
                        <li>
                            {{ $entry['at'] ?? '' }} —
                            {{ count($entry['customers'] ?? []) }} عميل،
                            {{ count($entry['regions'] ?? []) }} منطقة،
                            {{ count($entry['categories'] ?? []) }} قسم
                            ({{ !empty($entry['remove']) ? 'نقل' : 'نسخ' }})
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    {{-- ===== جرد العربية ===== --}}
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">جرد العربية</h5>
            <small class="text-muted">
                الفرق يُسجَّل كميةً فقط، ولا يُقوَّم بمال.
            </small>
        </div>

        <div class="card-body">
            @if(!$handover->include_stock)
                <p class="text-muted mb-0">
                    هذا التسليم <strong>بلا بضاعة</strong>: بضاعة العربية تبقى في عهدة {{ $fromName }}، فلا جرد.
                </p>
            @elseif($vanCount)
                @php $pending = $vanCount->status === 'pending'; @endphp

                <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                    <div>
                        @if($pending)
                            <span class="badge badge-soft-warning">بانتظار الاعتماد</span>
                        @elseif($vanCount->status === 'approved')
                            <span class="badge badge-soft-success">معتمَد</span>
                        @else
                            <span class="badge badge-soft-danger">مرفوض</span>
                        @endif

                        <span class="mr-3">
                            العجز: <strong class="text-danger">{{ $vanCount->shortage() }}</strong>
                            &nbsp;·&nbsp;
                            الزيادة: <strong class="text-success">{{ $vanCount->surplus() }}</strong>
                        </span>
                    </div>

                    @if($pending)
                        <div class="d-flex" style="gap:.5rem;">
                            <form method="POST"
                                  action="{{ route('admin.handover.count.approve', [$handover->id, $vanCount->id]) }}"
                                  onsubmit="return confirm('اعتماد الجرد؟ المسلَّم يعود للمخزن والعربية تُفرَّغ.');">
                                @csrf
                                <button class="btn btn-sm btn-success">اعتماد</button>
                            </form>

                            <form method="POST"
                                  action="{{ route('admin.handover.count.reject', [$handover->id, $vanCount->id]) }}">
                                @csrf
                                <button class="btn btn-sm btn-outline-danger">رفض</button>
                            </form>
                        </div>
                    @endif
                </div>

                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-align-middle mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>الصنف</th>
                                <th class="text-center">في النظام</th>
                                <th class="text-center">المسلَّم</th>
                                <th class="text-center">الفرق</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($vanCount->items as $item)
                            <tr>
                                <td>{{ optional($item->product)->name ?: '#' . $item->product_id }}</td>
                                <td class="text-center">{{ $item->expected }}</td>
                                <td class="text-center">{{ $item->counted }}</td>
                                <td class="text-center">
                                    @if($item->difference < 0)
                                        <span class="text-danger"><strong>{{ $item->difference }}</strong></span>
                                    @elseif($item->difference > 0)
                                        <span class="text-success"><strong>+{{ $item->difference }}</strong></span>
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

            @elseif($vanStock->isEmpty())
                <p class="text-muted mb-0">عربية هذا المندوب فارغة.</p>

            @else
                <form method="POST" action="{{ route('admin.handover.count.store', $handover->id) }}"
                      onsubmit="return confirm('تسجيل الجرد؟ لا يتحرك المخزون قبل الاعتماد.');">
                    @csrf

                    <p class="text-muted">
                        اكتب الكمية المسلَّمة فعلًا لكل صنف. الصنف الذي يُترك فارغًا يُعدّ غير مسلَّم.
                    </p>

                    <div class="table-responsive mb-3">
                        <table class="table table-sm table-bordered table-align-middle mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>الصنف</th>
                                    <th class="text-center" style="width:8rem;">في النظام</th>
                                    <th class="text-center" style="width:10rem;">المسلَّم فعلًا</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($vanStock as $row)
                                <tr>
                                    <td>{{ optional($row->product)->name ?: '#' . $row->product_id }}</td>
                                    <td class="text-center">{{ (int) $row->stock }}</td>
                                    <td>
                                        <input type="number" min="0" step="1"
                                               name="counted[{{ $row->product_id }}]"
                                               value="{{ (int) $row->stock }}"
                                               class="form-control form-control-sm text-center">
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="form-row align-items-end">
                        <div class="form-group col-md-8">
                            <label class="input-label">ملاحظة</label>
                            <input type="text" name="note" class="form-control" maxlength="2000">
                        </div>
                        <div class="form-group col-md-4 text-left">
                            <button type="submit" class="btn btn-primary">تسجيل الجرد</button>
                        </div>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection

@push('script_2')
<script>
    (function () {
        var all = document.getElementById('check-all');

        if (!all) {
            return;
        }

        all.addEventListener('change', function () {
            document.querySelectorAll('.order-check').forEach(function (box) {
                box.checked = all.checked;
            });
        });
    })();

    // نقل التعيينات: «الكل» لكل عمود يحدّد الظاهر منه فقط، والبحث يصفّي القائمة.
    document.querySelectorAll('.as-col').forEach(function (col) {
        var all = col.querySelector('.as-check-all');
        var filter = col.querySelector('.as-filter');

        if (all) {
            all.addEventListener('change', function () {
                col.querySelectorAll('.as-item').forEach(function (item) {
                    if (!item.hidden) item.querySelector('.as-check').checked = all.checked;
                });
            });
        }

        if (filter) {
            filter.addEventListener('input', function () {
                var q = filter.value.trim().toLowerCase();
                col.querySelectorAll('.as-item').forEach(function (item) {
                    item.hidden = q !== '' && item.textContent.toLowerCase().indexOf(q) === -1;
                });
            });
        }
    });
</script>
<style>
    .as-grid { display: grid; gap: 1rem; grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .as-col { border: 1px solid #e3ebf4; border-radius: 12px; padding: .85rem; min-width: 0; }
    .as-head { display: flex; align-items: center; gap: .5rem; margin-bottom: .6rem; }
    .as-all { margin-inline-start: auto; font-size: .85rem; cursor: pointer; display: inline-flex; gap: .35rem; align-items: center; }
    .as-list { max-height: 280px; overflow-y: auto; }
    .as-item { display: flex; align-items: center; gap: .5rem; padding: .3rem .25rem; margin: 0; cursor: pointer; border-radius: 6px; }
    .as-item:hover { background: #f4f8fc; }
    .as-item small { margin-inline-start: auto; white-space: nowrap; }
    @media (max-width: 991.98px) { .as-grid { grid-template-columns: 1fr; } }
</style>
@endpush
