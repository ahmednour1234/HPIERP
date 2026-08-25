@extends('layouts.admin.app')

@section('content')
<style>
    .badge {
        padding: 3px 8px;
        border-radius: 4px;
        color: white;
        font-size: 13px;
    }

    .badge-success { background-color: #28a745; }
    .badge-danger { background-color: #dc3545; }
    .badge-warning { background-color: #ffc107; color: black; }
    .badge-info { background-color: #17a2b8; }
    .badge-secondary { background-color: #6c757d; }

    /* فلتر شكله أشيك */
    .filter-card {
        border-radius: 12px;
        border: 1px solid #e0e0e0;
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    }
    .filter-title {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 700;
        color: #1f3b57;
    }
    .filter-title-icon {
        width: 32px;
        height: 32px;
        border-radius: 999px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #e3f2fd;
        color: #0d6efd;
        font-size: 18px;
    }
    .filter-label {
        font-weight: 600;
        font-size: 13px;
        color: #6c757d;
    }
    .filter-section-divider {
        border-top: 1px dashed #d0d7de;
        margin-top: 12px;
        padding-top: 16px;
    }
</style>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

<div class="container my-4">
    <h1 class="mb-4">تقرير مبيعات المنتجات</h1>

    <!-- Search Form -->
    <form method="GET" action="{{ route('admin.product.getreportProducts') }}" class="mb-4 p-4 filter-card shadow-sm">
        <div class="mb-4">
            <div class="filter-title">
                <span class="filter-title-icon">
                    <i class="bi bi-funnel-fill"></i>
                </span>
                <span>فلترة التقرير</span>
            </div>
            <small class="text-muted ms-5 d-block mt-1">
                اختر المنتجات، البائع، المنطقة وباقي الخيارات للحصول على تقرير أدق.
            </small>
        </div>

        @php
            // دمج region_id القديم مع region_ids[] لو موجودين في الطلب
            $selectedRegionIds = collect(request('region_ids', []))
                ->when(request()->filled('region_id'), fn($c) => $c->push((int) request('region_id')))
                ->unique()->values()->all();

            // المنتجات المختارة (ID)
            $selectedProductIds = (array) request('product_code', []);
        @endphp

        <div class="row gy-4 gx-3">
            {{-- المنتجات (Multi Select) --}}
            <div class="col-lg-4 col-md-6">
                <label class="form-label filter-label">
                    <i class="bi bi-box-seam me-1"></i> المنتجات
                </label>
                <select name="product_code" class="form-control select2-multiple w-100" 
                        data-placeholder="اختر منتج أو أكثر">
                    @isset($productsall)
                        @foreach($productsall as $product)
                            <option value="{{ $product->product_code }}"
                               >
                                {{ $product->name }} @if(!empty($product->product_code)) ({{ $product->product_code }}) @endif
                            </option>
                        @endforeach
                    @endisset
                </select>
                <small class="text-muted d-block mt-1">
                    اتركه فارغًا لعرض كل المنتجات.
                </small>
            </div>

            {{-- التواريخ --}}
            <div class="col-lg-3 col-md-6">
                <label class="form-label filter-label">
                    <i class="bi bi-calendar-date me-1"></i> من تاريخ
                </label>
                <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
            </div>

            <div class="col-lg-3 col-md-6">
                <label class="form-label filter-label">
                    <i class="bi bi-calendar-event me-1"></i> إلى تاريخ
                </label>
                <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
            </div>

            {{-- البائع --}}
            <div class="col-lg-3 col-md-6">
                <label class="form-label filter-label">
                    <i class="bi bi-person-badge me-1"></i> البائع
                </label>
                <select name="seller_id" class="form-select select2" data-placeholder="كل البائعين">
                    <option value="">كل البائعين</option>
                    @foreach ($sellers as $seller)
                        <option value="{{ $seller->id }}" {{ request('seller_id') == $seller->id ? 'selected' : '' }}>
                            {{ $seller->email }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- المنطقة (Multi Select) --}}
            <div class="col-lg-3 col-md-6">
                <label class="form-label filter-label">
                    <i class="bi bi-geo-alt me-1"></i> المنطقة
                </label>
                <select name="region_ids[]" class="form-control select2-multiple w-100" multiple
                        data-placeholder="كل المناطق">
                    @foreach ($regions as $region)
                        <option value="{{ $region->id }}"
                            {{ in_array($region->id, $selectedRegionIds, true) ? 'selected' : '' }}>
                            {{ $region->name }}
                        </option>
                    @endforeach
                </select>
                <small class="text-muted d-block mt-1">اتركه فارغًا لعرض كل المناطق</small>
            </div>

            {{-- نوع الطلب --}}
            <div class="col-lg-3 col-md-6">
                <label class="form-label filter-label">
                    <i class="bi bi-receipt-cutoff me-1"></i> نوع الطلب
                </label>
                <select name="order_type" class="form-select select2" data-placeholder="كل الأنواع">
                    <option value="">كل الأنواع</option>
                    @foreach ([4 => 'مبيعات', 7 => 'مرتجع مبيعات', 12 => 'عينات', 24 => 'تبرعات'] as $key => $label)
                        <option value="{{ $key }}" {{ request('order_type') == $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- حالة الدفع --}}
            <div class="col-lg-3 col-md-6">
                <label class="form-label filter-label">
                    <i class="bi bi-cash-coin me-1"></i> حالة الدفع
                </label>
                <select name="payment_status" class="form-select select2" data-placeholder="كل الحالات">
                    <option value="">كل الحالات</option>
                    <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>تم التحصيل</option>
                    <option value="unpaid" {{ request('payment_status') == 'unpaid' ? 'selected' : '' }}>لم يتم التحصيل</option>
                </select>
            </div>

            {{-- حالة الفاتورة (Multi Select) --}}
            <div class="col-lg-3 col-md-6">
                <label class="form-label filter-label">
                    <i class="bi bi-file-earmark-bar-graph me-1"></i> حالة الفاتورة
                </label>
                @php
                    $selectedStatuses = (array) request()->input('invoice_status', []);
                @endphp
                <select name="invoice_status[]" class="form-control select2-multiple w-100" multiple
                        data-placeholder="كل حالات الفواتير">
                    <option value="paid"             {{ in_array('paid', $selectedStatuses) ? 'selected' : '' }}>
                        محصلة بالكامل
                    </option>
                    <option value="returned_fully"   {{ in_array('returned_fully', $selectedStatuses) ? 'selected' : '' }}>
                        الإرجاع الكامل
                    </option>
                    <option value="partial_paid"     {{ in_array('partial_paid', $selectedStatuses) ? 'selected' : '' }}>
                        تحصيل جزئي فقط
                    </option>
                    <option value="partial_returned" {{ in_array('partial_returned', $selectedStatuses) ? 'selected' : '' }}>
                        إرجاع جزئي فقط
                    </option>
                    <option value="partial_both"     {{ in_array('partial_both', $selectedStatuses) ? 'selected' : '' }}>
                        تحصيل جزئي وإرجاع جزئي
                    </option>
                    <option value="unpaid"           {{ in_array('unpaid', $selectedStatuses) ? 'selected' : '' }}>
                        غير محصلة
                    </option>
                </select>
                <small class="text-muted d-block mt-1">
                    يمكنك اختيار أكثر من حالة بالضغط مع Ctrl/⌘.
                </small>
            </div>
        </div>

        <div class="filter-section-divider d-flex justify-content-between align-items-center">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-search me-1"></i> بحث
                </button>
                <a href="{{ route('admin.product.getreportProducts') }}" class="btn btn-light border px-3">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> تصفية جديدة
                </a>
            </div>

            <button type="button" class="btn btn-outline-secondary px-4" onclick="printTable()">
                <i class="bi bi-printer me-1"></i> طباعة
            </button>
        </div>
    </form>

    <div class="card-body" id="product-table">
        <!-- Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card shadow-sm border-start border-success border-4 h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <i class="bi bi-box-seam fs-2 text-success"></i>
                        <div>
                            <div class="text-muted small">إجمالي عدد المنتجات المباعة</div>
                            <div class="fw-bold fs-5">{{ $productCount }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card shadow-sm border-start border-primary border-4 h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <i class="bi bi-stack fs-2 text-primary"></i>
                        <div>
                            <div class="text-muted small">إجمالي كميات المنتجات المباعة</div>
                            <div class="fw-bold fs-5">{{ $quantitySum }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card shadow-sm border-start border-primary border-4 h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <i class="bi bi-stack fs-2 text-primary"></i>
                        <div>
                            <div class="text-muted small">إجمالي كميات المنتجات المحصلة</div>
                            <div class="fw-bold fs-5">{{ $collectedUnits }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card shadow-sm border-start border-info border-4 h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <i class="bi bi-tags fs-2 text-info"></i>
                        <div>
                            <div class="text-muted small">إجمالي مبالغ المنتجات</div>
                            <div class="fw-bold fs-5">{{ number_format($priceSum, 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card shadow-sm border-start border-success border-4 h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <i class="bi bi-cash-coin fs-2 text-success"></i>
                        <div>
                            <div class="text-muted small">إجمالي مبالغ البيع</div>
                            <div class="fw-bold fs-5">{{ number_format($orderAmountType4, 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card shadow-sm border-start border-warning border-4 h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <i class="bi bi-arrow-counterclockwise fs-2 text-warning"></i>
                        <div>
                            <div class="text-muted small">إجمالي مبالغ المرتجع</div>
                            <div class="fw-bold fs-5">{{ number_format($orderAmountType7, 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card shadow-sm border-start border-primary border-4 h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <i class="bi bi-check-circle fs-2 text-primary"></i>
                        <div>
                            <div class="text-muted small">إجمالي مبالغ المحصلة</div>
                            <div class="fw-bold fs-5">{{ number_format($transactionRefType4, 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card shadow-sm border-start border-danger border-4 h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <i class="bi bi-exclamation-circle fs-2 text-danger"></i>
                        <div>
                            <div class="text-muted small">إجمالي المبالغ المتبقية بدون تحصيل</div>
                            <div class="fw-bold fs-5">{{ number_format($amountDue, 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- بطاقات حالات الفواتير --}}
        <div class="row g-3 mb-4">
            <div class="col-md-2">
                <div class="card border-start border-success shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="text-muted small">فواتير محصلة</div>
                        <div class="fs-5 fw-bold">{{ $invoiceStatusCounts['paid'] }}</div>
                    </div>
                </div>
            </div>

            <div class="col-md-2">
                <div class="card border-start border-danger shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="text-muted small">غير محصلة</div>
                        <div class="fs-5 fw-bold">{{ $invoiceStatusCounts['unpaid'] }}</div>
                    </div>
                </div>
            </div>

            <div class="col-md-2">
                <div class="card border-start border-warning shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="text-muted small">إرجاع كامل</div>
                        <div class="fs-5 fw-bold">{{ $invoiceStatusCounts['returned_fully'] }}</div>
                    </div>
                </div>
            </div>

            <div class="col-md-2">
                <div class="card border-start border-info shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="text-muted small">تحصيل جزئي فقط</div>
                        <div class="fs-5 fw-bold">{{ $invoiceStatusCounts['partial_paid'] }}</div>
                    </div>
                </div>
            </div>

            <div class="col-md-2">
                <div class="card border-start border-info shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="text-muted small">إرجاع جزئي فقط</div>
                        <div class="fs-5 fw-bold">{{ $invoiceStatusCounts['partial_returned'] }}</div>
                    </div>
                </div>
            </div>

            <div class="col-md-2">
                <div class="card border-start border-primary shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="text-muted small">تحصيل جزئي + إرجاع جزئي</div>
                        <div class="fs-5 fw-bold">{{ $invoiceStatusCounts['partial_both'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Products Table -->
        <table class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>معرّف المنتج</th>
                    <th>اسم المنتج</th>
                    <th>كود المنتج</th>
                    <th>سعر البيع</th>
                    <th>الكمية</th>
                    <th>البائع</th>
                    <th>العميل</th>
                    <th>المنطقة</th>
                    <th>النوع</th>
                    <th>المبلغ المحصل</th>
                    <th>رقم الفاتورة</th>
                    <th>تاريخ البيع</th>
                    <th>الحالة</th>
                    <th class="none">{{ \App\CPU\translate('صورة') }}</th>
                    <th class="none">{{ \App\CPU\translate('روؤية الفاتورة') }}</th>
                </tr>
            </thead>
            <tbody>
            @foreach($products as $productDetails)
                @foreach($productDetails as $index => $product)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $product['product_name'] }}</td>
                        <td>{{ $product['product_code'] }}</td>
                        <td>{{ $product['selling_price'] }}</td>
                        <td>{{ $product['quantity'] }}</td>
                        <td>{{ $product['seller'] }}</td>
                        <td>{{ $product['customer'] ?? 'غير متوفر' }}</td>
                        <td>{{ $product['region'] ?? 'غير متوفر' }}</td>
                        <td>
                            @if($product['order_type'] == 4)
                                مبيعات
                            @elseif($product['order_type'] == 7)
                                مرتجع مبيعات
                            @elseif($product['order_type'] == 12)
                                عينات
                            @elseif($product['order_type'] == 24)
                                تبرعات
                            @else
                                غير معروف
                            @endif
                        </td>
                        <td>{{ $product['transaction_reference'] }}</td>
                        <td>{{ $product['order_id'] }}</td>
                        <td>{{ $product['created_at'] }}</td>

                        @php
                            $statusMap = [
                                'paid' => [
                                    'label' => 'محصلة بالكامل',
                                    'class' => 'badge-success',
                                    'desc'  => 'تم تحصيل كامل المبلغ بدون أي إرجاعات.'
                                ],
                                'unpaid' => [
                                    'label' => 'غير محصلة',
                                    'class' => 'badge-danger',
                                    'desc'  => 'لم يتم تحصيل أي جزء من المبلغ حتى الآن.'
                                ],
                                'returned_fully' => [
                                    'label' => 'إرجاع كامل',
                                    'class' => 'badge-warning',
                                    'desc'  => 'تم إرجاع كل الكميات/القيم .'
                                ],
                                'partial_paid' => [
                                    'label' => 'تحصيل جزئي فقط',
                                    'class' => 'badge-info',
                                    'desc'  => 'تم تحصيل جزء من المبلغ وما زال هناك رصيد مستحق.'
                                ],
                                'partial_returned' => [
                                    'label' => 'إرجاع جزئي فقط',
                                    'class' => 'badge-info',
                                    'desc'  => 'تم إرجاع جزء من الطلب بدون تحصيل مبالغ إضافية.'
                                ],
                                'partial_both' => [
                                    'label' => 'تحصيل جزئي وإرجاع جزئي',
                                    'class' => 'badge-secondary',
                                    'desc'  => 'تم تحصيل جزء وإرجاع جزء آخر؛.'
                                ],
                            ];

                            $statusKey = $product['invoice_status'] ?? null;
                            $info = $statusMap[$statusKey] ?? [
                                'label' => 'غير معروفة',
                                'class' => 'badge-secondary',
                                'desc'  => 'حالة غير معروفة — تحقق من منطق تحديد الحالة.'
                            ];
                        @endphp

                        <td>
                            <span
                                class="badge {{ $info['class'] }}"
                                data-bs-toggle="tooltip"
                                data-bs-placement="top"
                                title="{{ $info['desc'] }}"
                            >
                                {{ $info['label'] }}
                            </span>
                        </td>

                        <td class="none">
                            <img
                                src="{{ asset('storage/app/public/'.$product['img']) }}"
                                alt="Image Description"
                                style="width: 50px; height: auto; cursor: pointer;"
                                data-toggle="modal"
                                data-target="#imageModal{{ $product['order_id'] }}">
                        </td>
                        <td class="none">
                            <button class="btn btn-sm btn-white" target="_blank" type="button"
                                    onclick="print_invoice('{{ $product['order_id']}}')">
                                <i class="tio-download"></i> {{ \App\CPU\translate('الفاتورة') }}
                            </button>
                        </td>
                    </tr>

                    <!-- Modal -->
                    <div class="modal fade none" id="imageModal{{ $product['order_id'] }}" tabindex="-1" role="dialog" aria-labelledby="imageModalLabel{{ $product['order_id'] }}" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="imageModalLabel{{ $product['order_id'] }}">Image Preview</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body text-center">
                                    <img
                                        src="{{ asset('storage/app/public/'.$product['img']) }}"
                                        alt="Image Description"
                                        style="max-width: 100%; height: auto;">
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endforeach
            </tbody>
        </table>

        <div class="card-footer none">
            <!-- Pagination -->
            <div class="row justify-content-center justify-content-sm-between align-items-sm-center">
                <div class="col-sm-auto">
                    <div class="d-flex justify-content-center justify-content-sm-end">
                        {!! $orderDetails->withQueryString()->links() !!}
                    </div>
                </div>
            </div>
            <!-- End Pagination -->
        </div>
    </div>

    <div class="modal fade" id="print-invoice" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content modal-content1">
                <div class="modal-header">
                    <h5 class="modal-title">{{ \App\CPU\translate('print') }} {{ \App\CPU\translate('invoice') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span class="text-dark" aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body row">
                    <div class="col-md-12">
                        <center>
                            <input type="button" class="mt-2 btn btn-primary non-printable"
                                   onclick="printDiv('printableArea')"
                                   value="{{ \App\CPU\translate('Proceed, If thermal printer is ready') }}."/>
                            <a href="{{ url()->previous() }}"
                               class="mt-2 btn btn-danger non-printable">{{ \App\CPU\translate('Back') }}</a>
                        </center>
                        <hr class="non-printable">
                    </div>
                    <div class="row m-auto" id="printableArea"></div>
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
            <html lang="ar" dir="rtl">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>{{ \App\CPU\translate('تقرير المبيعات') }}</title>
                <style>
                    body {
                        font-family: 'Cairo', Arial, sans-serif;
                        margin: 0;
                        background-color: #f9f9f9;
                        color: #333;
                        direction: rtl;
                        padding: 20px;
                    }
                    h1, h2 {
                        text-align: center;
                        color: #003366;
                        font-weight: bold;
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
                        font-size: 14px;
                    }
                    .logo img {
                        max-width: 150px;
                        height: auto;
                    }
                    table {
                        width: 100%;
                        border-collapse: collapse;
                        margin-bottom: 20px;
                        font-size: 13px;
                    }
                    table th, table td {
                        border: 1px solid #ddd;
                        padding: 6px 8px;
                        text-align: center;
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
                    input[type="search"][aria-controls^="DataTables_Table_"] {
                        display: none;
                    }
                    label:has(input[type="search"][aria-controls^="DataTables_Table_"]) {
                        display: none;
                    }
                    [id^="DataTables_Table_"][id$="_info"]{
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

                <h2>{{ \App\CPU\translate('تقرير   المبيعات') }}</h2>

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

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Bootstrap tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.forEach(function (el) {
            if (window.bootstrap && bootstrap.Tooltip) {
                new bootstrap.Tooltip(el);
            }
        });
    });
</script>

@push('script_2')
    <script>
        "use strict";
        function print_invoice(order_id) {
            $.get({
                url: '{{url('/')}}/admin/pos/invoice/' + order_id,
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
                    console.log(error)
                }
            });
        }

        // تفعيل Select2 لكل الـ selects الخاصة بالفلترة
        $(document).ready(function () {
            if ($.fn.select2) {
                $('.select2').select2({
                    width: '100%',
                    dir: 'rtl',
                    allowClear: true,
                    placeholder: function(){
                        return $(this).data('placeholder') || '';
                    }
                });

                $('.select2-multiple').select2({
                    width: '100%',
                    dir: 'rtl',
                    closeOnSelect: false,
                    placeholder: function(){
                        return $(this).data('placeholder') || '';
                    }
                });
            }
        });
    </script>

    <script src={{asset("public/assets/admin/js/global.js")}}></script>
@endpush
