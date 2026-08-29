@extends('layouts.admin.app')

@section('title', \App\CPU\translate('Reservations Management'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/custom.css') }}">
    <style>
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
        }
        .badge-pending { background: #ffd70033; color: #c4a000; }
        .badge-approved { background: #4CAF5033; color: #388E3C; }
        .badge-rejected { background: #f4433633; color: #D32F2F; }
        .action-btn-group .btn { padding: 3px 8px; }

        /* ===== بطاقات الطلبات ===== */
        .resv-card {
            border-radius: 12px;
            transition: transform .15s ease, box-shadow .15s ease;
        }
        .resv-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 .5rem 1rem rgba(0,0,0,.08) !important;
        }
        .resv-num {
            display: inline-block;
            background: #eef2ff;
            color: #4338ca;
            font-weight: 700;
            font-size: .75rem;
            padding: 2px 8px;
            border-radius: 999px;
        }
        /* قائمة الأصناف: ارتفاع محدود حتى تتساوى البطاقات مهما كثرت الأصناف. */
        .resv-items {
            max-height: 120px;
            overflow-y: auto;
            font-size: .85rem;
        }
        .resv-items li {
            padding: 3px 0;
            border-bottom: 1px dashed #eee;
        }
        .resv-items li:last-child { border-bottom: 0; }

        /* صفوف أضيق: كانت الخلايا مرتفعة فتتبعثر البيانات. */
        .table td, .table th { padding: 0.45rem 0.6rem; vertical-align: middle; }
        .table td .badge { font-size: 0.72rem; }
        .product-badge {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 8px 12px;
            margin: 4px;
            display: inline-flex;
            align-items: center;
        }
        .product-badge i { margin-right: 8px; color: #6c757d; }
         /* تحسين تصميم التسمية */
    .custom-label {
        font-weight: bold;
        font-size: 16px;
        color: #333;
        margin-bottom: 5px;
        display: block;
    }

    /* تحسين القائمة المنسدلة */
    .custom-select {
        border-radius: 10px; /* زوايا ناعمة */
        border: 2px solid #ddd;
        padding: 10px;
        font-size: 14px;
        background-color: #fff;
        transition: all 0.3s ease-in-out;
    }

    /* تأثير عند تمرير الماوس */
    .custom-select:hover {
        border-color: #007bff;
    }

    /* عند التركيز (تحديده) */
    .custom-select:focus {
        border-color: #007bff;
        box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
    }
    </style>
@endpush

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="page-header pb-3">
        <div class="row align-items-center">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title">
                                       <span class="badge badge-soft-dark ml-2">{{ $reservations->total() }}</span>
                </h1>
            </div>
            <div class="col-sm-auto">
                <button class="btn btn-primary" onclick="printTable()">
                    <i class="tio-print mr-1"></i> {{ \App\CPU\translate('طباعة التقرير') }}
                </button>
            </div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card mb-3 border-0 shadow-sm">
        <div class="card-body p-4">
            <form action="{{ url()->current() }}" method="GET">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">{{ \App\CPU\translate('بحث باسم المندوب') }}</label>
                        <div class="input-group input-group-merged">
                            <span class="input-group-prepend">
                                <div class="input-group-text">
                                    <i class="tio-search"></i>
                                </div>
                            </span>
                            <input type="search" name="search" class="form-control" 
                                   placeholder="{{ \App\CPU\translate('ابحث بالمندوب...') }}" 
                                   value="{{ $search }}">
                        </div>
                    </div>

              

                    <div class="col-md-2">
                        <label class="form-label">{{ \App\CPU\translate('من') }}</label>
                        <input type="date" name="from_date" class="form-control" value="{{ $fromDate }}">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">{{ \App\CPU\translate('إلى') }}</label>
                        <input type="date" name="to_date" class="form-control" value="{{ $toDate }}">
                    </div>

                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="tio-filter-list mr-1"></i> {{ \App\CPU\translate('فلتر') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- بطاقات بدل جدول.
         الجدول كان يضع النوافذ المنبثقة داخل <tbody>، وهو ترميز
         غير صالح فيخرجها المتصفح خارج الجدول ويكسر الصفحة. --}}
    <div class="row g-3">
        @forelse($reservations as $key => $item)
            @php($products = json_decode($item->data))

            <div class="col-12 col-lg-6 col-xl-4 mb-3">
                <div class="card h-100 border-0 shadow-sm resv-card">
                    <div class="card-body p-3">

                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <span class="resv-num">#{{ $reservations->firstItem() + $key }}</span>
                                <span class="font-weight-bold d-block mt-1">
                                    {{ $item->seller->f_name }} {{ $item->seller->l_name }}
                                </span>
                                <small class="text-muted">{{ $item->seller->phone }}</small>
                            </div>
                            <span class="status-badge badge-{{ $item->status_class }}">
                                {{ $item->status_text }}
                            </span>
                        </div>

                        <ul class="list-unstyled mb-2 resv-items">
                            @foreach($products as $product)
                                <li class="d-flex justify-content-between align-items-center">
                                    <span class="text-truncate">{{ $product->product_name ?? '' }}</span>
                                    <span class="badge badge-soft-secondary">{{ $product->stock }}</span>
                                </li>
                            @endforeach
                        </ul>

                        <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                            <small class="text-muted">
                                <i class="tio-date-range"></i>
                                {{ date('d M Y H:i', strtotime($item->created_at)) }}
                            </small>

                            <div class="action-btn-group d-flex">
                                @if($type != 3)
                                    <a href="{{ route('admin.pos.generate_reservation_invoice_notification', $item->id) }}"
                                       class="btn btn-sm btn-soft-primary mx-1" title="مراجعة الطلب">
                                        <i class="tio-visible-outlined"></i>
                                    </a>
                                    <button class="btn btn-sm btn-soft-danger mx-1"
                                            data-toggle="modal"
                                            data-target="#rejectModal-{{ $item->id }}" title="رفض الطلب">
                                        <i class="tio-clear"></i>
                                    </button>
                                @else
                                    <button class="btn btn-sm btn-soft-success mx-1"
                                            onclick="print_invoice('{{ $item->id }}')" title="طباعة الفاتورة">
                                        <i class="tio-print-outlined"></i>
                                    </button>
                                    <button class="btn btn-sm btn-soft-warning mx-1"
                                            data-toggle="modal"
                                            data-target="#returnModal-{{ $item->id }}" title="رد المخزون">
                                        <i class="tio-undo"></i>
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        @empty
            <div class="col-12">
                                <div class="empty-state">
                                    <img class="img-fluid mb-3" src="{{ asset('public/assets/admin/svg/illustrations/sorry.svg') }}" 
                                         alt="لا توجد بيانات" style="max-width: 200px;">
                                    <h4 class="text-muted">{{ \App\CPU\translate('لا توجد طلبات لعرضها') }}</h4>
                                </div>
            </div>
        @endforelse
    </div>

    {{-- النوافذ خارج الشبكة: وضعها داخل <tbody> كان يخرجها المتصفح
         من الجدول فتظهر كأقسام عادية أسفل الصفحة. --}}
    @foreach($reservations as $item)
                        <!-- Rejection Modal -->
                        <div class="modal fade" id="rejectModal-{{ $item->id }}" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header bg-light">
                                        <h5 class="modal-title">{{ \App\CPU\translate('تأكيد الرفض') }}</h5>
                                        <button type="button" class="close" data-dismiss="modal">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <form action="{{ route('admin.pos.deactivateReservedProductsByReservationId', $item->id) }}" method="POST">
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

                        <!-- Return Stock Modal -->
                        {{-- الرد يعكس الصرف: ينقص من رصيد المندوب ويعيد الكمية
                             إلى المخزن. لا يُسمح إلا بما لم يُبَع بعد. --}}
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
                                                        <th class="text-center" style="width:120px;">{{ \App\CPU\translate('الكمية المردودة') }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($products as $idx => $line)
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

            <!-- Pagination -->
            @if($reservations->total() > 0)
            <div class="card-footer border-0 pt-3">
                <div class="row justify-content-center justify-content-sm-between align-items-sm-center">
                    <div class="col-sm-auto">
                        <div class="d-flex justify-content-center justify-content-sm-end">
                            {!! $reservations->links() !!}
                        </div>
                    </div>
                    <div class="col-sm-auto text-center">
                        <span class="text-muted">
                            {{ \App\CPU\translate('عرض') }} {{ $reservations->firstItem() }} - {{ $reservations->lastItem() }} 
                            {{ \App\CPU\translate('من أصل') }} {{ $reservations->total() }}
                        </span>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<div class="modal fade col-md-12" id="print-invoice" tabindex="-1">
    <div class="modal-dialog modal-lg"> <!-- Use modal-lg for a larger modal -->
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
                <div class="row m-auto" id="printableArea">
                    <!-- Content for printing will be dynamically inserted here -->
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
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
<script>
   

    function printTable() {
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
            <head>
                <title>{{ \App\CPU\translate('تقرير اوامر الصرف') }}</title>
                <style>
                  body { 
    font-family: Arial, sans-serif; 
}

.report-header { 
    text-align: center; 
    direction: rtl;  /* جعل العنوان باتجاه عربي */
    border-bottom: 2px solid #333; 
    padding-bottom: 20px;
    margin-bottom: 30px;
}

.table {
    width: 100%;
    direction: rtl;  /* جعل الجدول بالكامل باتجاه عربي */
    border-collapse: collapse;
    margin-top: 20px;
    unicode-bidi: embed; /* يضمن عرض النصوص بشكل صحيح */
}

.table th, .table td {
    border: 1px solid #ddd;
    padding: 10px;
    text-align: right;  /* محاذاة النص إلى اليمين */
}

.table th {
    background-color: #f8f9fa;
    font-weight: bold;
}
.none{
    display:none;
}
                </style>
            </head>
            <body>
                <div class="report-header">
                    <h2>{{ \App\CPU\translate('تقرير اوامر الصرف') }}</h2>
                    <p>{{ \App\CPU\translate('تاريخ التقرير') }}: ${new Date().toLocaleDateString()}</p>
                </div>
                ${document.querySelector('.table').outerHTML}
            </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.print();
    }
</script>

