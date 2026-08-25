@extends('layouts.admin.app')
@section('title','Order List')
@push('css_or_js')
    {{-- <meta name="csrf-token" content="{{ csrf_token() }}"> --}}
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/custom.css"/>
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="">
            <div class="row align-items-center mb-3">
                <div class="col-sm">
                    <h1 class="page-header-title text-capitalize">{{\App\CPU\translate('pos')}} {{\App\CPU\translate('عينات')}}
                        <span
                            class="badge badge-soft-dark ml-2">{{$orders->total()}}</span></h1>
                </div>
            </div>
        </div>
        <!-- End Page Header -->
        <!-- Card -->
        <div class="card">
            <!-- Header -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 px-4">
        <h5 class="mb-0 text-primary fw-bold">
            <i class="tio-search me-2"></i> {{ \App\CPU\translate('بحث وتصفية') }}
        </h5>
    </div>
    <div class="card-body bg-light p-4">
        <form action="{{ url()->current() }}" method="GET" class="row g-3">
            <!-- Search Input -->
            <div class="col-md-4">
                <label class="form-label text-secondary fw-semibold">{{ \App\CPU\translate('بحث') }}</label>
                <div class="input-group shadow-sm">
                    <span class="input-group-text bg-white border-0">
                        <i class="tio-search text-muted"></i>
                    </span>
                    <input type="search" name="search" class="form-control border-0" placeholder="{{ \App\CPU\translate('رقم الفاتورة، اسم العميل أو البائع') }}" value="{{ $search }}">
                </div>
            </div>

            <!-- Region Select -->
            <div class="col-md-4">
                <label class="form-label text-secondary fw-semibold">{{ \App\CPU\translate('المنطقة') }}</label>
                <div class="input-group shadow-sm">
                    <span class="input-group-text bg-white border-0">
                        <i class="tio-map-making text-muted"></i>
                    </span>
                    <select name="region_id" class="form-select border-0">
                        <option value="">{{ \App\CPU\translate('اختر المنطقة') }}</option>
                        @foreach($regions as $region)
                            <option value="{{ $region->id }}" @selected($regionId == $region->id)>{{ $region->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Date Range From -->
            <div class="col-md-2">
                <label class="form-label text-secondary fw-semibold">{{ \App\CPU\translate('من تاريخ') }}</label>
                <input type="date" name="from_date" class="form-control shadow-sm" value="{{ $fromDate }}">
            </div>

            <!-- Date Range To -->
            <div class="col-md-2">
                <label class="form-label text-secondary fw-semibold">{{ \App\CPU\translate('إلى تاريخ') }}</label>
                <input type="date" name="to_date" class="form-control shadow-sm" value="{{ $toDate }}">
            </div>

            <!-- Action Buttons -->
            <div class="col-md-12 text-end">
                <button type="submit" class="btn btn-primary me-2 px-4 py-2 shadow-sm">
                    <i class="tio-filter_list me-1"></i> {{ \App\CPU\translate('تطبيق') }}
                </button>
                <button type="button" class="btn btn-outline-secondary px-4 py-2 shadow-sm" onclick="printTable()">
                    <i class="tio-print me-1"></i> {{ \App\CPU\translate('طباعة') }}
                </button>
            </div>
        </form>
    </div>
</div>
<div class="card-body"  id="product-table">
    <!-- Total Sales -->
    <div class="row mb-3">
        <div class="col-md-3">
            <strong>{{ \App\CPU\translate('إجمالي العينات') }}:</strong> {{ number_format($orderAmountSum, 2) }}
        </div>
        <div class="col-md-3">
            <strong>{{ \App\CPU\translate('إجمالي المبالغ المحصلة') }}:</strong> 0.0
        </div>
        <div class="col-md-3">  
            <strong>{{ \App\CPU\translate('إجمالي عدد المنتجات عينات') }}:</strong> {{ $productCount }}
        </div>
        <div class="col-md-3">
            <strong>{{ \App\CPU\translate('إجمالي كميات المنتجات عينات') }}:</strong> {{ $quantitySum }}
        </div>
    </div>            <!-- End Header -->

            <!-- Table -->
            <div class="table-responsive ">
                <table
                    class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                >
                    <thead class="thead-light">
                    <tr>
                        <th class="">
                            {{\App\CPU\translate('#')}}
                        </th>
                        <th class="table-column-pl-0">{{\App\CPU\translate('عينات')}}</th>
                        <th>{{\App\CPU\translate('اسم البائع')}}</th>
                        <th>{{\App\CPU\translate('اسم العميل')}}</th>
                                                <th>{{\App\CPU\translate('المنطقة')}}</th>
                        <th>{{\App\CPU\translate('تاريخ')}}</th>
                    <th>{{\App\CPU\translate(' نوع')}}</th>
                                        <th>{{\App\CPU\translate('طريقة الدفع')}}</th>
                        <th>{{\App\CPU\translate('اسم الحساب')}}</th>
                        <th>{{\App\CPU\translate('اجمالي الفاتورة')}}</th>
                                                <th>{{\App\CPU\translate('خصم اضافي')}}</th>
                        <th>{{\App\CPU\translate('ضريبة')}}</th>
                        <th>{{\App\CPU\translate('المبلغ المدفوع')}}</th>
                        <th class="none">{{\App\CPU\translate('صورة الفاتورة')}}</th>
                        <th class="none">{{\App\CPU\translate('روؤية الفاتورة')}}</th>
                    </tr>
                    </thead>

                    <tbody id="set-rows">
                    @foreach($orders as $key=>$order)
                        <tr class="status-{{$order['order_status']}} class-all">
                            <td class="">
                                {{$key+$orders->firstItem()}}
                            </td>
                            <td class="table-column-pl-0">
                                <a class="text-primary" href="#" onclick="print_invoice('{{$order->id}}')">{{$order['id']}}</a>
                            </td>
                            <td>
{{$order->seller->email ?? ''}}                            
</td>
                            <td>
                                  {{ optional($order->customer)->name }}
                            </td>
                                    <td>
                                  {{ optional($order->customer->regions)->name }}
                            </td>
                            <td>{{date('d M Y',strtotime($order['created_at']))}}</td>
                            <td>عينة</td>
                            <td>{{ $order['cash'] == 2 ? 'قسط' : 'كاش' }}</td>
                            <td>
                                {{ ($order->payment_id != 0) ? ($order->account ? $order->account->account : \App\CPU\translate('account_deleted')): 'Customer balance' }}
                            </td>
                            <td>
                                {{ number_format($order->order_amount, 2) }}
                            </td>
                        <td>{{ $order->extra_discount ? number_format($order->extra_discount, 2) : 0  }}</td>
                            <td>{{ number_format($order['total_tax'], 2) }}</td>
                            <td>0.0</td>
                            <td class="none">
    <img src="{{ asset('storage/app/public/'.$order['img']) }}" alt="Image Description" style="width: 50px; height: auto;">
</td>
                            <td class="none">
                                <button class="btn btn-sm btn-white" target="_blank" type="button"
                                        onclick="print_invoice('{{$order->id}}')"><i
                                        class="tio-download"></i> {{\App\CPU\translate('الفاتورة')}}</button>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <!-- End Table -->

            <!-- Footer -->
            <div class="card-footer none">
                <!-- Pagination -->
                <div class="row justify-content-center justify-content-sm-between align-items-sm-center">
                    <div class="col-sm-auto">
                        <div class="d-flex justify-content-center justify-content-sm-end">
                            <!-- Pagination -->
                            {!! $orders->withQueryString()->links() !!}
                        </div>
                    </div>
                </div>
                <!-- End Pagination -->
            </div>
            @if(count($orders)==0)
                <div class="text-center p-4">
                    <img class="mb-3 img-one-ol" src="{{asset('public/assets/admin')}}/svg/illustrations/sorry.svg"
                         alt="Image Description">
                    <p class="mb-0">{{ \App\CPU\translate('لاتوجد بيانات لعرضها')}}</p>
                </div>
        @endif
        <!-- End Footer -->
        </div>
        <!-- End Card -->
    </div>

    <div class="modal fade" id="print-invoice" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content modal-content1">
                <div class="modal-header">
                    <h5 class="modal-title">{{\App\CPU\translate('print')}} {{\App\CPU\translate('invoice')}}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span class="text-dark" aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body row">
                    <div class="col-md-12">
                        <center>
                            <input type="button" class="mt-2 btn btn-primary non-printable"
                                   onclick="printDiv('printableArea')"
                                   value="{{\App\CPU\translate('Proceed, If thermal printer is ready')}}."/>
                            <a href="{{url()->previous()}}"
                               class="mt-2 btn btn-danger non-printable">{{\App\CPU\translate('Back')}}</a>
                        </center>
                        <hr class="non-printable">
                    </div>
                    <div class="row m-auto" id="printableArea">

                    </div>
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
                <title>{{ \App\CPU\translate('تقرير العينات') }}</title>
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
                            <p><strong>رقم السجل التجاري:</strong> {{ \App\Models\BusinessSetting::where(["key" => "vat_reg_no"])->first()->value??'' }}</p>
                            <p><strong>الرقم الضريبي:</strong> {{ \App\Models\BusinessSetting::where(["key" => "number_tax"])->first()->value ??''}}</p>
                            <p><strong>البريد الإلكتروني:</strong> {{ \App\Models\BusinessSetting::where(["key" => "shop_email"])->first()->value }}</p>
                        </div>
                        <div class="logo">
                            <img src="{{ asset('storage/app/public/shop/' . \App\Models\BusinessSetting::where(['key' => 'shop_logo'])->first()->value) }}" alt="شعار المتجر">
                        </div>
                        <div class="right">
                            <p><strong>اسم المؤسسة:</strong> {{ \App\Models\BusinessSetting::where(["key" => "shop_name"])->first()->value }}</p>
                            <p><strong>العنوان:</strong> {{ \App\Models\BusinessSetting::where(["key" => "shop_address"])->first()->value }}</p>
                            <p><strong>رقم الجوال:</strong> {{ \App\Models\BusinessSetting::where(["key" => "shop_phone"])->first()->value }}</p>
                        </div>
                    </div>
                    
                <h2>{{ \App\CPU\translate('تقرير   العينات') }}</h2>
                
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
    <script>
        "use strict";
        function print_invoice(order_id) {
            $.get({
                url: '{{url('/')}}/admin/pos/sample/invoice/' + order_id,
                dataType: 'json',
                beforeSend: function () {
                    $('#loading').show();
                },
                success: function (data) {
                    //console.log("success...")
                    $('#print-invoice').modal('show');
                    $('#printableArea').empty().html(data.view);
                },
                complete: function () {
                    $('#loading').hide();
                },
                error: function (error) {
                    console.log(error)
                }
            });
        }
    </script>

    <script src={{asset("public/assets/admin/js/global.js")}}></script>
@endpush
