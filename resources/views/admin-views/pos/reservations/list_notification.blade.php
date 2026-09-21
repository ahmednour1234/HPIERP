@extends('layouts.admin.app')

@php
    // العنوان يتبع نوع الطلب: الشاشة نفسها تخدم الحجز والردّ والصرف،
    // وكان ثابتًا على «اوامر الصرف» في الحالات الثلاث.
    $rvTitle = match ((int) $type) {
        4       => \App\CPU\translate('تقرير حجز المناديب'),
        7       => \App\CPU\translate('تقرير رد حجز المناديب'),
        default => \App\CPU\translate('تقرير اوامر الصرف'),
    };
@endphp

@section('title', $rvTitle)

@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/custom.css') }}">
    @include('admin-views.roles._tokens')
    <style>
        /* ---------- الفلاتر ---------- */

        .rv-filters {
            display: grid;
            grid-template-columns: minmax(200px, 1.6fr) repeat(2, minmax(140px, 1fr)) auto;
            gap: .6rem;
            align-items: end;
        }

        .rv-filters .f-label {
            font-size: .74rem;
            font-weight: 700;
            color: var(--hpi-muted);
            margin-bottom: .25rem;
            display: block;
        }

        .rv-filters .form-control,
        .rv-filters select {
            border: 1px solid var(--hpi-line);
            border-radius: 10px;
            padding: .45rem .7rem;
            font-size: .85rem;
            height: auto;
            width: 100%;
        }

        .rv-filters .form-control:focus,
        .rv-filters select:focus {
            outline: 0;
            border-color: var(--hpi-blue);
            box-shadow: 0 0 0 .18rem rgba(142,197,239,.28);
        }

        .rv-filters .actions { display: flex; gap: .4rem; }

        @media (max-width: 991.98px) {
            .rv-filters { grid-template-columns: repeat(2, 1fr); }
            .rv-filters .actions { grid-column: 1 / -1; }
        }

        @media (max-width: 575.98px) {
            .rv-filters { grid-template-columns: 1fr; }
        }

        /* ---------- البطاقات ---------- */

        .rv-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1rem;
        }

        .rv-card {
            display: flex;
            flex-direction: column;
            background: #fff;
            border: 1px solid var(--hpi-line);
            border-radius: 14px;
            transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
        }

        .rv-card:hover {
            transform: translateY(-2px);
            border-color: var(--hpi-blue);
            box-shadow: 0 12px 26px rgba(20,57,92,.10);
        }

        .rv-head {
            display: flex;
            align-items: flex-start;
            gap: .7rem;
            padding: .9rem 1.05rem;
            border-bottom: 1px solid var(--hpi-line);
            background: #f9fcfe;
            border-top-left-radius: 14px;
            border-top-right-radius: 14px;
        }

        .rv-avatar {
            width: 38px;
            height: 38px;
            flex: none;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .85rem;
            font-weight: 800;
            background: var(--hpi-navy);
            color: #fff;
        }

        .rv-name { font-size: .9rem; font-weight: 700; color: var(--hpi-ink); margin: 0; }

        .rv-phone { font-size: .75rem; color: var(--hpi-muted); direction: ltr; text-align: start; }

        .rv-num {
            font-size: .7rem;
            font-weight: 700;
            color: var(--hpi-navy);
            background: #eaf4fb;
            border-radius: 99px;
            padding: .1rem .5rem;
            direction: ltr;
        }

        .rv-body { padding: .85rem 1.05rem; flex: 1; }

        .rv-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: .74rem;
            color: var(--hpi-muted);
            margin-bottom: .6rem;
        }

        /* قائمة الأصناف محدودة الارتفاع حتى تتساوى البطاقات مهما كثرت. */
        .rv-items {
            list-style: none;
            margin: 0;
            padding: 0;
            max-height: 150px;
            overflow-y: auto;
        }

        .rv-items li {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
            padding: .3rem 0;
            font-size: .82rem;
            border-bottom: 1px dashed var(--hpi-line);
        }

        .rv-items li:last-child { border-bottom: 0; }

        .rv-items .p-name {
            color: var(--hpi-ink);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .rv-qty {
            flex: none;
            font-size: .72rem;
            font-weight: 800;
            color: var(--hpi-navy);
            background: #f1f7fc;
            border: 1px solid #dceaf6;
            border-radius: 99px;
            padding: .05rem .5rem;
            direction: ltr;
        }

        .rv-foot {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
            padding: .65rem 1.05rem;
            border-top: 1px solid var(--hpi-line);
            background: #fbfdff;
            border-bottom-left-radius: 14px;
            border-bottom-right-radius: 14px;
        }

        .rv-state {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            font-size: .72rem;
            font-weight: 700;
            border-radius: 99px;
            padding: .15rem .55rem;
            white-space: nowrap;
        }

        .rv-state.badge-pending  { background: #fef4e4; color: #a86412; }
        .rv-state.badge-approved { background: #e6f7ef; color: #0f7a4d; }
        .rv-state.badge-rejected { background: #fdecec; color: #b3261e; }

        .rv-actions { display: flex; gap: .3rem; }

        .rv-empty {
            grid-column: 1 / -1;
            padding: 3rem 1rem;
            text-align: center;
            color: var(--hpi-muted);
            background: #fff;
            border: 1px dashed var(--hpi-line);
            border-radius: 14px;
        }

        .rv-pager {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            margin-top: 1.25rem;
        }

        @media print {
            .roles-hero, .roles-panel, .rv-actions, .rv-pager { display: none !important; }
            .rv-grid { grid-template-columns: repeat(2, 1fr); }
            .rv-card { break-inside: avoid; box-shadow: none; }
        }
    </style>
@endpush

@section('content')
<div class="roles-wrap">

    <div class="roles-hero">
        <div class="hero-text">
            {{-- كان العنوان شارة عدد وحدها، فتطفو الصفحة بلا اسم. --}}
            <h1><i class="tio-shipping mr-1"></i> {{ $rvTitle }}</h1>
            <p>{{ number_format($reservations->total()) }} {{ \App\CPU\translate('طلب') }}</p>
        </div>

        <div class="hero-actions">
            <button type="button" class="btn btn-solid" onclick="window.print()">
                <i class="tio-print mr-1"></i> {{ \App\CPU\translate('طباعة التقرير') }}
            </button>
        </div>
    </div>

    <div class="roles-panel">
        <div class="head" style="display:block;">
            <form action="{{ url()->current() }}" method="GET" class="rv-filters">
                <div>
                    <label class="f-label" for="rv-seller">{{ \App\CPU\translate('المندوب') }}</label>
                    {{-- قائمة بدل كتابة الاسم: الاسم مقسوم على عمودين
                         (f_name / l_name) فالبحث بالاسم الكامل لا يطابق
                         شيئًا، وأي فرق في التهجئة يُفشله. --}}
                    <select name="seller_id" id="rv-seller">
                        <option value="">{{ \App\CPU\translate('كل المناديب') }}</option>
                        @foreach($sellers as $seller)
                            <option value="{{ $seller->id }}"
                                @selected((string) $sellerId === (string) $seller->id)>
                                {{ trim($seller->f_name . ' ' . $seller->l_name) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="f-label" for="rv-from">{{ \App\CPU\translate('من') }}</label>
                    <input type="date" id="rv-from" name="from_date" class="form-control" value="{{ $fromDate }}">
                </div>

                <div>
                    <label class="f-label" for="rv-to">{{ \App\CPU\translate('إلى') }}</label>
                    <input type="date" id="rv-to" name="to_date" class="form-control" value="{{ $toDate }}">
                </div>

                <div class="actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="tio-filter-list mr-1"></i> {{ \App\CPU\translate('فلتر') }}
                    </button>

                    {{-- يصدّر ما تطابقه الفلاتر الحالية لا الصفحة المعروضة. --}}
                    <a href="{{ route('admin.pos.reservation_export_notification', array_merge(['type' => $type, 'active' => $active], request()->query())) }}"
                       class="btn btn-success">
                        <i class="tio-file-outlined mr-1"></i> {{ \App\CPU\translate('تصدير Excel') }}
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- بطاقات بدل جدول: الجدول كان يضع النوافذ المنبثقة داخل <tbody>،
         وهو ترميز غير صالح فيخرجها المتصفح خارج الجدول ويكسر الصفحة. --}}
    <div class="rv-grid">
        @forelse($reservations as $key => $item)
            @php($products = json_decode($item->data) ?: [])
            @php($sellerName = trim(($item->seller->f_name ?? '') . ' ' . ($item->seller->l_name ?? '')))

            <div class="rv-card">
                <div class="rv-head">
                    <div class="rv-avatar">{{ mb_substr($sellerName ?: '؟', 0, 1) }}</div>

                    <div class="flex-grow-1" style="min-width:0;">
                        <p class="rv-name">{{ $sellerName ?: '—' }}</p>
                        <div class="rv-phone">{{ $item->seller->phone ?? '—' }}</div>
                    </div>

                    <span class="rv-num">#{{ $reservations->firstItem() + $key }}</span>
                </div>

                <div class="rv-body">
                    <div class="rv-meta">
                        <span>
                            <i class="tio-date-range"></i>
                            {{ date('d M Y H:i', strtotime($item->created_at)) }}
                        </span>
                        <span>{{ count($products) }} {{ \App\CPU\translate('صنف') }}</span>
                    </div>

                    <ul class="rv-items">
                        @forelse($products as $product)
                            <li>
                                <span class="p-name" title="{{ $product->product_name ?? '' }}">
                                    {{ $product->product_name ?? '—' }}
                                </span>
                                <span class="rv-qty">{{ $product->stock ?? 0 }}</span>
                            </li>
                        @empty
                            <li><span class="p-name text-muted">{{ \App\CPU\translate('لا أصناف') }}</span></li>
                        @endforelse
                    </ul>
                </div>

                <div class="rv-foot">
                    <span class="rv-state badge-{{ $item->status_class }}">{{ $item->status_text }}</span>

                    <div class="rv-actions">
                        @if($type != 3)
                            <a href="{{ route('admin.pos.generate_reservation_invoice_notification', $item->id) }}"
                               class="btn btn-sm btn-soft-primary" title="{{ \App\CPU\translate('مراجعة الطلب') }}">
                                <i class="tio-visible-outlined"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-soft-danger"
                                    data-toggle="modal" data-target="#rejectModal-{{ $item->id }}"
                                    title="{{ \App\CPU\translate('رفض الطلب') }}">
                                <i class="tio-clear"></i>
                            </button>
                        @else
                            <button type="button" class="btn btn-sm btn-soft-success"
                                    onclick="print_invoice('{{ $item->id }}')"
                                    title="{{ \App\CPU\translate('طباعة الفاتورة') }}">
                                <i class="tio-print-outlined"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-soft-warning"
                                    data-toggle="modal" data-target="#returnModal-{{ $item->id }}"
                                    title="{{ \App\CPU\translate('رد المخزون') }}">
                                <i class="tio-undo"></i>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="rv-empty">
                <img class="img-fluid mb-3" style="max-width:180px;"
                     src="{{ asset('public/assets/admin/svg/illustrations/sorry.svg') }}"
                     alt="{{ \App\CPU\translate('لا توجد طلبات لعرضها') }}">
                <p class="mb-0">{{ \App\CPU\translate('لا توجد طلبات لعرضها') }}</p>
            </div>
        @endforelse
    </div>

    @if($reservations->total() > 0)
        <div class="rv-pager">
            <span class="text-muted small">
                {{ \App\CPU\translate('عرض') }} {{ $reservations->firstItem() }} -
                {{ $reservations->lastItem() }}
                {{ \App\CPU\translate('من أصل') }} {{ $reservations->total() }}
            </span>

            {!! $reservations->links() !!}
        </div>
    @endif

    {{-- النوافذ خارج الشبكة: وضعها داخل البطاقة يجعل تحريكها مع
         تحويل البطاقة عند المرور فوقها. --}}
    @foreach($reservations as $item)
        {{-- أصناف هذا الطلب تحديدًا: كان يُعاد استعمال $products المتسرّب
             من حلقة البطاقات، فتعرض كل نافذة أصناف آخر طلب في الصفحة. --}}
        @php($modalProducts = json_decode($item->data) ?: [])

        <div class="modal fade" id="rejectModal-{{ $item->id }}" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title">{{ \App\CPU\translate('تأكيد الرفض') }}</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <form action="{{ route('admin.pos.deactivateReservedProductsByReservationId', $item->id) }}"
                          method="POST">
                        @csrf
                        <div class="modal-body">
                            <div class="text-center mb-4">
                                <i class="tio-alert-outlined text-danger" style="font-size: 2.5rem;"></i>
                                <h4 class="mt-3">{{ \App\CPU\translate('هل أنت متأكد من رفض هذا الطلب؟') }}</h4>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                {{ \App\CPU\translate('إلغاء') }}
                            </button>
                            <button type="submit" class="btn btn-danger">
                                {{ \App\CPU\translate('تأكيد الرفض') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- الرد يعكس الصرف: ينقص من رصيد المندوب ويعيد الكمية إلى
             المخزن. لا يُسمح إلا بما لم يُبَع بعد. --}}
        <div class="modal fade" id="returnModal-{{ $item->id }}" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title">{{ \App\CPU\translate('رد مخزون تم صرفه') }}</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <form action="{{ route('admin.pos.return-dispatch', $item->id) }}" method="POST">
                        @csrf
                        <div class="modal-body">
                            <table class="table table-sm table-bordered">
                                <thead class="thead-light">
                                    <tr>
                                        <th>{{ \App\CPU\translate('الصنف') }}</th>
                                        <th class="text-center">{{ \App\CPU\translate('المصروف') }}</th>
                                        <th class="text-center" style="width:120px;">
                                            {{ \App\CPU\translate('الكمية المردودة') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($modalProducts as $idx => $line)
                                        <tr>
                                            <td>{{ $line->product_name ?? '' }}</td>
                                            <td class="text-center">{{ $line->stock ?? 0 }}</td>
                                            <td>
                                                <input type="number" min="0"
                                                       max="{{ $line->stock ?? 0 }}"
                                                       name="quantities[{{ $idx }}]"
                                                       class="form-control form-control-sm"
                                                       value="0">
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <div class="form-group mb-0">
                                <label class="small">{{ \App\CPU\translate('سبب الرد') }}</label>
                                <input type="text" name="note" class="form-control form-control-sm"
                                       maxlength="500">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                {{ \App\CPU\translate('إلغاء') }}
                            </button>
                            <button type="submit" class="btn btn-warning">
                                {{ \App\CPU\translate('تأكيد الرد') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="modal fade col-md-12" id="print-invoice" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content modal-content1">
            <div class="modal-header">
                <h5 class="modal-title">{{\App\CPU\translate('طباعة')}} {{\App\CPU\translate('الفاتورة')}}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span class="text-dark" aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body row">
                <div class="col-md-12">
                    <center>
                        <input type="button" class="mt-2 btn btn-primary non-printable"
                               onclick="printDiv('printableArea')"
                               value="{{\App\CPU\translate('لو متصل بالطابعة اطبع')}}."/>
                        <a href="{{url()->previous()}}"
                           class="mt-2 btn btn-danger non-printable">{{\App\CPU\translate('عودة')}}</a>
                    </center>
                    <hr class="non-printable">
                </div>
                <div class="row m-auto" id="printableArea"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script_2')
    <script src={{asset("public/assets/admin/js/global.js")}}></script>
    <script>
        "use strict";

        function print_invoice(id) {
            $.get({
                url: '{{url('/')}}/admin/pos/we/reservations/invoicea2/' + id,
                dataType: 'json',
                beforeSend: function () {
                    $('#loading').show();
                },
                success: function (data) {
                    $('#print-invoice').modal('show');
                    $('#printableArea').empty().html(data.view);
                },
                complete: function () {
                    $('#loading').hide();
                },
                error: function (error) {
                    console.log(error);
                }
            });
        }
    </script>
@endpush
