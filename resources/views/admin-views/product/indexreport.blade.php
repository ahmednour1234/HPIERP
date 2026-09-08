@extends('layouts.admin.app')

@section('title', 'تقرير مبيعات المنتجات')

@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/custom.css') }}"/>
    <style>
        .product-report-page {
            direction: rtl;
            min-height: calc(100vh - 4rem);
            padding-top: 2rem;
            padding-bottom: 2.5rem;
            background: linear-gradient(135deg, rgba(17, 36, 90, .05), rgba(20, 184, 166, .08)), #f4f8fb;
            color: #102a43;
        }

        .product-report-shell {
            display: flex;
            flex-direction: column;
            gap: 1.15rem;
        }

        .product-report-hero,
        .product-report-filter,
        .product-report-card {
            border: 1px solid #d9e6f2;
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 14px 32px rgba(15, 23, 42, .08);
            overflow: hidden;
        }

        .product-report-hero {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1.35rem 1.5rem;
            background: linear-gradient(135deg, #111857, #0f766e);
            color: #fff;
        }

        .product-report-title {
            display: flex;
            align-items: center;
            gap: .75rem;
            margin: 0;
            color: #fff;
            font-size: 1.65rem;
            font-weight: 900;
        }

        .product-report-title span,
        .product-report-section-title span {
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .product-report-title span {
            width: 3rem;
            height: 3rem;
            background: rgba(255, 255, 255, .14);
        }

        .product-report-hero p {
            margin: .4rem 3.75rem 0 0;
            color: rgba(255, 255, 255, .78);
            font-weight: 700;
        }

        .product-report-count {
            border-radius: 999px;
            padding: .55rem .9rem;
            background: #facc15;
            color: #111857;
            font-weight: 900;
            white-space: nowrap;
        }

        .product-report-filter .card-header,
        .product-report-card .card-header {
            border-bottom: 1px solid #e7eef6;
            background: linear-gradient(90deg, #f8fbff, #eef7fb);
            padding: 1rem 1.15rem;
        }

        .product-report-section-title {
            display: flex;
            align-items: center;
            gap: .65rem;
            margin: 0;
            color: #132f52;
            font-weight: 900;
        }

        .product-report-section-title span {
            width: 2.4rem;
            height: 2.4rem;
            background: #11245a;
            color: #fff;
        }

        .product-report-filter .card-body {
            padding: 1.25rem;
        }

        .product-report-filter label {
            color: #52677f;
            font-weight: 900;
            margin-bottom: .45rem;
        }

        .product-report-filter .form-control,
        .product-report-filter .custom-select,
        .product-report-filter .bootstrap-select > .dropdown-toggle {
            min-height: 3.15rem;
            border: 1px solid #d5e2ef !important;
            border-radius: 8px !important;
            background: #fff !important;
            color: #102a43 !important;
            font-weight: 800;
            box-shadow: 0 8px 18px rgba(15, 23, 42, .04) !important;
        }

        .product-report-filter .bootstrap-select {
            width: 100% !important;
        }

        .product-report-filter .filter-option-inner-inner {
            text-align: right;
        }

        .product-report-help {
            display: block;
            margin-top: .35rem;
            color: #7a8da1;
            font-size: .78rem;
            font-weight: 700;
        }

        .product-report-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: .65rem;
            border-top: 1px solid #e7eef6;
            padding: 1rem 1.25rem;
            background: #fbfdff;
        }

        .product-report-actions-group {
            display: flex;
            flex-wrap: wrap;
            gap: .65rem;
        }

        .product-report-btn {
            min-height: 3rem;
            border-radius: 8px !important;
            padding: .7rem 1.1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .45rem;
            font-weight: 900;
            box-shadow: 0 10px 20px rgba(15, 23, 42, .07);
        }

        .product-report-summary,
        .product-report-status-grid {
            display: grid;
            gap: 1rem;
        }

        .product-report-summary {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .product-report-status-grid {
            grid-template-columns: repeat(6, minmax(0, 1fr));
        }

        .product-report-summary-card,
        .product-report-status-card {
            border: 1px solid #dbe7f2;
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 10px 22px rgba(15, 23, 42, .05);
            position: relative;
            overflow: hidden;
        }

        .product-report-summary-card {
            min-height: 6.7rem;
            padding: 1rem;
        }

        .product-report-status-card {
            min-height: 5.2rem;
            padding: .85rem;
        }

        .product-report-summary-card::after,
        .product-report-status-card::after {
            content: "";
            position: absolute;
            inset: auto auto -2.75rem -2.75rem;
            width: 6rem;
            height: 6rem;
            border-radius: 50%;
            background: currentColor;
            opacity: .1;
        }

        .product-report-label {
            display: block;
            color: #60758b;
            font-size: .82rem;
            font-weight: 900;
            margin-bottom: .5rem;
        }

        .product-report-value {
            color: #102a43;
            font-size: 1.25rem;
            font-weight: 900;
            word-break: break-word;
        }

        .product-report-table-wrap {
            overflow-x: auto !important;
            overflow-y: hidden;
            border-radius: 8px;
        }

        .product-report-table {
            min-width: 112rem;
            margin: 0;
        }

        .product-report-table thead th {
            border: 0;
            background: #11245a;
            color: #fff;
            padding: 1rem;
            font-weight: 900;
            text-align: right;
            white-space: nowrap;
        }

        .product-report-table tbody td {
            border-top: 1px solid #e7eef6;
            padding: .95rem 1rem;
            color: #506882;
            font-weight: 800;
            vertical-align: middle;
        }

        .product-report-table tbody tr:hover {
            background: #f8fbff;
        }

        .product-report-index,
        .product-report-invoice {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            padding: .38rem .7rem;
            font-weight: 900;
            white-space: nowrap;
        }

        .product-report-index {
            min-width: 2.5rem;
            background: #eef4fb;
            color: #52677f;
        }

        .product-report-invoice {
            min-width: 4.5rem;
            background: #e0f2fe;
            color: #075985;
            text-decoration: none;
        }

        .product-report-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            padding: .38rem .65rem;
            font-size: .8rem;
            font-weight: 900;
            white-space: nowrap;
        }

        .product-report-pill--sale { background: #dcfce7; color: #166534; }
        .product-report-pill--refund { background: #ffe4e6; color: #be123c; }
        .product-report-pill--sample { background: #e0f2fe; color: #075985; }
        .product-report-pill--donation { background: #fef3c7; color: #92400e; }
        .product-report-pill--neutral { background: #eef4fb; color: #52677f; }

        .product-report-empty {
            padding: 3rem 1rem;
            text-align: center;
            color: #71869c;
            font-weight: 800;
        }

        .product-report-pagination {
            border-top: 1px solid #e7eef6;
            background: #fff;
        }

        @media (max-width: 1199.98px) {
            .product-report-summary {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .product-report-status-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 767.98px) {
            .product-report-page {
                padding-top: 1rem;
            }

            .product-report-hero {
                flex-direction: column;
                align-items: stretch;
            }

            .product-report-hero p {
                margin-right: 0;
            }

            .product-report-summary,
            .product-report-status-grid {
                grid-template-columns: 1fr;
            }

            .product-report-actions {
                flex-direction: column-reverse;
            }

            .product-report-actions-group,
            .product-report-btn {
                width: 100%;
            }
        }

        @media print {
            .product-report-filter,
            .product-report-hero,
            .product-report-pagination,
            .non-printable,
            .none,
            .admin-table-scroll-proxy {
                display: none !important;
            }

            .product-report-page,
            .product-report-card,
            .product-report-summary-card,
            .product-report-status-card {
                background: #fff;
                box-shadow: none;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $totalRows = method_exists($orderDetails, 'total') ? $orderDetails->total() : count($products);
        $selectedRegionIds = collect(request('region_ids', []))
            ->when(request()->filled('region_id'), fn($c) => $c->push((int) request('region_id')))
            ->unique()->values()->all();
        $selectedStatuses = (array) request()->input('invoice_status', []);
        $statusMeta = [
            'paid' => ['label' => 'محصلة بالكامل', 'class' => 'product-report-pill--sale', 'color' => '#16a34a'],
            'unpaid' => ['label' => 'غير محصلة', 'class' => 'product-report-pill--refund', 'color' => '#dc2626'],
            'returned_fully' => ['label' => 'إرجاع كامل', 'class' => 'product-report-pill--donation', 'color' => '#d97706'],
            'partial_paid' => ['label' => 'تحصيل جزئي', 'class' => 'product-report-pill--sample', 'color' => '#0284c7'],
            'partial_returned' => ['label' => 'إرجاع جزئي', 'class' => 'product-report-pill--sample', 'color' => '#0f766e'],
            'partial_both' => ['label' => 'تحصيل وإرجاع جزئي', 'class' => 'product-report-pill--neutral', 'color' => '#64748b'],
        ];
        $typeMeta = [
            4 => ['label' => 'مبيعات', 'class' => 'product-report-pill--sale'],
            7 => ['label' => 'مرتجع مبيعات', 'class' => 'product-report-pill--refund'],
            12 => ['label' => 'عينات', 'class' => 'product-report-pill--sample'],
            24 => ['label' => 'تبرعات', 'class' => 'product-report-pill--donation'],
        ];
    @endphp

    <div class="content container-fluid product-report-page">
        <div class="product-report-shell">
            <section class="product-report-hero">
                <div>
                    <h1 class="product-report-title">
                        <span><i class="tio-chart-bar-4"></i></span>
                        تقرير مبيعات المنتجات
                    </h1>
                    <p>تصفية وتحليل المنتجات حسب الفترة، البائع، المنطقة، نوع الطلب وحالة الفاتورة.</p>
                </div>
                <div class="product-report-count">{{ number_format($totalRows) }} نتيجة</div>
            </section>

            <div class="card product-report-filter">
                <div class="card-header">
                    <h5 class="product-report-section-title">
                        <span><i class="tio-filter-list"></i></span>
                        فلترة التقرير
                    </h5>
                </div>

                <form method="GET" action="{{ route('admin.product.getreportProducts') }}">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-xl-4 col-lg-6 mb-3">
                                <label>المنتجات</label>
                                <select name="product_code[]" class="form-control selectpicker searchable-multiple-picker" multiple
                                        data-live-search="true" title="اختر منتج أو أكثر">
                                    @foreach($productsall as $product)
                                        <option value="{{ $product->product_code }}"
                                            {{ in_array((string) $product->product_code, array_map('strval', (array) request('product_code', [])), true) ? 'selected' : '' }}>
                                            {{ $product->name }} @if($product->product_code) ({{ $product->product_code }}) @endif
                                        </option>
                                    @endforeach
                                </select>
                                <span class="product-report-help">اتركه فارغا لعرض كل المنتجات.</span>
                            </div>

                            <div class="col-xl-2 col-lg-3 col-md-6 mb-3">
                                <label>من تاريخ</label>
                                <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                            </div>

                            <div class="col-xl-2 col-lg-3 col-md-6 mb-3">
                                <label>إلى تاريخ</label>
                                <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                            </div>

                            <div class="col-xl-4 col-lg-6 mb-3">
                                <label>البائع</label>
                                <select name="seller_id[]" class="form-control selectpicker searchable-multiple-picker" multiple
                                        data-live-search="true" title="كل البائعين">
                                    @foreach ($sellers as $seller)
                                        <option value="{{ $seller->id }}"
                                            {{ in_array((string) $seller->id, array_map('strval', (array) request('seller_id', [])), true) ? 'selected' : '' }}>
                                            {{ $seller->email }}
                                        </option>
                                    @endforeach
                                </select>
                                <span class="product-report-help">يمكن اختيار أكثر من بائع.</span>
                            </div>

                            <div class="col-xl-3 col-lg-4 col-md-6 mb-3">
                                <label>المنطقة</label>
                                <select name="region_ids[]" class="form-control selectpicker searchable-multiple-picker" multiple
                                        data-live-search="true" title="كل المناطق">
                                    @foreach ($regions as $region)
                                        <option value="{{ $region->id }}"
                                            {{ in_array($region->id, $selectedRegionIds, true) ? 'selected' : '' }}>
                                            {{ $region->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <span class="product-report-help">يمكن اختيار أكثر من منطقة.</span>
                            </div>

                            <div class="col-xl-3 col-lg-4 col-md-6 mb-3">
                                <label>نوع الطلب</label>
                                <select name="order_type[]" class="form-control selectpicker searchable-multiple-picker" multiple
                                        data-live-search="true" title="كل الأنواع">
                                    @foreach ([4 => 'مبيعات', 7 => 'مرتجع مبيعات', 12 => 'عينات', 24 => 'تبرعات'] as $key => $label)
                                        <option value="{{ $key }}"
                                            {{ in_array((string) $key, array_map('strval', (array) request('order_type', [])), true) ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-xl-3 col-lg-4 col-md-6 mb-3">
                                <label>حالة الدفع</label>
                                <select name="payment_status[]" class="form-control selectpicker searchable-multiple-picker" multiple
                                        data-live-search="true" title="كل الحالات">
                                    @foreach (['paid' => 'تم التحصيل', 'unpaid' => 'لم يتم التحصيل'] as $key => $label)
                                        <option value="{{ $key }}"
                                            {{ in_array($key, array_map('strval', (array) request('payment_status', [])), true) ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-xl-3 col-lg-4 col-md-6 mb-3">
                                <label>حالة الفاتورة</label>
                                <select name="invoice_status[]" class="form-control selectpicker searchable-multiple-picker" multiple
                                        data-live-search="true" title="كل حالات الفواتير">
                                    @foreach($statusMeta as $key => $meta)
                                        <option value="{{ $key }}" {{ in_array($key, $selectedStatuses, true) ? 'selected' : '' }}>
                                            {{ $meta['label'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="product-report-actions">
                        <div class="product-report-actions-group">
                            <a href="{{ route('admin.product.getreportProducts.export', request()->query()) }}"
                               class="btn btn-success product-report-btn">
                                <i class="tio-download-to"></i> تصدير إكسل
                            </a>
                            <button type="button" class="btn btn-outline-secondary product-report-btn" onclick="printTable()">
                                <i class="tio-print"></i> طباعة
                            </button>
                        </div>
                        <div class="product-report-actions-group">
                            <a href="{{ route('admin.product.getreportProducts') }}" class="btn btn-outline-secondary product-report-btn">
                                تصفية جديدة
                            </a>
                            <button type="submit" class="btn btn-primary product-report-btn">
                                <i class="tio-search"></i> بحث
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <div id="product-table">
                <div class="product-report-summary mb-3">
                    <div class="product-report-summary-card" style="color:#11245a">
                        <span class="product-report-label">عدد المنتجات</span>
                        <span class="product-report-value">{{ number_format($productCount) }}</span>
                    </div>
                    <div class="product-report-summary-card" style="color:#0f9f6e">
                        <span class="product-report-label">كميات المنتجات</span>
                        <span class="product-report-value">{{ number_format($quantitySum) }}</span>
                    </div>
                    <div class="product-report-summary-card" style="color:#0284c7">
                        <span class="product-report-label">إجمالي المنتجات</span>
                        <span class="product-report-value">{{ number_format($priceSum, 2) }}</span>
                    </div>
                    <div class="product-report-summary-card" style="color:#dc2626">
                        <span class="product-report-label">المتبقي بدون تحصيل</span>
                        <span class="product-report-value">{{ number_format($amountDue, 2) }}</span>
                    </div>
                    <div class="product-report-summary-card" style="color:#16a34a">
                        <span class="product-report-label">إجمالي البيع</span>
                        <span class="product-report-value">{{ number_format($orderAmountType4, 2) }}</span>
                    </div>
                    <div class="product-report-summary-card" style="color:#d97706">
                        <span class="product-report-label">إجمالي المرتجع</span>
                        <span class="product-report-value">{{ number_format($orderAmountType7, 2) }}</span>
                    </div>
                    <div class="product-report-summary-card" style="color:#0f766e">
                        <span class="product-report-label">إجمالي المحصل</span>
                        <span class="product-report-value">{{ number_format($transactionRefType4, 2) }}</span>
                    </div>
                    <div class="product-report-summary-card" style="color:#7c3aed">
                        <span class="product-report-label">الوحدات المحصلة</span>
                        <span class="product-report-value">{{ number_format($collectedUnits) }}</span>
                    </div>
                </div>

                <div class="product-report-status-grid mb-3">
                    @foreach($statusMeta as $key => $meta)
                        <div class="product-report-status-card" style="color:{{ $meta['color'] }}">
                            <span class="product-report-label">{{ $meta['label'] }}</span>
                            <span class="product-report-value">{{ number_format($invoiceStatusCounts[$key] ?? 0) }}</span>
                        </div>
                    @endforeach
                </div>

                <div class="card product-report-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="product-report-section-title">
                            <span><i class="tio-format-points"></i></span>
                            نتائج التقرير
                        </h5>
                        <span class="badge badge-soft-info">{{ number_format($totalRows) }} صف</span>
                    </div>

                    <div class="table-responsive product-report-table-wrap">
                        <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table product-report-table">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>رقم الفاتورة</th>
                                <th>اسم المنتج</th>
                                <th>كود المنتج</th>
                                <th>سعر البيع</th>
                                <th>الكمية</th>
                                <th>الإجمالي</th>
                                <th>البائع</th>
                                <th>العميل</th>
                                <th>المنطقة</th>
                                <th>النوع</th>
                                <th>المبلغ المحصل</th>
                                <th>تاريخ البيع</th>
                                <th>الحالة</th>
                                <th class="none">صورة</th>
                                <th class="none">الفاتورة</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($products as $product)
                                @php
                                    $type = $typeMeta[$product['order_type']] ?? ['label' => 'غير معروف', 'class' => 'product-report-pill--neutral'];
                                    $status = $statusMeta[$product['invoice_status']] ?? ['label' => 'غير معروف', 'class' => 'product-report-pill--neutral'];
                                @endphp
                                <tr>
                                    <td><span class="product-report-index">{{ $loop->iteration + (($orderDetails->firstItem() ?? 1) - 1) }}</span></td>
                                    <td>
                                        <a href="#" class="product-report-invoice" onclick="print_invoice('{{ $product['order_id'] }}'); return false;">
                                            {{ $product['order_id'] }}
                                        </a>
                                    </td>
                                    <td>{{ $product['product_name'] ?: '-' }}</td>
                                    <td>{{ $product['product_code'] ?: '-' }}</td>
                                    <td>{{ number_format((float) $product['selling_price'], 2) }}</td>
                                    <td>{{ number_format((float) $product['quantity']) }}</td>
                                    <td>{{ number_format((float) $product['total_selling_price'], 2) }}</td>
                                    <td>{{ $product['seller'] ?: '-' }}</td>
                                    <td>{{ $product['customer'] ?: '-' }}</td>
                                    <td>{{ $product['region'] ?: '-' }}</td>
                                    <td><span class="product-report-pill {{ $type['class'] }}">{{ $type['label'] }}</span></td>
                                    <td>{{ number_format((float) $product['transaction_reference'], 2) }}</td>
                                    <td>{{ $product['created_at'] }}</td>
                                    <td><span class="product-report-pill {{ $status['class'] }}">{{ $status['label'] }}</span></td>
                                    <td class="none">
                                        @if(!empty($product['img']))
                                            <img src="{{ asset('storage/'.$product['img']) }}" alt="Image Description"
                                                 style="width: 50px; height: auto; cursor: pointer;"
                                                 onclick="openProductReportImage(this.src)">
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="none">
                                        <button class="btn btn-sm btn-white" type="button"
                                                onclick="print_invoice('{{ $product['order_id'] }}')">
                                            <i class="tio-download"></i> الفاتورة
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="16" class="product-report-empty">لا توجد بيانات مطابقة للفلاتر</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="card-footer product-report-pagination">
                        <div class="d-flex justify-content-center justify-content-sm-end">
                            {!! $orderDetails->withQueryString()->links() !!}
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="product-report-image-modal" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">معاينة الصورة</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body text-center">
                            <img id="product-report-image" src="" alt="Image Preview" style="max-width: 100%; height: auto;">
                        </div>
                    </div>
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
    </div>
@endsection

@push('script_2')
    <script>
        "use strict";

        function printTable() {
            var tableContent = document.getElementById('product-table').innerHTML;
            var printWindow = window.open('', '_blank', 'width=900,height=700');

            printWindow.document.write(`
                <!DOCTYPE html>
                <html lang="ar" dir="rtl">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>تقرير مبيعات المنتجات</title>
                    <style>
                        body {
                            font-family: Tahoma, Arial, sans-serif;
                            margin: 0;
                            padding: 24px;
                            background: #fff;
                            color: #102a43;
                            direction: rtl;
                        }
                        .header-section {
                            display: grid;
                            grid-template-columns: 1fr auto 1fr;
                            gap: 16px;
                            align-items: center;
                            border: 1px solid #dbe7f2;
                            border-radius: 8px;
                            padding: 16px;
                            margin-bottom: 18px;
                        }
                        .header-section p {
                            margin: 4px 0;
                            line-height: 1.7;
                            font-size: 13px;
                        }
                        .logo img {
                            max-width: 120px;
                            height: auto;
                        }
                        h2 {
                            margin: 0 0 16px;
                            text-align: center;
                            color: #11245a;
                            font-size: 22px;
                        }
                        .product-report-summary,
                        .product-report-status-grid {
                            display: grid;
                            grid-template-columns: repeat(4, 1fr);
                            gap: 10px;
                            margin-bottom: 16px;
                        }
                        .product-report-summary-card,
                        .product-report-status-card {
                            border: 1px solid #dbe7f2;
                            border-radius: 8px;
                            padding: 10px;
                        }
                        .product-report-label {
                            display: block;
                            color: #60758b;
                            font-size: 12px;
                            margin-bottom: 6px;
                        }
                        .product-report-value {
                            font-size: 18px;
                            font-weight: 800;
                        }
                        .card-header,
                        .product-report-pagination,
                        .admin-table-scroll-proxy,
                        .none {
                            display: none !important;
                        }
                        table {
                            width: 100%;
                            border-collapse: collapse;
                            margin-top: 12px;
                        }
                        th,
                        td {
                            border: 1px solid #dbe7f2;
                            padding: 7px;
                            text-align: right;
                            vertical-align: top;
                            font-size: 11px;
                        }
                        th {
                            background: #11245a;
                            color: #fff;
                        }
                    </style>
                </head>
                <body>
                    <div class="header-section">
                        <div>
                            <p><strong>رقم السجل التجاري:</strong> {{ optional(\App\Models\BusinessSetting::where(["key" => "vat_reg_no"])->first())->value ?? '' }}</p>
                            <p><strong>الرقم الضريبي:</strong> {{ optional(\App\Models\BusinessSetting::where(["key" => "number_tax"])->first())->value ?? '' }}</p>
                            <p><strong>البريد الإلكتروني:</strong> {{ optional(\App\Models\BusinessSetting::where(["key" => "shop_email"])->first())->value ?? '' }}</p>
                        </div>
                        <div class="logo">
                            <img src="{{ asset('storage/shop/' . optional(\App\Models\BusinessSetting::where(['key' => 'shop_logo'])->first())->value) }}" alt="شعار المتجر">
                        </div>
                        <div>
                            <p><strong>اسم المؤسسة:</strong> {{ optional(\App\Models\BusinessSetting::where(["key" => "shop_name"])->first())->value ?? '' }}</p>
                            <p><strong>العنوان:</strong> {{ optional(\App\Models\BusinessSetting::where(["key" => "shop_address"])->first())->value ?? '' }}</p>
                            <p><strong>رقم الجوال:</strong> {{ optional(\App\Models\BusinessSetting::where(["key" => "shop_phone"])->first())->value ?? '' }}</p>
                        </div>
                    </div>
                    <h2>تقرير مبيعات المنتجات</h2>
                    ${tableContent}
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

        function openProductReportImage(src) {
            var image = document.getElementById('product-report-image');
            if (!image) return;

            image.src = src;
            if (window.jQuery && $.fn.modal) {
                $('#product-report-image-modal').modal('show');
            }
        }

        function print_invoice(order_id) {
            $.get({
                url: '{{ url('/') }}/admin/pos/invoice/' + order_id,
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
                    console.log(error);
                }
            });
        }

        $(function () {
            if (window.jQuery && $.fn.selectpicker) {
                $('.selectpicker').selectpicker('refresh');
            }
        });
    </script>

    <script src="{{ asset('public/assets/admin/js/global.js') }}"></script>
    <script>
        "use strict";

        function productReportPrintAssets() {
            return Array.from(document.querySelectorAll('link[rel="stylesheet"], style'))
                .map(function (node) {
                    return node.outerHTML;
                })
                .join('\n');
        }

        function productReportPrintHtml(markup, title, extraCss) {
            var frame = document.createElement('iframe');
            frame.setAttribute('title', title || 'print');
            frame.style.position = 'fixed';
            frame.style.left = '0';
            frame.style.bottom = '0';
            frame.style.width = '1px';
            frame.style.height = '1px';
            frame.style.border = '0';
            frame.style.opacity = '0';
            document.body.appendChild(frame);

            var printWindow = frame.contentWindow;
            var printDocument = printWindow.document;

            printDocument.open();
            printDocument.write(
                '<!doctype html>' +
                '<html lang="ar" dir="rtl">' +
                '<head>' +
                '<meta charset="UTF-8">' +
                '<meta name="viewport" content="width=device-width, initial-scale=1.0">' +
                '<title>' + (title || 'Print') + '</title>' +
                productReportPrintAssets() +
                '<style>' +
                'body{font-family:Tahoma,Arial,sans-serif;margin:0;padding:20px;background:#fff;color:#102a43;direction:rtl;}' +
                '.non-printable,.admin-table-scroll-proxy,.product-report-pagination,.none,.modal-backdrop{display:none!important;}' +
                '.content,.container-fluid,.product-report-page{padding:0!important;margin:0!important;background:#fff!important;}' +
                '.product-report-summary,.product-report-status-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:12px;}' +
                '.product-report-summary-card,.product-report-status-card{border:1px solid #dbe7f2!important;border-radius:8px!important;padding:8px!important;box-shadow:none!important;min-height:auto!important;}' +
                '.product-report-label{display:block;font-size:11px;color:#60758b;margin-bottom:4px;}' +
                '.product-report-value{font-size:16px;font-weight:800;color:#102a43;}' +
                '.card,.product-report-card{border:0!important;box-shadow:none!important;}' +
                '.card-header{display:none!important;}' +
                '.table-responsive,.product-report-table-wrap{overflow:visible!important;border-radius:0!important;}' +
                'table{width:100%!important;min-width:0!important;border-collapse:collapse!important;margin-top:10px!important;}' +
                'th,td{border:1px solid #dbe7f2!important;padding:6px!important;text-align:right!important;vertical-align:top!important;font-size:10px!important;white-space:normal!important;color:#102a43!important;}' +
                'th{background:#11245a!important;color:#fff!important;font-weight:800!important;}' +
                '.product-report-pill,.product-report-index,.product-report-invoice{border-radius:999px!important;padding:3px 7px!important;text-decoration:none!important;}' +
                '@page{size:A4 landscape;margin:8mm;}' +
                '@media print{body{-webkit-print-color-adjust:exact;print-color-adjust:exact;}}' +
                (extraCss || '') +
                '</style>' +
                '</head>' +
                '<body>' + markup + '</body>' +
                '</html>'
            );
            printDocument.close();

            setTimeout(function () {
                printWindow.focus();
                printWindow.print();

                setTimeout(function () {
                    if (frame.parentNode) {
                        frame.parentNode.removeChild(frame);
                    }
                }, 1500);
            }, 400);
        }

        window.printTable = function () {
            var tableNode = document.getElementById('product-table');
            if (!tableNode) {
                return;
            }

            var cloned = tableNode.cloneNode(true);
            cloned.querySelectorAll('.none, .product-report-pagination, .admin-table-scroll-proxy').forEach(function (node) {
                node.remove();
            });

            var header =
                '<div style="border:1px solid #dbe7f2;border-radius:8px;padding:12px;margin-bottom:14px;text-align:center;">' +
                '<h2 style="margin:0;color:#11245a;font-size:20px;">تقرير مبيعات المنتجات</h2>' +
                '<p style="margin:6px 0 0;color:#60758b;font-weight:700;">{{ now()->format('Y-m-d H:i') }}</p>' +
                '</div>';

            productReportPrintHtml(header + cloned.innerHTML, 'تقرير مبيعات المنتجات');
        };

        window.printDiv = function (divName) {
            var printNode = document.getElementById(divName);
            if (!printNode) {
                return;
            }

            productReportPrintHtml(
                printNode.innerHTML,
                'طباعة الفاتورة',
                '.width-inone{direction:rtl!important;}@page{size:auto;margin:4mm;}'
            );
        };
    </script>
@endpush
