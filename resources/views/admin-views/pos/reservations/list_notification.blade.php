@extends('layouts.admin.app')

@section('title', \App\CPU\translate('Reservations Management'))

@push('css_or_js')
    <style>
        /* النوافذ مخفية افتراضيًا.
           بدون هذا تُعرض داخل الصفحة كقسم عادي — وهو ما كان يحدث فعلًا —
           لأن ملف bootstrap.css المحمّل هنا لا يحمل قواعد .modal. */
        .modal { display: none; }
        .modal.show { display: block; }
        .modal-backdrop { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
                          background: #000; opacity: .5; z-index: 1040; }
        .modal.show { z-index: 1050; }
    </style>
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
        .action-btn-group .btn { padding: 6px 12px; }
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

    <!-- Reservations Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-borderless table-thead-bordered">
                    <thead class="thead-light">
                        <tr>
                            <th>{{ \App\CPU\translate('#') }}</th>
                            <th>{{ \App\CPU\translate('المندوب') }}</th>
                            <th>{{ \App\CPU\translate('المنتجات') }}</th>
                            <th class="text-center">{{ \App\CPU\translate('الحالة') }}</th>
                            <th>{{ \App\CPU\translate('التاريخ') }}</th>
                            <th class="text-center none">{{ \App\CPU\translate('الإجراءات') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reservations as $key => $item)
                        @php
                            $products = json_decode($item->data);
                        @endphp
                        <tr>
                            <td>{{ $key + 1 }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-circle mr-2">
                                        <span class="avatar-initials">
                                            {{ substr($item->seller->f_name, 0, 1) }}{{ substr($item->seller->l_name, 0, 1) }}
                                        </span>
                                    </div>
                                    <div>
                                        <span class="d-block font-weight-bold">{{ $item->seller->f_name }} {{ $item->seller->l_name }}</span>
                                        <small class="text-muted">{{ $item->seller->phone }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @foreach($products as $product)
                                @php
                                    $p = \App\Models\Product::find($product->product_id);
                                @endphp
                                <div class="product-badge">
                                    <i class="tio-shopping-basket-outlined"></i>
                                    <div>
                                        <span class="d-block">{{ $p->name ?? 'N/A' }}</span>
                                        <small class="text-muted">
                                            {{ $product->stock }} 
                                        </small>
                                    </div>
                                </div>
                                @endforeach
                            </td>
                            <td class="text-center">
                                <span class="status-badge badge-{{ $item->status_class }}">
                                    {{ $item->status_text }}
                                </span>
                            </td>
                            <td>{{ date('d M Y H:i', strtotime($item->created_at)) }}</td>
                            <td class="text-center none">
                                <div class="action-btn-group d-flex justify-content-center">
                                    @if($type != 3)
                                    <a href="{{ route('admin.pos.generate_reservation_invoice_notification', $item->id) }}" 
                                       class="btn btn-sm btn-soft-primary mx-1"
                                       data-toggle="tooltip" title="مراجعة الطلب">
                                        <i class="tio-visible-outlined"></i>
                                    </a>
                                    <button class="btn btn-sm btn-soft-danger mx-1" 
                                            data-toggle="modal" 
                                            data-target="#rejectModal-{{ $item->id }}"
                                            title="رفض الطلب">
                                        <i class="tio-clear"></i>
                                    </button>
                                    @else
                                    <button class="btn btn-sm btn-soft-success mx-1"
                                            onclick="print_invoice('{{ $item->id }}')"
                                            title="طباعة الفاتورة">
                                        <i class="tio-print-outlined"></i>
                                    </button>

                                    {{-- رد مخزون تم صرفه: يعكس أثر الصرف --}}
                                    <button class="btn btn-sm btn-soft-warning mx-1"
                                            data-toggle="modal"
                                            data-target="#returnModal-{{ $item->id }}"
                                            title="رد المخزون">
                                        <i class="tio-undo"></i>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>

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
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="empty-state">
                                    <img class="img-fluid mb-3" src="{{ asset('public/assets/admin/svg/illustrations/sorry.svg') }}" 
                                         alt="لا توجد بيانات" style="max-width: 200px;">
                                    <h4 class="text-muted">{{ \App\CPU\translate('لا توجد طلبات لعرضها') }}</h4>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
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

@push('script_2')
    <script>
        "use strict";

        // فتح وإغلاق النوافذ يدويًا.
        //
        // القالب لا يحمّل bootstrap.js مستقلًا، فـ data-toggle="modal" لا
        // يعمل: النافذة تُعرض داخل الصفحة كقسم عادي بدل أن تطفو فوقها.
        // هذا يشمل نافذة رفض الطلب الأصلية ونافذة رد المخزون معًا.
        document.addEventListener('DOMContentLoaded', function () {

            function openModal(modal) {
                if (!modal) { return; }

                modal.classList.add('show');
                modal.style.display = 'block';
                document.body.classList.add('modal-open');

                // الخلفية المعتمة؛ تُنشأ مرة واحدة.
                if (!document.querySelector('.modal-backdrop')) {
                    var backdrop = document.createElement('div');
                    backdrop.className = 'modal-backdrop fade show';
                    document.body.appendChild(backdrop);
                }
            }

            function closeModal(modal) {
                if (!modal) { return; }

                modal.classList.remove('show');
                modal.style.display = 'none';
                document.body.classList.remove('modal-open');

                var backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) { backdrop.remove(); }
            }

            document.querySelectorAll('[data-toggle="modal"]').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    openModal(document.querySelector(btn.getAttribute('data-target')));
                });
            });

            document.querySelectorAll('[data-dismiss="modal"]').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    closeModal(btn.closest('.modal'));
                });
            });

            // الضغط على الخلفية يغلق النافذة، لا الضغط داخل محتواها.
            document.querySelectorAll('.modal').forEach(function (modal) {
                modal.addEventListener('click', function (e) {
                    if (e.target === modal) { closeModal(modal); }
                });
            });
        });
    </script>
@endpush