@extends('layouts.admin.app')

@section('title', \App\CPU\translate('التحصيلات'))

@push('css_or_js')
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/custom.css"/>
    @include('admin-views.roles._tokens')
    <style>
        /* ---------- الفلاتر ---------- */

        .in-filters {
            display: grid;
            grid-template-columns: minmax(200px, 1.6fr) minmax(170px, 1fr) repeat(2, minmax(140px, .9fr));
            gap: .7rem;
            align-items: start;
        }

        .in-filters .f-label {
            font-size: .74rem;
            font-weight: 700;
            color: var(--hpi-muted);
            margin-bottom: .25rem;
            display: block;
        }

        .in-filters .form-control,
        .in-filters select {
            border: 1px solid var(--hpi-line);
            border-radius: 10px;
            padding: .45rem .7rem;
            font-size: .85rem;
            height: auto;
            width: 100%;
        }

        .in-filters .form-control:focus,
        .in-filters select:focus {
            outline: 0;
            border-color: var(--hpi-blue);
            box-shadow: 0 0 0 .18rem rgba(142,197,239,.28);
        }

        .in-actions {
            display: flex;
            flex-wrap: wrap;
            gap: .4rem;
            margin-top: .9rem;
            padding-top: .9rem;
            border-top: 1px solid var(--hpi-line);
        }

        @media (max-width: 991.98px) { .in-filters { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 575.98px) { .in-filters { grid-template-columns: 1fr; } }

        /* ---------- الملخّص ---------- */

        .in-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 1rem;
            margin-bottom: 1.25rem;
        }

        .in-stat {
            background: #fff;
            border: 1px solid var(--hpi-line);
            border-radius: 14px;
            padding: 1rem 1.15rem;
            display: flex;
            align-items: center;
            gap: .85rem;
        }

        .in-stat .s-icon {
            width: 42px;
            height: 42px;
            flex: none;
            border-radius: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            background: #e6f7ef;
            color: #0f7a4d;
        }

        .in-stat .s-icon.is-blue { background: #eaf4fb; color: var(--hpi-navy); }

        .in-stat .s-label { font-size: .76rem; color: var(--hpi-muted); margin-bottom: .1rem; }

        .in-stat .s-value {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--hpi-ink);
            line-height: 1.2;
            direction: ltr;
            text-align: start;
        }

        .in-stat .s-value .unit { font-size: .76rem; font-weight: 600; color: var(--hpi-muted); }

        /* ---------- الجدول ---------- */

        .in-table { width: 100%; margin: 0; }

        .in-table thead th {
            background: #f6fafd;
            font-size: .75rem;
            font-weight: 700;
            color: var(--hpi-muted);
            border: 0;
            border-bottom: 1px solid var(--hpi-line);
            padding: .65rem .7rem;
            white-space: nowrap;
        }

        .in-table tbody td {
            padding: .6rem .7rem;
            border-top: 1px solid var(--hpi-line);
            font-size: .84rem;
            vertical-align: middle;
            white-space: nowrap;
        }

        .in-table tbody tr:hover { background: #fbfdff; }

        .in-table .col-num { direction: ltr; text-align: start; font-variant-numeric: tabular-nums; }

        .in-table .col-price {
            font-weight: 800;
            color: var(--hpi-ink);
            direction: ltr;
            text-align: start;
        }

        /* الملاحظة وحدها تلتف؛ بقية الأعمدة سطر واحد. */
        .in-table .col-note {
            white-space: normal;
            max-width: 16rem;
            font-size: .8rem;
            color: var(--hpi-muted);
        }

        .in-thumb {
            width: 42px;
            height: 42px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid var(--hpi-line);
            cursor: pointer;
            background: #f3f6f9;
            transition: transform .15s ease;
        }

        .in-thumb:hover { transform: scale(1.08); border-color: var(--hpi-blue); }

        .in-noimg {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 42px;
            height: 42px;
            border-radius: 8px;
            border: 1px dashed var(--hpi-line);
            color: var(--hpi-muted);
        }

        .in-row-actions { display: flex; gap: .3rem; white-space: nowrap; }

        .in-order {
            font-size: .78rem;
            font-weight: 700;
            color: var(--hpi-navy);
            direction: ltr;
        }

        .in-order:hover { text-decoration: underline; }

        .in-empty { padding: 3rem 1rem; text-align: center; color: var(--hpi-muted); }

        .in-pager {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            padding: .9rem 1.2rem;
            border-top: 1px solid var(--hpi-line);
        }

        @media print {
            .roles-hero, .roles-panel > .head, .in-actions, .in-pager,
            .in-row-actions { display: none !important; }
        }
    </style>
@endpush

@section('content')
<div class="roles-wrap">

    <div class="roles-hero">
        <div class="hero-text">
            <h1><i class="tio-wallet mr-1"></i> {{ \App\CPU\translate('التحصيلات') }}</h1>
            <p>{{ number_format($installments->total()) }} {{ \App\CPU\translate('عملية تحصيل') }}</p>
        </div>
    </div>

    <div class="roles-panel">
        <div class="head" style="display:block;">
            <form action="{{ url()->current() }}" method="GET">
                <div class="in-filters">
                    <div>
                        <label class="f-label" for="in-search">{{ \App\CPU\translate('بحث') }}</label>
                        <input type="search" id="in-search" name="search" class="form-control"
                               placeholder="{{ \App\CPU\translate('رقم الفاتورة، اسم العميل أو البائع') }}"
                               value="{{ $search }}">
                    </div>

                    <div>
                        <label class="f-label" for="in-region">{{ \App\CPU\translate('المنطقة') }}</label>
                        <select name="region_id[]" id="in-region" multiple
                                data-placeholder="{{ \App\CPU\translate('اختر المنطقة') }}">
                            @foreach($regions as $region)
                                <option value="{{ $region->id }}"
                                    @selected(in_array((string) $region->id, (array) $regionId))>
                                    {{ $region->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="f-label" for="in-from">{{ \App\CPU\translate('من تاريخ') }}</label>
                        <input type="date" id="in-from" name="from_date" class="form-control" value="{{ $fromDate }}">
                    </div>

                    <div>
                        <label class="f-label" for="in-to">{{ \App\CPU\translate('إلى تاريخ') }}</label>
                        <input type="date" id="in-to" name="to_date" class="form-control" value="{{ $toDate }}">
                    </div>
                </div>

                <div class="in-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="tio-filter-list mr-1"></i> {{ \App\CPU\translate('تطبيق') }}
                    </button>

                    @if(request()->hasAny(['search', 'region_id', 'from_date', 'to_date']))
                        <a href="{{ url()->current() }}" class="btn btn-outline-secondary">
                            {{ \App\CPU\translate('reset') }}
                        </a>
                    @endif

                    <span class="flex-grow-1"></span>

                    {{-- يحمل الفلاتر الحالية، فيطابق التصدير ما تعرضه الشاشة.
                         كان داخل نموذج «عكس التحصيل» في كل صف، فيتكرّر بعدد
                         الصفوف ويقع داخل خلية جدول. --}}
                    <a href="{{ route('admin.pos.installments.export', request()->query()) }}"
                       class="btn btn-success">
                        <i class="tio-file-outlined mr-1"></i> {{ \App\CPU\translate('تصدير CSV') }}
                    </a>

                    <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
                        <i class="tio-print mr-1"></i> {{ \App\CPU\translate('طباعة') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="in-stats">
        <div class="in-stat">
            <div class="s-icon"><i class="tio-wallet"></i></div>
            <div>
                <div class="s-label">{{ \App\CPU\translate('إجمالي المبالغ المحصلة') }}</div>
                <div class="s-value">{{ number_format($totalAmount, 2) }} <span class="unit">ج.م</span></div>
            </div>
        </div>

        <div class="in-stat">
            <div class="s-icon is-blue"><i class="tio-receipt"></i></div>
            <div>
                <div class="s-label">{{ \App\CPU\translate('إجمالي الفواتير الكاش') }}</div>
                <div class="s-value">{{ number_format($collectedCashSum, 2) }} <span class="unit">ج.م</span></div>
            </div>
        </div>
    </div>

    <div class="roles-panel">
        <div class="body p-0">
            <div class="table-responsive">
                <table class="in-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ \App\CPU\translate('اسم البائع') }}</th>
                            <th>{{ \App\CPU\translate('اسم العميل') }}</th>
                            <th>{{ \App\CPU\translate('المنطقة') }}</th>
                            <th>{{ \App\CPU\translate('السعر') }}</th>
                            <th>{{ \App\CPU\translate('ملاحظة') }}</th>
                            <th>{{ \App\CPU\translate('التاريخ') }}</th>
                            <th>{{ \App\CPU\translate('رقم الفاتورة') }}</th>
                            <th>{{ \App\CPU\translate('الصورة') }}</th>
                            <th>{{ \App\CPU\translate('الإجراءات') }}</th>
                        </tr>
                    </thead>

                    <tbody>
                    @forelse($installments as $key => $installment)
                        @php
                            // عمود img يحمل البادئة "shop/" أصلًا، فإضافتها
                            // مرة أخرى تنتج storage/shop/shop/... ولا تُحمَّل.
                            $img = $installment->img
                                ? asset('storage/' . ltrim($installment->img, '/'))
                                : null;
                        @endphp

                        <tr>
                            <td class="col-num">{{ $key + $installments->firstItem() }}</td>

                            <td>{{ trim(optional($installment->seller)->f_name . ' ' . optional($installment->seller)->l_name) ?: '—' }}</td>
                            <td>{{ optional($installment->customer)->name ?: '—' }}</td>
                            <td>{{ $installment->customer->regions->name ?? '—' }}</td>

                            <td class="col-price">{{ number_format($installment->total_price, 2) }}</td>
                            <td class="col-note">{{ $installment->note ?: '—' }}</td>

                            <td class="col-num">
                                {{ \Carbon\Carbon::parse($installment->created_at)->format('Y-m-d') }}
                            </td>

                            <td>
                                @if($installment->order_id)
                                    <a class="in-order"
                                       href="{{ route('admin.pos.orders', ['search' => $installment->order_id]) }}"
                                       title="{{ \App\CPU\translate('orders') }}">{{ $installment->order_id }}</a>
                                @else
                                    —
                                @endif
                            </td>

                            <td>
                                @if($img)
                                    <img src="{{ $img }}" alt="{{ \App\CPU\translate('الصورة') }}"
                                         class="in-thumb" loading="lazy"
                                         data-toggle="modal" data-target="#imageModal{{ $installment->id }}"
                                         onerror="this.outerHTML='&lt;span class=\'in-noimg\'&gt;&lt;i class=\'tio-image\'&gt;&lt;/i&gt;&lt;/span&gt;'">
                                @else
                                    <span class="in-noimg"><i class="tio-image"></i></span>
                                @endif
                            </td>

                            <td>
                                <div class="in-row-actions">
                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                            onclick="print_invoice('{{ $installment->id }}')">
                                        <i class="tio-download"></i> {{ \App\CPU\translate('فاتورة') }}
                                    </button>

                                    <form action="{{ route('admin.pos.cancelInstallment', ['id' => $installment->id]) }}"
                                          method="POST" class="d-inline"
                                          onsubmit="return confirm('هل أنت متأكد من عكس عملية التحصيل لهذا القسط؟');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="tio-history"></i> {{ \App\CPU\translate('عكس التحصيل') }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10">
                                <div class="in-empty">
                                    <i class="tio-wallet" style="font-size:2rem;opacity:.4"></i>
                                    <p class="mt-2 mb-0">{{ \App\CPU\translate('No_data_to_show') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if($installments->hasPages())
                <div class="in-pager">
                    <span class="text-muted small">
                        {{ \App\CPU\translate('عرض') }} {{ $installments->firstItem() }} -
                        {{ $installments->lastItem() }}
                        {{ \App\CPU\translate('من أصل') }} {{ number_format($installments->total()) }}
                    </span>

                    {{ $installments->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- النوافذ خارج الجدول: <div> داخل <tbody> ترميز غير صالح فينقله
         المتصفح خارج الجدول. --}}
    @foreach($installments as $installment)
        @if($installment->img)
            <div class="modal fade" id="imageModal{{ $installment->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ \App\CPU\translate('الصورة') }}</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body text-center">
                            <img src="{{ asset('storage/' . ltrim($installment->img, '/')) }}"
                                 alt="{{ \App\CPU\translate('الصورة') }}"
                                 style="max-width: 100%; height: auto;">
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endforeach

    <!-- مودال طباعة الفاتورة -->
    <div class="modal fade" id="print-invoice" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content modal-content1">
                <div class="modal-header">
                    <h5 class="modal-title">{{ \App\CPU\translate('طباعة الفاتورة') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-4">
                    <div class="text-center mb-3">
                        <button type="button" class="btn btn-primary mr-2" onclick="printDiv('printableArea')">
                            {{ \App\CPU\translate('إجراء الطباعة إذا كانت الطابعة الحرارية جاهزة') }}
                        </button>
                        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">
                            {{ \App\CPU\translate('عودة') }}
                        </button>
                    </div>
                    <hr>
                    <div id="printableArea"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script_2')
    <script src={{asset("public/assets/admin/js/global.js")}}></script>
    <script>
        "use strict";

        function print_invoice(order_id) {
            $.get({
                url: '{{url('admin/pos/installments/invoice')}}/' + order_id,
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
                    console.log(error.responseText);
                }
            });
        }
    </script>
@endpush
