@extends('layouts.admin.app')

@section('title', \App\CPU\translate('قائمة التحويلات من المناديب'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/custom.css') }}">
    @include('admin-views.roles._tokens')
    <style>
        .tx-filters {
            display: grid;
            grid-template-columns: minmax(180px, 1.4fr) minmax(150px, 1fr) repeat(2, minmax(130px, .8fr)) auto;
            gap: .6rem;
            align-items: end;
        }

        .tx-filters .f-label {
            font-size: .74rem;
            font-weight: 700;
            color: var(--hpi-muted);
            margin-bottom: .25rem;
            display: block;
        }

        .tx-filters .form-control,
        .tx-filters select {
            border: 1px solid var(--hpi-line);
            border-radius: 10px;
            padding: .45rem .7rem;
            font-size: .85rem;
            height: auto;
            width: 100%;
        }

        .tx-filters .form-control:focus,
        .tx-filters select:focus {
            outline: 0;
            border-color: var(--hpi-blue);
            box-shadow: 0 0 0 .18rem rgba(142,197,239,.28);
        }

        .tx-filters .actions { display: flex; gap: .4rem; }

        /* الأعمدة تتزاحم على الشاشات الضيقة، فتُكدَّس بدل أن تتداخل. */
        @media (max-width: 991.98px) {
            .tx-filters { grid-template-columns: repeat(2, 1fr); }
            .tx-filters .actions { grid-column: 1 / -1; }
        }

        @media (max-width: 575.98px) {
            .tx-filters { grid-template-columns: 1fr; }
        }

        /* ---------- الجدول ---------- */

        .tx-table { width: 100%; margin: 0; }

        .tx-table thead th {
            background: #f6fafd;
            font-size: .76rem;
            font-weight: 700;
            color: var(--hpi-muted);
            border: 0;
            border-bottom: 1px solid var(--hpi-line);
            padding: .7rem .8rem;
            white-space: nowrap;
        }

        .tx-table tbody td {
            padding: .7rem .8rem;
            border-top: 1px solid var(--hpi-line);
            font-size: .85rem;
            vertical-align: middle;
        }

        .tx-table tbody tr:hover { background: #fbfdff; }

        /* الملاحظة وحدها تلتف؛ بقية الأعمدة تبقى سطرًا واحدًا. */
        .tx-table .col-note {
            max-width: 22rem;
            white-space: normal;
            word-break: break-word;
            color: var(--hpi-muted);
            font-size: .8rem;
        }

        .tx-table .col-amount {
            font-weight: 800;
            color: var(--hpi-ink);
            direction: ltr;
            text-align: start;
            white-space: nowrap;
        }

        .tx-table .col-date,
        .tx-table .col-debt { white-space: nowrap; direction: ltr; text-align: start; }

        .tx-table .col-mail {
            direction: ltr;
            text-align: start;
            font-size: .78rem;
            color: var(--hpi-muted);
        }

        /* ---------- الصورة ---------- */

        .tx-thumb {
            width: 46px;
            height: 46px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid var(--hpi-line);
            cursor: pointer;
            transition: transform .15s ease;
            background: #f3f6f9;
        }

        .tx-thumb:hover { transform: scale(1.06); border-color: var(--hpi-blue); }

        .tx-noimg {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 46px;
            height: 46px;
            border-radius: 8px;
            border: 1px dashed var(--hpi-line);
            color: var(--hpi-muted);
            font-size: 1.1rem;
        }

        /* ---------- الحالة ---------- */

        .tx-state {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            font-size: .76rem;
            font-weight: 700;
            border-radius: 99px;
            padding: .2rem .6rem;
            white-space: nowrap;
        }

        .tx-state.is-ok     { background: #e6f7ef; color: #0f7a4d; }
        .tx-state.is-no     { background: #fdecec; color: #b3261e; }
        .tx-state.is-wait   { background: #fef4e4; color: #a86412; }

        .tx-actions { display: flex; gap: .35rem; white-space: nowrap; }

        .tx-empty { padding: 3rem 1rem; text-align: center; color: var(--hpi-muted); }
    </style>
@endpush

@section('content')
<div class="roles-wrap">

    @php
        $pending = $transactions->getCollection()->where('active', 0)->count();
    @endphp

    <div class="roles-hero">
        <div class="hero-text">
            <h1><i class="tio-swap-horizontal mr-1"></i> {{ \App\CPU\translate('قائمة التحويلات من المناديب') }}</h1>
            <p>
                {{ number_format($transactions->total()) }} تحويل
                @if($pending)
                    &middot; <strong>{{ $pending }}</strong> بانتظار المراجعة في هذه الصفحة
                @endif
            </p>
        </div>
    </div>

    <div class="roles-panel">
        <div class="head" style="display:block;">
            <form action="{{ url()->current() }}" method="GET" class="tx-filters">
                <div>
                    <label class="f-label" for="tx-search">{{ \App\CPU\translate('بحث بالاسم او الايميل') }}</label>
                    <input type="search" id="tx-search" name="search" class="form-control"
                           placeholder="{{ \App\CPU\translate('بحث بالاسم او الايميل') }}"
                           value="{{ request('search') }}">
                </div>

                <div>
                    <label class="f-label" for="tx-seller">{{ \App\CPU\translate('اختار البائع') }}</label>
                    {{-- select مفرد: لا يرقّيه التخطيط إلى bootstrap-select. --}}
                    <select name="seller_id" id="tx-seller">
                        <option value="">{{ \App\CPU\translate('اختار البائع') }}</option>
                        @foreach($sellers as $seller)
                            <option value="{{ $seller->id }}"
                                @selected((string) request('seller_id') === (string) $seller->id)>
                                {{ trim($seller->f_name . ' ' . $seller->l_name) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="f-label" for="tx-from">{{ \App\CPU\translate('من تاريخ') }}</label>
                    <input type="date" id="tx-from" name="start_date" class="form-control"
                           value="{{ request('start_date') }}">
                </div>

                <div>
                    <label class="f-label" for="tx-to">{{ \App\CPU\translate('إلى تاريخ') }}</label>
                    <input type="date" id="tx-to" name="end_date" class="form-control"
                           value="{{ request('end_date') }}">
                </div>

                <div class="actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="tio-search mr-1"></i> {{ \App\CPU\translate('بحث') }}
                    </button>
                    <a href="{{ url()->current() }}" class="btn btn-outline-secondary">
                        {{ \App\CPU\translate('إعادة تعيين') }}
                    </a>
                </div>
            </form>
        </div>

        <div class="body p-0">
            <div class="table-responsive">
                <table class="tx-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ \App\CPU\translate('تاريخ التحويل') }}</th>
                            <th>{{ \App\CPU\translate('الاسم') }}</th>
                            <th>{{ \App\CPU\translate('الايميل') }}</th>
                            <th>{{ \App\CPU\translate('المديونية') }}</th>
                            <th>{{ \App\CPU\translate('الحساب') }}</th>
                            <th>{{ \App\CPU\translate('الملاحظة') }}</th>
                            <th>{{ \App\CPU\translate('المبلغ') }}</th>
                            <th>{{ \App\CPU\translate('الصورة') }}</th>
                            <th>{{ \App\CPU\translate('action') }}</th>
                        </tr>
                    </thead>

                    <tbody>
                    @forelse($transactions as $key => $transaction)
                        <tr>
                            <td>{{ $transactions->firstItem() + $key }}</td>

                            <td class="col-date">
                                {{ optional($transaction->created_at)->format('Y-m-d H:i') ?: '—' }}
                            </td>

                            <td>{{ trim(($transaction->sellers->f_name ?? '') . ' ' . ($transaction->sellers->l_name ?? '')) ?: '—' }}</td>
                            <td class="col-mail">{{ $transaction->sellers->email ?? '—' }}</td>
                            <td class="col-debt">{{ number_format((float) ($transaction->sellers->credit ?? 0), 2) }}</td>
                            <td>{{ $transaction->accounts->account ?? '—' }}</td>

                            <td class="col-note">{{ $transaction->note ?: '—' }}</td>
                            <td class="col-amount">{{ number_format((float) $transaction->amount, 2) }}</td>

                            <td>
                                @php($imgPath = $transaction->img ? asset('storage/' . $transaction->img) : null)

                                @if($imgPath)
                                    {{-- الملف قد يكون مفقودًا على هذه النسخة، فتظهر أيقونة
                                         بدل رمز صورة مكسورة يملأ الصف. --}}
                                    <img src="{{ $imgPath }}" alt="صورة التحويل" class="tx-thumb"
                                         loading="lazy"
                                         data-toggle="modal"
                                         data-target="#imageModal{{ $transaction->id }}"
                                         onerror="this.outerHTML='&lt;span class=\'tx-noimg\' title=\'الصورة غير متاحة\'&gt;&lt;i class=\'tio-image\'&gt;&lt;/i&gt;&lt;/span&gt;'">

                                    <div class="modal fade" id="imageModal{{ $transaction->id }}" tabindex="-1"
                                         aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-body text-center p-0">
                                                    <img src="{{ $imgPath }}" alt="صورة التحويل"
                                                         class="img-fluid rounded" style="max-height: 90vh;">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <span class="tx-noimg" title="بلا صورة"><i class="tio-image"></i></span>
                                @endif
                            </td>

                            <td>
                                @if((int) $transaction->active === 0)
                                    <div class="tx-actions">
                                        <form action="{{ route('admin.TransactionSeller.status', $transaction->id) }}"
                                              method="POST">
                                            @csrf @method('PUT')
                                            <input type="hidden" name="active" value="1">
                                            <button type="submit" class="btn btn-success btn-sm">
                                                {{ \App\CPU\translate('موافقة على التحويل') }}
                                            </button>
                                        </form>

                                        <form action="{{ route('admin.TransactionSeller.status', $transaction->id) }}"
                                              method="POST">
                                            @csrf @method('PUT')
                                            <input type="hidden" name="active" value="2">
                                            <button type="submit" class="btn btn-outline-danger btn-sm"
                                                    onclick="return confirm('رفض هذا التحويل؟')">
                                                {{ \App\CPU\translate('رفض التحويل') }}
                                            </button>
                                        </form>
                                    </div>
                                @elseif((int) $transaction->active === 1)
                                    <span class="tx-state is-ok">
                                        <i class="tio-checkmark-circle"></i> {{ \App\CPU\translate('تمت الموافقة على التحويل') }}
                                    </span>
                                @elseif((int) $transaction->active === 2)
                                    <span class="tx-state is-no">
                                        <i class="tio-clear-circle"></i> {{ \App\CPU\translate('تم رفض التحويل') }}
                                    </span>
                                @else
                                    <span class="tx-state is-wait">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10">
                                <div class="tx-empty">
                                    <img class="mb-3 w-one-cl"
                                         src="{{ asset('public/assets/admin/svg/illustrations/sorry.svg') }}"
                                         alt="{{ \App\CPU\translate('Image Description') }}">
                                    <p class="mb-0">{{ \App\CPU\translate('لاتوجد تجويلات لعرضها') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if($transactions->hasPages())
                <div class="p-3 border-top">
                    {!! $transactions->appends(request()->query())->onEachSide(1)->links() !!}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('script_2')
    <script src="{{ asset('public/assets/admin/js/global.js') }}"></script>
@endpush
