{{-- معاينة التسليم: تُحمَّل في صفحة الإنشاء عند اختيار المندوبين. --}}
@php
    $fromUnits = $products->sum('from');
    $toUnits   = $products->sum('to');
    // البضاعة لا تُضاف للمستلم إن لم تُسلَّم.
    $movedUnits = $includeStock ? $fromUnits : 0;
@endphp

@unless($includeStock)
    <div class="alert alert-soft-warning mb-3">
        البضاعة <strong>لن تُسلَّم</strong> للمستلم: تبقى في عهدة صاحبها، ولا جرد للعربية في هذا التسليم.
    </div>
@endunless

<div class="hp-grid">
    <div class="hp-stat hp-stat--from">
        <span class="hp-stat-label">مع صاحب العهدة{{ $includeStock ? ' — يتسلّمه المستلم' : '' }}</span>
        <div class="hp-stat-row">
            <div><b>{{ $fromSide['orders']->count() }}</b><small>فاتورة غير محصّلة</small></div>
            <div><b>{{ number_format($fromSide['remaining'], 2) }}</b><small>متبقٍّ للتحصيل</small></div>
            <div><b>{{ number_format($fromUnits) }}</b><small>وحدة في العربية ({{ $products->where('from', '>', 0)->count() }} صنف)</small></div>
        </div>
    </div>

    @if($toSide)
        <div class="hp-stat">
            <span class="hp-stat-label">مع المستلم الآن</span>
            <div class="hp-stat-row">
                <div><b>{{ $toSide['orders']->count() }}</b><small>فاتورة</small></div>
                <div><b>{{ number_format($toSide['remaining'], 2) }}</b><small>متبقٍّ</small></div>
                <div><b>{{ number_format($toUnits) }}</b><small>وحدة</small></div>
            </div>
        </div>

        <div class="hp-stat hp-stat--after">
            <span class="hp-stat-label">مع المستلم بعد التسليم</span>
            <div class="hp-stat-row">
                <div><b>{{ $toSide['orders']->count() + $fromSide['orders']->count() }}</b><small>فاتورة</small></div>
                <div><b>{{ number_format($toSide['remaining'] + $fromSide['remaining'], 2) }}</b><small>متبقٍّ</small></div>
                <div><b>{{ number_format($toUnits + $movedUnits) }}</b><small>وحدة</small></div>
            </div>
        </div>
    @else
        <div class="hp-stat hp-stat--hint">اختر المستلم لترى ما سيصير معه بعد التسليم.</div>
    @endif
</div>

<div class="hp-section">
    <h6>مخزون العربية</h6>
    @if($products->isEmpty())
        <p class="text-muted mb-0">لا مخزون في العربية.</p>
    @else
        <div class="table-responsive">
            <table class="table table-sm hp-table mb-0">
                <thead>
                    <tr>
                        <th>الصنف</th>
                        <th class="text-center">مع صاحب العهدة</th>
                        @if($toSide)
                            <th class="text-center">مع المستلم الآن</th>
                            <th class="text-center">مع المستلم بعد</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $p)
                        <tr>
                            <td>{{ $p['name'] }} @if($p['code'])<small class="text-muted">({{ $p['code'] }})</small>@endif</td>
                            <td class="text-center">{{ $p['from'] ?: '-' }}</td>
                            @if($toSide)
                                <td class="text-center">{{ $p['to'] ?: '-' }}</td>
                                <td class="text-center font-weight-bold">{{ ($includeStock ? $p['from'] : 0) + $p['to'] }}</td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<div class="hp-section">
    <h6>الفواتير غير المحصّلة التي ستُرحَّل ({{ $fromSide['orders']->count() }})</h6>
    @if($fromSide['orders']->isEmpty())
        <p class="text-muted mb-0">لا فواتير غير محصّلة.</p>
    @else
        <div class="table-responsive hp-scroll">
            <table class="table table-sm hp-table mb-0">
                <thead>
                    <tr>
                        <th>الفاتورة</th>
                        <th>العميل</th>
                        <th>التاريخ</th>
                        <th class="text-center">الإجمالي</th>
                        <th class="text-center">المحصّل</th>
                        <th class="text-center">المتبقي</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($fromSide['orders'] as $o)
                        <tr>
                            <td>#{{ $o->id }}</td>
                            <td>{{ optional($o->customer)->name ?? '-' }}</td>
                            <td>{{ optional($o->created_at)->format('Y-m-d') }}</td>
                            <td class="text-center">{{ number_format((float) $o->order_amount, 2) }}</td>
                            <td class="text-center">{{ number_format((float) $o->transaction_reference, 2) }}</td>
                            <td class="text-center font-weight-bold">{{ number_format(max(0, (float) $o->order_amount - (float) $o->transaction_reference), 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
