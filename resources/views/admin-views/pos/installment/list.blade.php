@extends('layouts.admin.app')
@section('title','installments List')
@push('css_or_js')
    {{-- <meta name="csrf-token" content="{{ csrf_token() }}"> --}}
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/custom.css"/>
@endpush

@section('content')
<style>
    /* Custom styling for POS Installments page */
    .filter-card, .summary-card, .table-card {
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }
    .filter-card .form-label {
        font-weight: 600;
        color: #4a4a4a;
    }
    .summary-card {
        background-color: #ffffff;
        padding: 1rem 1.5rem;
        margin-bottom: 1rem;
    }
    .summary-card .summary-item {
        font-size: 1rem;
        color: #333333;
    }
    .summary-card .summary-value {
        font-size: 1.25rem;
        font-weight: bold;
        color: #0056b3;
    }
    .table-card {
        background-color: #ffffff;
    }
    .table-card .table thead {
        background-color: #0056b3;
    }
    .table-card .table thead th {
        color: #ffffff;
    }
    .modal-content1 {
        border-radius: 8px;
    }
</style>
<div class="content container-fluid">
    <!-- رأس الصفحة -->
    <div class="row align-items-center mb-4">
        <div class="col">
            <h1 class="page-header-title">
                {{ \App\CPU\translate('') }} {{ \App\CPU\translate('التحصيلات') }}
                <span class="badge bg-white ms-2">{{ $installments->total() }}</span>
            </h1>
        </div>
    </div>

    <!-- بطاقة البحث والتصفية -->
{{-- بطاقة الفلاتر --}}
<div class="card border-0 shadow-sm mb-4">
    {{-- رأس مُلوَّن بتدرج خفيف --}}
    <div class="card-header bg-primary bg-gradient text-white rounded-2 py-3 d-flex align-items-center">
                <h5 class="mb-0 fw-bold text-white">{{ \App\CPU\translate('بحث وتصفية') }}</h5>

        <i class="tio-filter_list fs-4 mr-2"></i>
    </div>

    <div class="card-body">
        <form action="{{ url()->current() }}" method="GET" class="row g-3 align-items-start">

            {{-- حقل البحث العام --}}
            <div class="col-12 col-lg-4">
                <label class="form-label fw-semibold">{{ \App\CPU\translate('بحث') }}</label>
                <div class="input-group shadow-sm">
                    <span class="input-group-text bg-white border-0">
                        <i class="tio-search text-muted"></i>
                    </span>
                    <input type="search"
                           name="search"
                           class="form-control border-0"
                           placeholder="{{ \App\CPU\translate('رقم الفاتورة، اسم العميل أو البائع') }}"
                           value="{{ $search }}">
                </div>
            </div>

            {{-- المنطقة --}}
            <div class="col-12 col-md-6 col-lg-3">
                <label class="form-label fw-semibold">{{ \App\CPU\translate('المنطقة') }}</label>
                <div class="input-group shadow-sm">
                    <span class="input-group-text bg-white border-0">
                        <i class="tio-map-making text-muted"></i>
                    </span>
                    <select name="region_id[]" class="custom-select border-0" multiple size="4" style="height:auto;">
                        <option value="">{{ \App\CPU\translate('اختر المنطقة') }}</option>
                        @foreach($regions as $region)
                            <option value="{{ $region->id }}" @selected(in_array((string) $region->id, (array) $regionId))>
                                {{ $region->name }}
                            </option>
                        @endforeach
                    </select>

                    
                </div>
            </div>

            {{-- الفترة الزمنية --}}
            <div class="col-6 col-md-3 col-lg-2">
                <label class="form-label fw-semibold">{{ \App\CPU\translate('من تاريخ') }}</label>
                <input type="date"
                       name="from_date"
                       class="form-control shadow-sm"
                       value="{{ $fromDate }}">
            </div>

            <div class="col-6 col-md-3 col-lg-2">
                <label class="form-label fw-semibold">{{ \App\CPU\translate('إلى تاريخ') }}</label>
                <input type="date"
                       name="to_date"
                       class="form-control shadow-sm"
                       value="{{ $toDate }}">
            </div>
</div>
<div class="row-12">
            {{-- أزرار الإجراءات --}}
            <div class="col-12 d-flex align-items-end">
                <div class="btn-group w-100">
                    <button type="submit" class="btn btn-primary w-50">
                        <i class="tio-checkmark-circle mr-1"></i>
                        {{ \App\CPU\translate('تطبيق') }}
                    </button>
                    <button type="button" class="btn btn-outline-secondary w-50" onclick="printTable()">
                        <i class="tio-print mr-1"></i>
                        {{ \App\CPU\translate('طباعة') }}
                    </button>
                </div>
            </div>
            </div>

        </form>
</div>

    <!-- بطاقة الملخص -->
    <div class="card summary-card row mb-4" >
        <div class="col-md-4 summary-item">
            {{ \App\CPU\translate('إجمالي المبالغ المحصلة') }}:
            <span class="summary-value">{{ number_format($totalAmount, 2) }}</span>
        </div>
            <div class="col-md-4 summary-item">
            {{ \App\CPU\translate('إجمالي الفواتير  الكاش') }}:
            <span class="summary-value">{{ number_format($collectedCashSum, 2) }}</span>
        </div>
    </div>

    <!-- بطاقة الجدول -->
    <div class="card table-card" id="product-table">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle mb-0">
                    <thead>
                        <tr class="text-center">
                            <th>{{ \App\CPU\translate('#') }}</th>
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
                        @foreach($installments as $key => $installment)
                            <tr class="text-center">
                                <td>{{ $key + $installments->firstItem() }}</td>
                                <td>{{ optional($installment->seller)->f_name }} {{ optional($installment->seller)->l_name }}</td>
                                <td>{{ optional($installment->customer)->name }}</td>
                                <td>{{ $installment->customer->regions->name ?? '-' }}</td>
                                <td>{{ number_format($installment->total_price, 2) }}</td>
                                <td>{{ $installment->note }}</td>
                                <td>{{ \Carbon\Carbon::parse($installment->created_at)->format('d M Y') }}</td>
                                <td>{{ $installment->order_id }}<a class="nav-link"
   href="{{ route('admin.pos.orders', ['search' => $installment->order_id]) }}"
   title="{{ \App\CPU\translate('orders') }}">
    ...
</a>
</td>
                              <td class="none">
    <img 
        src="{{ asset('storage/shop/'.$installment['img']) }}" 
        alt="Image Description" 
        style="width: 50px; height: auto; cursor: pointer;" 
        data-toggle="modal" 
        data-target="#imageModal{{ $installment['id'] }}">
</td>
                                <td>
                                    <button class="btn btn-sm btn-outline-success" onclick="print_invoice('{{ $installment->id }}')">
                                        <i class="tio-download"></i> {{ \App\CPU\translate('فاتورة') }}
                                    </button>
                                       <form
        action="{{ route('admin.pos.cancelInstallment', ['id' => $installment->id]) }}"
        method="POST"
        class="d-inline"
        onsubmit="return confirm('هل أنت متأكد من عكس عملية التحصيل لهذه القسط؟');"
    >
        @csrf
        <button type="submit" class="btn btn-sm btn-outline-danger">
            <i class="tio-history"></i> {{ \App\CPU\translate('عكس التحصيل') }}
        </button>
    
            <div class="col-12 d-flex justify-content-end pt-2 border-top">
                {{-- Carries the current filters, so the download matches the screen. --}}
                    <a href="{{ route('admin.pos.installments.export', request()->query()) }}"
                       class="btn btn-success mt-2">
                        <i class="tio-file-outlined"></i> {{ \App\CPU\translate('تصدير CSV') }}
                    </a>
            </div>
        </form>
                                </td>
                            </tr>
                                    <!-- Modal -->
<div class="modal fade none" id="imageModal{{ $installment['id'] }}" tabindex="-1" role="dialog" aria-labelledby="imageModalLabel{{ $installment['id'] }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imageModalLabel{{ $installment['id'] }}">Image Preview</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <img 
                    src="{{ asset('storage/shop/'.$installment['img']) }}" 
                    alt="Image Description" 
                    style="max-width: 100%; height: auto;">
            </div>
        </div>
    </div>
</div>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-4">
                {{ $installments->withQueryString()->links() }}
            </div>
        </div>
    </div>

    <!-- مودال طباعة الفاتورة -->
    <div class="modal fade" id="print-invoice" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content modal-content1">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">{{ \App\CPU\translate('طباعة الفاتورة') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="text-center mb-3">
                        <button type="button" class="btn btn-light mr-2" onclick="printDiv('printableArea')">
                            {{ \App\CPU\translate('إجراء الطباعة إذا كانت الطابعة الحرارية جاهزة') }}
                        </button>
                        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">
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
                <title>{{ \App\CPU\translate('تقرير التحصيلات') }}</title>
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
                    
                <h2>{{ \App\CPU\translate('تقرير   التحصيلات') }}</h2>
                
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
                url: '{{url('admin/pos/installments/invoice')}}/' + order_id,
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
                    console.log(error.responseText);
                },
            });
        }
    </script>

    <script src={{asset("public/assets/admin/js/global.js")}}></script>
@endpush
