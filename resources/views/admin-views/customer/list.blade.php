@extends('layouts.admin.app')

@section('title',\App\CPU\translate('customer_list'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/custom.css"/>
@endpush

@section('content')
<style>
    body {
        direction: rtl;
        background: #f4f6f8;
    }
    .page-header {
        position: relative;
        background: #fff;
        padding: 2rem;
        border-radius: 8px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        margin-bottom: 30px;
        overflow: hidden;
    }
    .page-header::before {
        content: '';
        position: absolute;
        bottom: -20px;
        left: 0;
        right: 0;
        height: 20px;
        background: #3a5fa8;
        clip-path: ellipse(50% 100% at 50% 0%);
    }
    .page-header h1 {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin: 0;
        color: #001B63;
        z-index: 1;
    }
    .page-header h1 i {
        background: #3a5fa8;
        color: #fff;
        padding: 0.5rem;
        font-size: 1.5rem;
        border-radius: 50%;
    }
    .page-header .badge-soft-dark {
        background: #ffce00;
        color: #001B63;
        font-weight: 600;
        margin-left: 0.5rem;
        padding: 0.4rem 0.75rem;
        border-radius: 12px;
    }
    .add-customer-btn {
        background: #3a5fa8;
        color: #fff;
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 4px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        transition: background 0.3s;
        z-index: 1;
    }
    .add-customer-btn:hover {
        background: #001B63;
    }

    /* باقي التنسيقات بدون تغيير */
    .filter-section {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        margin-bottom: 30px;
    }
    .filter-section form {
        flex: 1;
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        align-items: flex-end;
    }
    .filter-section .form-label {
        font-weight: 600;
        color: #34495e;
    }
    .filter-section .form-control,
    .filter-section .form-select {
        border-radius: 4px;
    }
    .filter-section .btn {
        min-width: 120px;
    }
    .export-form {
        align-self: flex-end;
    }
    .card-table {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
    .card-table thead th {
        background: #001B63;
        color: #fff;
        font-weight: 600;
        text-align: center;
    }
    .card-table tbody tr:hover {
        background: rgba(58,95,168,0.1);
    }
    .toggle-switch {
        cursor: pointer;
    }
    .badge {
        font-size: 0.9rem;
    }
     .filter-card {
        border-radius: 0.5rem;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        background: #fff;
        margin-bottom: 1.5rem;
    }
    .filter-card .card-header {
        background: #f8f9fa;
        border-bottom: none;
    }
    .filter-card .form-label {
        font-weight: 600;
        color: #495057;
    }
    .filter-card .input-group-text {
        background: #fff;
        border-right: none;
    }
    .filter-card .form-control,
    .filter-card .form-select {
        border-left: none;
        min-height: 48px;
    }
    .filter-card .btn {
        min-height: 48px;
    }
      .filter-card {
        border-radius: 1rem;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        background: #ffffff;
        margin-bottom: 1.5rem;
    }
    .filter-card .card-header {
        background: #f1f5f9;
        border: none;
        border-top-left-radius: 1rem;
        border-top-right-radius: 1rem;
        padding: 1rem 1.5rem;
    }
    .filter-card .form-label {
        font-weight: 600;
        color: #333;
    }
    .filter-card .input-group-text {
        background: #ffffff;
        border-right: none;
    }
    .filter-card .form-control,
    .filter-card .form-select {
        border-radius: 0.75rem;
        min-height: 56px;
        padding: 0.5rem 1rem;
        font-size: 0.95rem;
    }
    .filter-card .btn {
        border-radius: 0.75rem;
        min-height: 56px;
        font-size: 0.95rem;
        font-weight: 600;
    }
    .filter-card hr {
        border-top: 2px solid #e2e8f0;
        margin: 1.5rem 0;
    }
</style>

<div class="content container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <h1 class="d-flex align-items-center">
            <i class="tio-filter-list"></i> {{ \App\CPU\translate('قائمة العملاء') }}
            <span class="badge badge-soft-dark">{{ $customers->total() }}</span>
        </h1>
        <a href="{{ route('admin.customer.add') }}" class="add-customer-btn">
            <i class="tio-add-circle"></i> {{ \App\CPU\translate('اضافة عميل جديد') }}
        </a>
    </div>
    <!-- End Page Header -->

    <!-- Filters -->
<div class="card filter-card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0 text-primary">
            <i class="tio-filter-list me-2"></i>
            {{ \App\CPU\translate('فلتر العملاء') }}
        </h5>
    </div>
    <div class="card-body">
        <form action="{{ url()->current() }}" method="GET">
            <div class="row g-3 align-items-end">
                <!-- Search -->
                <div class="col-md-4">
                    <label for="datatableSearch_" class="form-label">
                        {{ \App\CPU\translate('بحث') }}
                    </label>
                    <div class="input-group">
                        <span class="input-group-text border-end-0">
                            <i class="tio-search text-primary"></i>
                        </span>
                        <input
                            id="datatableSearch_"
                            type="search"
                            name="search"
                            class="form-control border-start-0"
                            placeholder="{{ \App\CPU\translate('بحث باسم أو كود العميل') }}"
                            value="{{ $search }}"
                        />
                    </div>
                </div>

                <!-- Seller Dropdown -->
                <div class="col-md-3">
                    <label for="seller" class="form-label">
                        {{ \App\CPU\translate('اختار البائع') }}
                    </label>
                    <select name="seller_id" id="seller" class="form-select">
                        <option value="">{{ \App\CPU\translate('اختر بائع') }}</option>
                        @foreach($sellers as $sellerOption)
                            <option
                                value="{{ $sellerOption->id }}"
                                {{ request('seller_id') == $sellerOption->id ? 'selected' : '' }}
                            >
                                {{ $sellerOption->email }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- الفئة (كانت تسمى التخصص) -->
                <div class="col-md-3">
                    <label for="specialist" class="form-label">
                        {{ \App\CPU\translate('الفئة') }}
                    </label>
                    <select name="specialist" id="specialist" class="form-select">
                        <option value="">{{ \App\CPU\translate('كل الفئات') }}</option>
                        <option value="1" {{ request('specialist') == 1 ? 'selected' : '' }}>
                            {{ \App\CPU\translate('صيدلية') }}
                        </option>
                        <option value="2" {{ request('specialist') == 2 ? 'selected' : '' }}>
                            {{ \App\CPU\translate('مركز طبي') }}
                        </option>
                        <option value="3" {{ request('specialist') == 3 ? 'selected' : '' }}>
                            {{ \App\CPU\translate('مستشفى') }}
                        </option>
                        {{-- كان هذا الخيار بلا فحص selected، فيضيع عند إعادة العرض --}}
                        <option value="4" {{ request('specialist') == 4 ? 'selected' : '' }}>
                            {{ \App\CPU\translate('طبيب') }}
                        </option>
                    </select>
                </div>

                <!-- التخصص: أطفال / نسا وتوليد / ... وهو category_id من نوع 0 -->
                <div class="col-md-3">
                    <label for="category_id" class="form-label">
                        {{ \App\CPU\translate('التخصص') }}
                    </label>
                    <select name="category_id" id="category_id" class="form-select">
                        <option value="">{{ \App\CPU\translate('كل التخصصات') }}</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}"
                                {{ (string) request('category_id') === (string) $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- المنطقة: يمكن تحديد أكثر من منطقة معًا -->
                <div class="col-md-3">
                    <label for="region_id" class="form-label">
                        {{ \App\CPU\translate('المنطقة (يمكن اختيار أكثر من منطقة)') }}
                    </label>
                    <select name="region_id[]" id="region_id" class="form-select" multiple size="4">
                        @foreach($regions as $region)
                            <option value="{{ $region->id }}"
                                {{ in_array((string) $region->id, $regionIds, true) ? 'selected' : '' }}>
                                {{ $region->name }}
                            </option>
                        @endforeach
                    </select>
                </div>


                <!-- Search Button -->
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="tio-search me-1"></i>
                        {{ \App\CPU\translate('بحث') }}
                    </button>
                </div>
            </div>
        </form>

        <hr>

        <div class="d-flex gap-3 justify-content-end">
            <!-- Export Button -->
            {{-- التصدير يحمل فلاتر الشاشة الحالية، وإلا صدّر كل العملاء --}}
            <form action="{{ route('admin.customer.export') }}" method="GET" class="d-inline-block">
                @foreach(request()->except('page') as $qkey => $qvalue)
                    @if(is_array($qvalue))
                        @foreach($qvalue as $qitem)
                            <input type="hidden" name="{{ $qkey }}[]" value="{{ $qitem }}">
                        @endforeach
                    @else
                        <input type="hidden" name="{{ $qkey }}" value="{{ $qvalue }}">
                    @endif
                @endforeach
                <button type="submit" class="btn btn-success">
                    <i class="tio-download-to me-1"></i>
                    {{ \App\CPU\translate('اصدار في اكسل') }}
                </button>
            </form>

            <!-- Print Button -->
            <button
                type="button"
                class="btn btn-outline-secondary"
                onclick="printTable()"
            >
                <i class="tio-print me-1"></i>
                {{ \App\CPU\translate('طباعة') }}
            </button>
        </div>
    </div>
</div>
    <!-- Table -->
    <div class="table-responsive card-table" id="product-table">
        <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle">
            <thead>
                <tr>
                    <th>{{ \App\CPU\translate('#') }}</th>
                    <th>{{ \App\CPU\translate('الاسم') }}</th>
                    <th>{{ \App\CPU\translate('رقم الهاتف') }}</th>
                    <th>{{ \App\CPU\translate('المنطقة التابع لها') }}</th>
                    <th>{{ \App\CPU\translate('النوع') }}</th>
                    <th>{{ \App\CPU\translate('عدد الطلبات') }}</th>
                    <th class="text-center">{{ \App\CPU\translate('دائن') }}</th>
                    <th class="text-center">{{ \App\CPU\translate('مدين') }}</th>
                    <th class="none">{{ \App\CPU\translate('التفعيل') }}</th>
                    <th class="none">{{ \App\CPU\translate('الاجراءات') }}</th>
                </tr>
            </thead>
            <tbody id="set-rows">
                @foreach($customers as $key => $customer)
                    <tr>
                        <td>{{ $customers->firstItem() + $key  }}</td>
                        <td>
                            <a class="text-primary" href="{{ route('admin.customer.view', [$customer['id']]) }}">
                                {{ $customer->name }}
                            </a>
                        </td>
                        <td>{{ $customer->mobile }}</td>
                        <td>{{ $customer->regions->name??'' }}</td>
                        <td>
                            @if ($customer->specialist == 1)
                                {{ \App\CPU\translate('صيدلية') }}
                            @elseif ($customer->specialist == 2)
                                {{ \App\CPU\translate('مركز طبي') }}
                            @elseif ($customer->specialist == 3)
                                {{ \App\CPU\translate('مستشفي') }}
                            @else
                                {{ \App\CPU\translate('طبيب') }}
                            @endif
                        </td>
                        <td>{{ $customer->orders->count() }}</td>
                        <td class="text-center p-5">
                            @if ($customer->id)
                                <div class="d-flex justify-content-between align-items-center none">
                                    <span>{{ $customer->balance . ' ' . \App\CPU\Helpers::currency_symbol() }}</span>
                                    <button type="button" class="btn btn-info btn-sm" onclick="update_customer_balance_cl({{ $customer->id }})" data-toggle="modal" data-target="#update-customer-balance">
                                        <i class="tio-add-circle"></i>
                                    </button>
                                </div>
                            @else
                                <span>{{ \App\CPU\translate('هذا العميل غير مدائن لنا بشئ') }}</span>
                            @endif
                        </td>
                        <td class="text-center p-5">
                            @if ($customer->id)
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>{{ $customer->credit . ' ' . \App\CPU\Helpers::currency_symbol() }}</span>
                                    <button type="button" class="btn btn-info btn-sm none" onclick="update_customer_credit_cl({{ $customer->id }})" data-toggle="modal" data-target="#update-customer-credit">
                                        <i class="tio-add-circle"></i>
                                    </button>
                                </div>
                            @else
                                <span>{{ \App\CPU\translate('هذا العميل غير مدين لنا بشئ') }}</span>
                            @endif
                        </td>
                        <td class="none">
                            <label class="toggle-switch toggle-switch-sm">
                                <input type="checkbox" class="toggle-switch-input" onclick="location.href='{{ route('admin.customer.status', [$customer['id'], $customer->active ? 1 : 0]) }}'" {{ $customer->active ? 'checked' : '' }}>
                                <span class="toggle-switch-label">
                                    <span class="toggle-switch-indicator"></span>
                                </span>
                            </label>
                        </td>
                        <td class="none">
                            <div class="btn-group" role="group">
                                @if ($customer->id)
                                    <a class="btn btn-white btn-sm" href="{{ route('admin.customer.prices', [$customer['id']]) }}"><i class="tio-money"></i></a>
                                    <a class="btn btn-white btn-sm" href="{{ route('admin.customer.view', [$customer['id']]) }}"><i class="tio-visible"></i></a>
                                    <a class="btn btn-white btn-sm" href="{{ route('admin.customer.edit', [$customer['id']]) }}"><i class="tio-edit"></i></a>
                                    <!--<button type="button" class="btn btn-white btn-sm" onclick="form_alert('customer-{{$customer['id']}}','{{ \App\CPU\translate('هل تود حذف هذا العميل؟') }}')"><i class="tio-delete"></i></button>-->
                                    <form id="customer-{{$customer['id']}}" action="{{ route('admin.customer.delete', [$customer['id']]) }}" method="post">@csrf @method('delete')</form>
                                @else
                                    <a class="btn btn-white btn-sm" href="{{ route('admin.customer.view', [$customer['id']]) }}"><i class="tio-visible"></i></a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-light">
                <tr class="font-weight-bold">
                    <th colspan="4" class="text-end">{{ \App\CPU\translate('تجميع') }}</th>
                    <th class="text-center table-active">
                        @php $totalBalance = $customers->sum('balance'); @endphp
                        {{ \App\CPU\translate('مدين') }}: {{ $totalBalance }}
                    </th>
                    <th class="text-center table-active">-</th>
                    <th class="text-center table-active">
                        @php $totalCredit = $customers->sum('credit'); @endphp
                        {{ \App\CPU\translate('دائن') }}: {{ $totalCredit }}
                    </th>
                    <th colspan="2"></th>
                </tr>
                <tr>
                    <th colspan="4" class="text-end">{{ \App\CPU\translate('الصافي') }}</th>
                    <th class="text-center">
                        @php $netAmount = $totalBalance - $totalCredit; @endphp
                        {{ $netAmount }} {{ $netAmount >= 0 ? \App\CPU\translate('مدين') : \App\CPU\translate('دائن') }}
                    </th>
                    <th colspan="3"></th>
                </tr>
            </tfoot>
        </table>
    </div>
    <!-- End Table -->

    <!-- Pagination -->
    <div class="mt-4">
        {!! $customers->withQueryString()->links() !!}
    </div>

    <!-- Empty State -->
    @if(count($customers) == 0)
        <div class="text-center py-5">
            <img class="mb-3" src="{{ asset('public/assets/admin/svg/illustrations/sorry.svg') }}" alt="{{ \App\CPU\translate('لا توجد بيانات') }}" style="width:150px;">
            <p class="text-muted">{{ \App\CPU\translate('لاتوجد بيانات لعرضها') }}</p>
        </div>
    @endif
</div><div class="modal fade" id="update-customer-balance" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ \App\CPU\translate('اضافة استلام نقدية') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="{{ route('admin.customer.update-balance') }}" method="post" class="row" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="customer_id" name="customer_id">

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
<div class="modal fade" id="update-customer-credit" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ \App\CPU\translate('اضافة دفع نقدية') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="{{ route('admin.customer.update-credit') }}" method="post" class="row" enctype="multipart/form-data">
                    @csrf

                    <div class="form-group col-12 col-sm-6">
                        <label>{{ \App\CPU\translate('دفع نقدية') }}</label>
                        <input type="number" step="0.01" min="0" class="form-control" name="amount" required>
                    </div>
                    
<input type="hidden" id="customer_credit_id" name="customer_id">

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
                        <label>{{ \App\CPU\translate('رقم الفاتورة') }}</label>
                        <input type="order_id" name="order_id" class="form-control" required>
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

@endsection
<script>
    function printTable() {
        const tableContent = document.getElementById('product-table').innerHTML;


        const printWindow = window.open('', '_blank', 'width=800,height=600');
        printWindow.document.write(`
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>{{ \App\CPU\translate('تقرير المرتجعات') }}</title>
                <style>
                
                    body {
                        font-family: Arial, sans-serif;
                        margin: 20px;
                    }
                     body {
                            font-family: 'Cairo', Arial, sans-serif;
                            margin: 0;
                            background-color: #f9f9f9;
                            color: #333;
                            direction: rtl;
                        }

                        h1 {
                            text-align: center;
                            color: #003366;
                            font-weight: bold;
                            font-size: 28px;
                            margin-bottom: 20px;
                        }

                        .header-section {
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                            border-bottom: 2px solid #003366;
                            padding: 10px 0;
                            margin-bottom: 30px;
                            flex-wrap: wrap;
                        }

                        .header-section .left,
                        .header-section .right,
                        .header-section .logo {
                            width: 32%;
                            text-align: center;
                        }

                        .header-section p {
                            margin: 5px 0;
                            line-height: 1.6;
                            font-size: 16px;
                        }

                        .logo img {
                            max-width: 150px;
                            height: auto;
                        }
                    table {
                        width: 100%;
                        border-collapse: collapse;
                        margin-bottom: 20px;
                    }
                    table th, table td {
                        border: 1px solid #ddd;
                        padding: 8px;
                        text-align: left;
                    }
                    table th {
                        background-color: #f2f2f2;
                        font-weight: bold;
                    }
                    .row {
                        display: flex;
                        flex-wrap: wrap;
                        margin-bottom: 10px;
                    }
                    .col-md-3 {
                        flex: 0 0 25%;
                        max-width: 25%;
                        padding: 5px;
                        box-sizing: border-box;
                    }
                    .none{
                        display:none;
                    }
                    strong {
                        font-weight: bold;
                    }
                    input[type="search"][aria-controls="DataTables_Table_0"] {
    display: none;
}
label:has(input[type="search"][aria-controls="DataTables_Table_0"]) {
    display: none;
}
label:has(input[type="search"][aria-controls="DataTables_Table_1"]) {
    display: none;
}
label:has(input[type="search"][aria-controls="DataTables_Table_2"]) {
    display: none;
}
label:has(input[type="search"][aria-controls="DataTables_Table_3"]) {
    display: none;
}
label:has(input[type="search"][aria-controls="DataTables_Table_4"]) {
    display: none;
}
label:has(input[type="search"][aria-controls="DataTables_Table_5"]) {
    display: none;
}
#DataTables_Table_0_info{
        display: none;

}
#DataTables_Table_1_info{
            display: none;

}
#DataTables_Table_2_info{
            display: none;

}
#DataTables_Table_3_info{
            display: none;

}
#DataTables_Table_4_info{
            display: none;

}
#DataTables_Table_5_info{
            display: none;

}
#links{
    display: block;
}
                </style>
            </head>
            <body>
            <div class="header-section">
                        <div class="left">
                            <p><strong>رقم السجل التجاري:</strong> {{ optional(\App\Models\BusinessSetting::where(["key" => "vat_reg_no"])->first())->value??'' }}</p>
                            <p><strong>الرقم الضريبي:</strong> {{ optional(\App\Models\BusinessSetting::where(["key" => "number_tax"])->first())->value ??''}}</p>
                            <p><strong>البريد الإلكتروني:</strong> {{ optional(\App\Models\BusinessSetting::where(["key" => "shop_email"])->first())->value }}</p>
                        </div>
                        <div class="logo">
                            <img src="{{ asset('storage/shop/' . optional(\App\Models\BusinessSetting::where(['key' => 'shop_logo'])->first())->value) }}" alt="شعار المتجر">
                        </div>
                        <div class="right">
                            <p><strong>اسم المؤسسة:</strong> {{ optional(\App\Models\BusinessSetting::where(["key" => "shop_name"])->first())->value }}</p>
                            <p><strong>العنوان:</strong> {{ optional(\App\Models\BusinessSetting::where(["key" => "shop_address"])->first())->value }}</p>
                            <p><strong>رقم الجوال:</strong> {{ optional(\App\Models\BusinessSetting::where(["key" => "shop_phone"])->first())->value }}</p>
                        </div>
                    </div>
                    
                <h2>{{ \App\CPU\translate('تقرير   العملاء') }}</h2>
                
                ${tableContent}
                <hr>
                <script>
                    window.onload = function() {
                        window.print();
                        window.close();
                    };
                <\/script>
            </body>
            </html>
        `);

        printWindow.document.close();
    }
</script>

@push('script_2')
    <script src={{asset("public/assets/admin/js/global.js")}}>
    </script>
    <!-- jQuery -->

<!-- Bootstrap JS -->
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>     
    function update_customer_balance_cl(customerId) {
    document.getElementById('customer_id').value = customerId; // For balance modal
}

function update_customer_credit_cl(customerId) {
    document.getElementById('customer_credit_id').value = customerId; // For credit modal
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

