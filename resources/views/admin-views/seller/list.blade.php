@extends('layouts.admin.app')

@section('title',\App\CPU\translate('seller_list'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/custom.css"/>
@endpush

@section('content')
<style>
    .sellers-page { --sl-navy:#11245a; --sl-ink:#1f2d3d; --sl-muted:#6b7a90;
                    --sl-line:#e6edf5; --sl-soft:#f4f8fc; }

    .sellers-head {
        display:flex; align-items:center; justify-content:space-between;
        flex-wrap:wrap; gap:1rem; margin-bottom:1.1rem;
    }
    .sellers-head h1 {
        display:flex; align-items:center; gap:.6rem;
        font-size:1.3rem; font-weight:800; color:var(--sl-navy); margin:0;
    }
    .sellers-head .count {
        background:var(--sl-navy); color:#fff; border-radius:999px;
        font-size:.78rem; font-weight:700; padding:.2rem .65rem;
    }

    .sellers-card {
        border:1px solid var(--sl-line); border-radius:14px; background:#fff;
        box-shadow:0 10px 26px rgba(15,23,42,.06); overflow:hidden;
    }

    .sellers-toolbar {
        display:flex; align-items:center; gap:.75rem; flex-wrap:wrap;
        padding:1rem 1.15rem; background:var(--sl-soft);
        border-bottom:1px solid var(--sl-line);
    }
    .sellers-toolbar form { flex:1 1 320px; margin:0; }
    .sellers-toolbar .input-group .form-control { border-color:var(--sl-line); }

    /* صفٌّ واحد متّصل لا بطاقات متباعدة: الفصل بـborder-spacing كان
       يترك فجوات تجعل الصف الواحد يبدو مقطّعًا عبر ثلاثة عشر عمودًا. */
    .sellers-table { width:100%; margin:0; border-collapse:collapse; }

    .sellers-table thead th {
        background:var(--sl-soft); color:var(--sl-navy);
        font-size:.76rem; font-weight:700; white-space:nowrap;
        padding:.8rem .7rem; border-bottom:1px solid var(--sl-line);
    }

    .sellers-table tbody td {
        padding:.85rem .7rem; vertical-align:middle;
        border-bottom:1px solid #f1f5f9; font-size:.85rem; color:var(--sl-ink);
    }
    .sellers-table tbody tr:hover { background:#fafcfe; }
    .sellers-table tbody tr:last-child td { border-bottom:0; }

    .sl-index { color:var(--sl-muted); font-size:.78rem; }

    /* الاسم والبريد في خلية واحدة: عمودان منفصلان لنفس الشخص يبدّدان
       العرض على جدول مزدحم أصلًا. */
    .sl-person strong { display:block; font-weight:700; line-height:1.3; }
    .sl-person small  { color:var(--sl-muted); font-size:.74rem; }

    .sl-code {
        display:inline-block; background:#eef3f9; color:var(--sl-navy);
        border-radius:6px; padding:.15rem .5rem;
        font-size:.75rem; font-weight:700; letter-spacing:.02em;
    }
    .sl-code--empty { background:transparent; color:#c3ccd8; font-weight:400; }

    .sl-num { font-variant-numeric:tabular-nums; white-space:nowrap; }
    .sl-zero { color:#c3ccd8; }

    /* المبلغ السالب أحمر: كان بلون النصّ نفسه فلا يُفرَّق دَينٌ عن رصيد
       إلا بقراءة الإشارة. */
    .sl-money { font-variant-numeric:tabular-nums; font-weight:700; white-space:nowrap; }
    .sl-money--neg { color:#c0392b; }
    .sl-money--pos { color:#1b7f5a; }
    .sl-money--nil { color:#9aa7b6; font-weight:400; }

    .sl-cell-money { display:flex; align-items:center; justify-content:center; gap:.45rem; }

    .sl-act {
        border:0; border-radius:7px; padding:.2rem .6rem;
        font-size:.72rem; font-weight:700; color:#fff; background:var(--sl-navy);
    }
    .sl-act:hover { background:#0c1a44; color:#fff; }

    .sl-tools { display:flex; gap:.3rem; }
    .sl-tools .btn {
        width:30px; height:30px; padding:0; display:inline-flex;
        align-items:center; justify-content:center;
        border:1px solid var(--sl-line); border-radius:8px;
        background:#fff; color:var(--sl-navy); font-size:.9rem;
    }
    .sl-tools .btn:hover { background:var(--sl-soft); color:var(--sl-navy); }

    @media (max-width: 991.98px) {
        .sellers-table thead th, .sellers-table tbody td { padding:.6rem .5rem; font-size:.8rem; }
    }
</style>

<div class="content container-fluid" dir="rtl">
    <!-- Page Header -->
    <div class="row align-items-center mb-3">
        <div class="col-sm mb-2 mb-sm-0">
            <h1 class="page-header-title d-flex align-items-center gap-2 text-capitalize">
                <i class="tio-filter-list"></i>
                {{ \App\CPU\translate('قائمة_المندوبين') }}
                <span class="badge">{{ $sellers->total() }}</span>
            </h1>
        </div>
    </div>
    <!-- End Page Header -->

    <div class="row gx-2 gx-lg-3">
        <div class="col-12 mb-3 mb-lg-2">
            <!-- Card -->
            <div class="card seller-card">
                <!-- Header -->
                <div class="card-header">
                    <!-- Search Form -->
                    <form action="{{ url()->current() }}" method="GET" class="flex-grow-1">
                        <div class="input-group" style="min-width: 300px;">
                            <span class="input-group-text"><i class="tio-search text-primary"></i></span>
                            <input
                                id="datatableSearch_"
                                type="search"
                                name="search"
                                class="form-control"
                                placeholder="{{ \App\CPU\translate('ابحث_بالاسم') }}"
                                value="{{ $search }}"
                                required
                            />
                            <button type="submit" class="btn btn-light">{{ \App\CPU\translate('بحث') }}</button>
                        </div>
                    </form>
                    <!-- Add New Button -->
                    <a href="{{ route('admin.seller.add') }}" class="btn btn-primary">
                        <i class="tio-add-circle me-1"></i>
                        {{ \App\CPU\translate('اضافة_مندوب_جديد') }}
                    </a>
                </div>
                <!-- End Header -->

                <!-- Table -->
                <div class="table-responsive datatable-custom p-3">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                {{-- الاسم والبريد في عمود واحد: عمودان لنفس
                                     الشخص يبدّدان العرض على جدول مزدحم. --}}
                                <th>المندوب</th>
                                <th>كود المندوب</th>
                                <th>كود العربة</th>
                                <th class="text-end">الراتب</th>
                                <th class="text-center">نسبة المبيعات</th>
                                <th class="text-center">زيارات الشهر</th>
                                <th class="text-center">المتوقعة</th>
                                <th class="text-center">التقييم</th>
                                <th class="text-center">دائن</th>
                                <th class="text-center">مدين</th>
                                <th class="text-center">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody id="set-rows">
                            @foreach($sellers as $key => $seller)
                                <tr>
                                    @php
                                        $vehicle = optional(\App\Models\Store::where('store_id', $seller->vehicle_code)->first())->store_code;
                                        $pct = fn ($v) => (float) $v > 0
                                            ? '<span class="sl-num">' . rtrim(rtrim(number_format((float) $v, 1), '0'), '.') . '%</span>'
                                            : '<span class="sl-num sl-zero">0%</span>';
                                        $cnt = fn ($v) => (float) $v > 0
                                            ? '<span class="sl-num">' . number_format((float) $v) . '</span>'
                                            : '<span class="sl-num sl-zero">0</span>';
                                    @endphp

                                    <td><span class="sl-index">{{ $key + 1 }}</span></td>

                                    <td class="sl-person">
                                        <strong>{{ trim($seller->f_name . ' ' . $seller->l_name) ?: '—' }}</strong>
                                        <small>{{ $seller->email }}</small>
                                    </td>

                                    <td>
                                        <span class="sl-code {{ $seller->mandob_code ? '' : 'sl-code--empty' }}">{{ $seller->mandob_code ?: '—' }}</span>
                                    </td>

                                    <td>
                                        <span class="sl-code {{ $vehicle ? '' : 'sl-code--empty' }}">{{ $vehicle ?: '—' }}</span>
                                    </td>

                                    <td class="text-end"><span class="sl-num">{{ number_format((float) $seller->salary) }}</span></td>
                                    <td class="text-center">{!! $pct($seller->precent_of_sales) !!}</td>
                                    <td class="text-center">{!! $cnt($seller->result_visitors) !!}</td>
                                    <td class="text-center">{!! $cnt($seller->visitors) !!}</td>
                                    <td class="text-center">{!! $pct($seller->score) !!}</td>
                                    @php
                                        // صفرٌ باهت وسالبٌ أحمر: كان الكل بلون
                                        // واحد فلا يُفرَّق دَينٌ عن رصيد إلا
                                        // بقراءة الإشارة في صفٍّ مزدحم.
                                        $money = function ($value) {
                                            $v = (float) $value;
                                            $class = $v < 0 ? 'sl-money--neg' : ($v > 0 ? 'sl-money--pos' : 'sl-money--nil');
                                            return '<span class="sl-money ' . $class . '">' . number_format($v, 2) . '</span>';
                                        };
                                    @endphp

                                    {{-- دائن --}}
                                    <td class="text-center">
                                        <div class="sl-cell-money">
                                            {!! $money($seller->balance) !!}
                                            <button class="sl-act"
                                                    onclick="update_seller_balance_cl({{ $seller->seller_id }})"
                                                    data-toggle="modal" data-target="#update-seller-balance">
                                                {{ \App\CPU\translate('استلام') }}
                                            </button>
                                        </div>
                                    </td>

                                    {{-- مدين --}}
                                    <td class="text-center">
                                        <div class="sl-cell-money">
                                            {!! $money($seller->credit) !!}
                                            <button class="sl-act"
                                                    onclick="update_seller_credit_cl({{ $seller->seller_id }})"
                                                    data-toggle="modal" data-target="#update-seller-credit">
                                                {{ \App\CPU\translate('تحصيل') }}
                                            </button>
                                        </div>
                                    </td>
                                    <!-- إجراءات -->
                                    <td class="text-center">
                                        <div class="sl-tools justify-content-center">
                                            <a href="{{ route('admin.seller.prices', [$seller->seller_id]) }}" class="btn" title="تسعير">
                                                <i class="tio-money"></i>
                                            </a>
                                            <a href="{{ route('admin.seller.edit', [$seller->seller_id]) }}" class="btn" title="تعديل">
                                                <i class="tio-edit"></i>
                                            </a>
                                        
                                            <a href="{{ route('admin.visitor.showResultVisitors', [$seller->seller_id]) }}" class="btn" title="عرض الزيارات">
                                                <i class="tio-visible"></i>
                                            </a>
                                        </div>
                                        <form id="seller-{{ $seller->seller_id }}" action="{{ route('admin.seller.delete', [$seller->seller_id]) }}" method="post" class="d-none">
                                            @csrf
                                            @method('delete')
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div class="mt-4">
                        {!! $sellers->links() !!}
                    </div>

                    <!-- No Data -->
                    @if($sellers->isEmpty())
                        <div class="text-center py-5">
                            <img src="{{ asset('public/assets/admin/svg/illustrations/sorry.svg') }}" alt="لا توجد بيانات" class="mb-4" style="width:100px;">
                            <p class="text-muted">{{ \App\CPU\translate('لا_توجد_بيانات_لعرضها') }}</p>
                        </div>
                    @endif
                </div>
                <!-- End Table -->
            </div>
            <!-- End Card -->
        </div>
    </div>
</div>
@endsection

@push('script_2')
    <script src={{asset("public/assets/admin/js/global.js")}}></script>
        <!-- jQuery -->

<!-- Bootstrap JS -->

    <script>     
    function update_seller_balance_cl(sellerId) {
    document.getElementById('seller_id').value = sellerId; // For balance modal
}

function update_seller_credit_cl(sellerId) {
    document.getElementById('seller_credit_id').value = sellerId; // For credit modal
}

    
    document.addEventListener('DOMContentLoaded', function () {
    const accountSelect = document.getElementById('account_id');
    const balanceDisplay = document.getElementById('account_balance');

    accountSelect.addEventListener('change', function () {
        const selectedOption = accountSelect.options[accountSelect.selectedIndex];
        const balance = selectedOption.getAttribute('data-balance');
        balanceDisplay.textContent = balance ? balance : '0';
    });

    // Initialize the balance display for the default selected option
    if (accountSelect.value) {
        const selectedOption = accountSelect.options[accountSelect.selectedIndex];
        const balance = selectedOption.getAttribute('data-balance');
        balanceDisplay.textContent = balance ? balance : '0';
    }
});

</script>

{{-- كان خارج أي قسم بعد @endsection، فيُطبع في جسم الصفحة
     الخام ويدفع المحتوى كله لأسفل. --}}
<div class="modal fade" id="update-seller-balance" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ \App\CPU\translate('اضافة استلام نقدية') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="{{ route('admin.seller.update-balance') }}" method="post" class="row" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="seller_id" name="seller_id">

                    <div class="form-group col-12 col-sm-6">
                        <label>{{ \App\CPU\translate('استلام نقدية') }}</label>
                        <input type="number" step="0.01" min="0" class="form-control" name="amount" required>
                    </div>

                    <div class="form-group col-12 col-sm-6">
                        <label>{{ \App\CPU\translate('الحساب الذي ستضاف له سيتم دفع منه المديونية') }}</label>
                        <select id="account_id" name="account_id" class="form-control js-select2-custom" required>
                            <option value="">---{{ \App\CPU\translate('اختار') }}---</option>
                            @foreach ($accounts as $account)
                                <option value="{{ $account['id'] }}" data-balance="{{ $account['balance'] }}">{{ $account['account'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group col-12 col-sm-6">
                        <label>{{ \App\CPU\translate('وصف') }}</label>
                        <input type="text" name="description" class="form-control" placeholder="{{ \App\CPU\translate('description') }}">
                    </div>

                    <div class="form-group col-12 col-sm-6">
                        <label>{{ \App\CPU\translate('التاريخ') }}</label>
                        <input type="date" name="date" class="form-control" required>
                    </div>
    <div class="form-group">
        <label class="input-label" for="img">{{ \App\CPU\translate('تحميل صورة') }}</label>
        <input type="file" name="img" id="img" class="form-control" required>
                   </div>
                    <div class="form-group col-12 col-sm-6">
                        <label>{{ \App\CPU\translate('رصيد الحساب') }}</label>
                        <p id="account_balance">0</p>
                    </div>

                    <div class="form-group col-sm-12">
                        <button class="btn btn-sm btn-primary" type="submit">{{ \App\CPU\translate('حفظ') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="update-seller-credit" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ \App\CPU\translate('اضافة دفع نقدية') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="{{ route('admin.seller.update-credit') }}" method="post" class="row" enctype="multipart/form-data">
                    @csrf

                    <div class="form-group col-12 col-sm-6">
                        <label>{{ \App\CPU\translate('دفع نقدية') }}</label>
                        <input type="number" step="0.01" min="0" class="form-control" name="amount" required>
                    </div>
                    
<input type="hidden" id="seller_credit_id" name="seller_id">

                    <div class="form-group col-12 col-sm-6">
                        <label>{{ \App\CPU\translate('الحساب الذي ستضاف له سيتم دفع اليه المبلغ ') }}</label>
                        <select id="account_id" name="account_id" class="form-control js-select2-custom" required>
                            <option value="">---{{ \App\CPU\translate('اختار') }}---</option>
                            @foreach ($accounts as $account)
                                <option value="{{ $account['id'] }}" data-balance="{{ $account['balance'] }}">{{ $account['account'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group col-12 col-sm-6">
                        <label>{{ \App\CPU\translate('وصف') }}</label>
                        <input type="text" name="description" class="form-control" placeholder="{{ \App\CPU\translate('description') }}">
                    </div>

                    <div class="form-group col-12 col-sm-6">
                        <label>{{ \App\CPU\translate('التاريخ') }}</label>
                        <input type="date" name="date" class="form-control" required>
                    </div>
    <div class="form-group">
        <label class="input-label" for="img">{{ \App\CPU\translate('تحميل صورة') }}</label>
        <input type="file" name="img" id="img" class="form-control" required>
                   </div>
                    <div class="form-group col-12 col-sm-6">
                        <label>{{ \App\CPU\translate('رصيد الحساب') }}</label>
                        <p id="account_balance">0</p>
                    </div>

                    <div class="form-group col-sm-12">
                        <button class="btn btn-sm btn-primary" type="submit">{{ \App\CPU\translate('حفظ') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endpush
