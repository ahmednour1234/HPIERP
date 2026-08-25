@extends('layouts.admin.app')

@section('title',\App\CPU\translate('seller_list'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/custom.css"/>
@endpush

@section('content')
<style>
    /* Page Header */
    .page-header-title i {
        color: #2596be;
    }
    .page-header-title .badge {
        background-color: #bee0ec;
        color: #333;
        font-weight: 600;
    }

    /* Seller Card */
    .card.seller-card {
        border-radius: 1rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        overflow: hidden;
        margin-bottom: 1.5rem;
    }
    .card.seller-card .card-header {
        background: #bee0ec;
        color: #333;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
        padding: 1rem 1.5rem;
        border-bottom: none;
        border-top-left-radius: 1rem;
        border-top-right-radius: 1rem;
    }
    .card.seller-card .card-header .input-group .form-control {
        border-radius: 0.75rem 0 0 0.75rem;
    }
    .card.seller-card .card-header .input-group .btn {
        border-radius: 0 0.75rem 0.75rem 0;
    }

    /* Table Rows */
    .datatable-custom table {
        border-collapse: separate;
        border-spacing: 0 0.5rem;
    }
    .datatable-custom tbody tr {
        background: #fff;
        border-radius: 0.75rem;
        box-shadow: 0 2px 6px rgba(0,0,0,0.03);
    }
    .datatable-custom thead {
        background: #bee0ec;
    }
    .datatable-custom thead th {
        color: #333;
        font-weight: 600;
        border: none;
    }
    .datatable-custom tbody td {
        vertical-align: middle;
        border: none;
    }

    /* Action Buttons */
    .datatable-custom .btn-white {
        background: #fff;
        border-radius: 0.75rem;
        box-shadow: 0 1px 4px rgba(0,0,0,0.1);
        margin-right: 0.25rem;
        color: #2596be;
    }
    .datatable-custom .btn-white:hover {
        background: #f1f5f9;
    }
    /* Ensure action buttons inline */
    .action-group {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
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
                                <th>الاسم</th>
                                <th>الايميل</th>
                                <th>كود المندوب</th>
                                <th>كود العربة</th>
                                <th>الراتب</th>
                                <th>نسبة المبيعات</th>
                                <th>زيارات الشهر</th>
                                <th>الزيارات المتوقعة</th>
                                <th>التقييم</th>
                                <th class="text-center">دائن</th>
                                <th class="text-center">مدين</th>
                                <th>إجراءات</th>
                            </tr>
                        </thead>
                        <tbody id="set-rows">
                            @foreach($sellers as $key => $seller)
                                <tr>
                                    <td>{{ $key + 1 }}</td>
                                    <td>{{ $seller->f_name . ' ' . $seller->l_name }}</td>
                                    <td>{{ $seller->email }}</td>
                                    <td>{{ $seller->mandob_code }}</td>
                                    <td>{{ optional(\App\Models\Store::where('store_id', $seller->vehicle_code)->first())->store_code }}</td>
                                    <td>{{ $seller->salary }}</td>
                                    <td>{{ $seller->precent_of_sales }}%</td>
                                    <td>{{ $seller->result_visitors }}</td>
                                    <td>{{ $seller->visitors }}</td>
                                    <td>{{ $seller->score }}%</td>
                                    <!-- دائن -->
                                    <td class="text-center">
                                        @if($seller->id)
                                            <div class="d-flex align-items-center justify-content-center gap-2">
                                                <span>{{ $seller->balance . ' ' . \App\CPU\Helpers::currency_symbol() }}</span>
                                                <button
                                                    class="btn btn-info badge"
                                                    onclick="update_seller_balance_cl({{ $seller->seller_id }})"
                                                    data-toggle="modal"
                                                    data-target="#update-seller-balance"
                                                >
                                                    {{ \App\CPU\translate('استلام') }}
                                                </button>
                                            </div>
                                        @else
                                            <span>لا توجد بيانات دائن</span>
                                        @endif
                                    </td>
                                    <!-- مدين -->
                                    <td class="text-center">
                                        @if($seller->id)
                                            <div class="d-flex align-items-center justify-content-center gap-2">
                                                <span>{{ $seller->credit . ' ' . \App\CPU\Helpers::currency_symbol() }}</span>
                                                <button
                                                    class="btn btn-info badge"
                                                    onclick="update_seller_credit_cl({{ $seller->seller_id }})"
                                                    data-toggle="modal"
                                                    data-target="#update-seller-credit"
                                                >
                                                    {{ \App\CPU\translate('تحصيل') }}
                                                </button>
                                            </div>
                                        @else
                                            <span>لا توجد بيانات مدين</span>
                                        @endif
                                    </td>
                                    <!-- إجراءات -->
                                    <td>
                                        <div class="action-group">
                                            <a href="{{ route('admin.seller.prices', [$seller->seller_id]) }}" class="btn btn-white" title="تسعير">
                                                <i class="tio-money"></i>
                                            </a>
                                            <a href="{{ route('admin.seller.edit', [$seller->seller_id]) }}" class="btn btn-white" title="تعديل">
                                                <i class="tio-edit"></i>
                                            </a>
                                        
                                            <a href="{{ route('admin.visitor.showResultVisitors', [$seller->seller_id]) }}" class="btn btn-white" title="عرض الزيارات">
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
@endpush
