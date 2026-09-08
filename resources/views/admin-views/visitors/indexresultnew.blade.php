@extends('layouts.admin.app')

@section('title', \App\CPU\translate('الزيارات المنفذة'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/custom.css') }}"/>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/css/select2.min.css" rel="stylesheet"/>
    <style>
        .visitor-results-page {
            direction: rtl;
            min-height: calc(100vh - 4rem);
            padding-top: 2rem;
            padding-bottom: 2.5rem;
            background: linear-gradient(135deg, rgba(17, 36, 90, .05), rgba(20, 184, 166, .08)), #f4f8fb;
            color: #102a43;
        }

        .visitor-results-shell {
            display: flex;
            flex-direction: column;
            gap: 1.15rem;
        }

        .visitor-results-hero,
        .visitor-results-filter,
        .visitor-results-card {
            border: 1px solid #d9e6f2;
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 14px 32px rgba(15, 23, 42, .08);
            overflow: hidden;
        }

        .visitor-results-hero {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1.35rem 1.5rem;
            background: linear-gradient(135deg, #111857, #0f766e);
            color: #fff;
        }

        .visitor-results-title {
            display: flex;
            align-items: center;
            gap: .75rem;
            margin: 0;
            color: #fff;
            font-size: 1.65rem;
            font-weight: 900;
        }

        .visitor-results-title span,
        .visitor-results-section-title span {
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .visitor-results-title span {
            width: 3rem;
            height: 3rem;
            background: rgba(255, 255, 255, .14);
        }

        .visitor-results-hero p {
            margin: .4rem 3.75rem 0 0;
            color: rgba(255, 255, 255, .78);
            font-weight: 700;
        }

        .visitor-results-count {
            border-radius: 999px;
            padding: .55rem .9rem;
            background: #facc15;
            color: #111857;
            font-weight: 900;
            white-space: nowrap;
        }

        .visitor-results-filter .card-header,
        .visitor-results-card .card-header {
            border-bottom: 1px solid #e7eef6;
            background: linear-gradient(90deg, #f8fbff, #eef7fb);
            padding: 1rem 1.15rem;
        }

        .visitor-results-section-title {
            display: flex;
            align-items: center;
            gap: .65rem;
            margin: 0;
            color: #132f52;
            font-weight: 900;
        }

        .visitor-results-section-title span {
            width: 2.4rem;
            height: 2.4rem;
            background: #11245a;
            color: #fff;
        }

        .visitor-results-filter .card-body {
            padding: 1.25rem;
        }

        .visitor-results-filter label {
            color: #52677f;
            font-weight: 900;
            margin-bottom: .45rem;
        }

        .visitor-results-filter .form-control,
        .visitor-results-filter .select2-container--default .select2-selection--single,
        .visitor-results-filter .select2-container--default .select2-selection--multiple {
            min-height: 3.15rem;
            border: 1px solid #d5e2ef !important;
            border-radius: 8px !important;
            background: #fff;
            color: #102a43;
            font-weight: 800;
            box-shadow: 0 8px 18px rgba(15, 23, 42, .04);
        }

        .visitor-results-filter .select2-container {
            width: 100% !important;
        }

        .visitor-results-filter .select2-selection--single .select2-selection__rendered {
            line-height: 3.05rem;
            padding-right: 1rem;
            padding-left: 2rem;
            color: #102a43;
        }

        .visitor-results-filter .select2-selection--single .select2-selection__arrow {
            height: 3rem;
            left: .75rem;
            right: auto;
        }

        .visitor-results-filter .select2-selection--multiple {
            padding: .35rem .55rem;
        }

        .visitor-results-filter .select2-selection--multiple .select2-selection__choice {
            border: 0;
            border-radius: 999px;
            background: #e0f2fe;
            color: #075985;
            padding: .25rem .6rem;
            font-weight: 900;
        }

        .visitor-results-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: .65rem;
            border-top: 1px solid #e7eef6;
            padding: 1rem 1.25rem;
            background: #fbfdff;
        }

        .visitor-results-actions-group {
            display: flex;
            flex-wrap: wrap;
            gap: .65rem;
        }

        .visitor-results-btn {
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

        .visitor-results-summary {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1rem;
        }

        .visitor-results-summary-card {
            min-height: 6.5rem;
            border: 1px solid #dbe7f2;
            border-radius: 8px;
            background: #fff;
            padding: 1rem;
            box-shadow: 0 10px 22px rgba(15, 23, 42, .05);
            position: relative;
            overflow: hidden;
        }

        .visitor-results-summary-card::after {
            content: "";
            position: absolute;
            inset: auto auto -2.75rem -2.75rem;
            width: 6rem;
            height: 6rem;
            border-radius: 50%;
            background: currentColor;
            opacity: .1;
        }

        .visitor-results-summary-label {
            display: block;
            color: #60758b;
            font-size: .82rem;
            font-weight: 900;
            margin-bottom: .55rem;
        }

        .visitor-results-summary-value {
            color: #102a43;
            font-size: 1.35rem;
            font-weight: 900;
        }

        .visitor-results-table-wrap {
            overflow-x: auto !important;
            overflow-y: hidden;
            border-radius: 8px;
        }

        .visitor-results-table {
            min-width: 78rem;
            margin: 0;
        }

        .visitor-results-table thead th {
            border: 0;
            background: #11245a;
            color: #fff;
            padding: 1rem;
            font-weight: 900;
            text-align: right;
            white-space: nowrap;
        }

        .visitor-results-table tbody td {
            border-top: 1px solid #e7eef6;
            padding: 1rem;
            color: #506882;
            font-weight: 800;
            vertical-align: middle;
        }

        .visitor-results-table tbody tr:hover {
            background: #f8fbff;
        }

        .visitor-results-index {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 2.5rem;
            border-radius: 999px;
            padding: .35rem .65rem;
            background: #e0f2fe;
            color: #075985;
            font-weight: 900;
        }

        .visitor-results-note {
            max-width: 32rem;
            white-space: normal;
            line-height: 1.8;
        }

        .visitor-results-map-btn {
            border-radius: 8px !important;
            font-weight: 900;
            white-space: nowrap;
        }

        .visitor-results-empty {
            padding: 3rem 1rem;
            text-align: center;
            color: #71869c;
            font-weight: 800;
        }

        .visitor-results-pagination {
            border-top: 1px solid #e7eef6;
            background: #fff;
        }

        @media (max-width: 1199.98px) {
            .visitor-results-summary {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 767.98px) {
            .visitor-results-page {
                padding-top: 1rem;
            }

            .visitor-results-hero {
                flex-direction: column;
                align-items: stretch;
            }

            .visitor-results-hero p {
                margin-right: 0;
            }

            .visitor-results-summary {
                grid-template-columns: 1fr;
            }

            .visitor-results-actions {
                flex-direction: column-reverse;
            }

            .visitor-results-actions-group,
            .visitor-results-btn {
                width: 100%;
            }
        }

        @media print {
            .visitor-results-filter,
            .visitor-results-hero,
            .visitor-results-pagination,
            .non-printable,
            .admin-table-scroll-proxy {
                display: none !important;
            }

            .visitor-results-page,
            .visitor-results-card {
                background: #fff;
                box-shadow: none;
                border: 0;
                padding: 0;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $totalVisitors = method_exists($visitors, 'total') ? $visitors->total() : count($visitors);
        $selectedRegionsCount = count((array) $regionIds);
    @endphp

    <div class="content container-fluid visitor-results-page">
        <div class="visitor-results-shell">
            <section class="visitor-results-hero">
                <div>
                    <h1 class="visitor-results-title">
                        <span><i class="tio-user-switch"></i></span>
                        الزيارات المنفذة
                    </h1>
                    <p>بحث ومراجعة زيارات العملاء حسب المندوب، المنطقة، التخصص، والفترة الزمنية.</p>
                </div>
                <div class="visitor-results-count">{{ number_format($totalVisitors) }} زيارة</div>
            </section>

            <div class="card visitor-results-filter">
                <div class="card-header">
                    <h5 class="visitor-results-section-title">
                        <span><i class="tio-filter-list"></i></span>
                        بحث وتصفية
                    </h5>
                </div>

                <form method="GET" action="{{ route('admin.visitor.indexresult') }}">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-4 col-md-6 mb-3">
                                <label for="customer">العميل</label>
                                <input type="text" name="customer" id="customer" class="form-control"
                                       placeholder="اكتب اسم العميل أو رقم الموبايل"
                                       value="{{ request('customer') }}">
                            </div>

                            <div class="col-lg-4 col-md-6 mb-3">
                                <label for="seller_id">المندوب</label>
                                <select name="seller_id" id="seller_id" class="form-control visitor-select2">
                                    <option value="">كل المناديب</option>
                                    @foreach($sellers as $s)
                                        <option value="{{ $s->id }}" {{ request('seller_id') == $s->id ? 'selected' : '' }}>
                                            {{ trim($s->f_name . ' ' . $s->l_name) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-lg-4 col-md-6 mb-3">
                                <label for="category_id">التخصص</label>
                                <select name="category_id" id="category_id" class="form-control visitor-select2">
                                    <option value="">كل التخصصات</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}"
                                            {{ (string) request('category_id') === (string) $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-lg-4 col-md-6 mb-3">
                                <label for="date_from">من تاريخ</label>
                                <input type="date" name="date_from" id="date_from" class="form-control"
                                       value="{{ request('date_from') }}">
                            </div>

                            <div class="col-lg-4 col-md-6 mb-3">
                                <label for="date_to">إلى تاريخ</label>
                                <input type="date" name="date_to" id="date_to" class="form-control"
                                       value="{{ request('date_to') }}">
                            </div>

                            <div class="col-lg-4 col-md-6 mb-3">
                                <label for="region_id">المنطقة</label>
                                <select name="region_id[]" id="region_id" class="form-control visitor-select2" multiple>
                                    @foreach($regions as $region)
                                        <option value="{{ $region->id }}"
                                            {{ in_array((string) $region->id, array_map('strval', $regionIds), true) ? 'selected' : '' }}>
                                            {{ $region->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="visitor-results-actions">
                        <div class="visitor-results-actions-group">
                            <a href="{{ route('admin.visitor.indexresult.export', request()->query()) }}"
                               class="btn btn-success visitor-results-btn">
                                <i class="tio-download-to"></i> تصدير إكسل
                            </a>
                            <button type="button" class="btn btn-outline-secondary visitor-results-btn" onclick="printTable()">
                                <i class="tio-print"></i> طباعة
                            </button>
                        </div>
                        <div class="visitor-results-actions-group">
                            <a href="{{ route('admin.visitor.indexresult') }}" class="btn btn-outline-secondary visitor-results-btn">
                                إعادة تعيين
                            </a>
                            <button type="submit" class="btn btn-primary visitor-results-btn">
                                <i class="tio-filter-list"></i> بحث
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <div id="product-table">
                <div class="visitor-results-summary mb-3">
                    <div class="visitor-results-summary-card" style="color:#11245a">
                        <span class="visitor-results-summary-label">إجمالي نتائج البحث</span>
                        <span class="visitor-results-summary-value">{{ number_format($totalVisitors) }}</span>
                    </div>
                    <div class="visitor-results-summary-card" style="color:#0f9f6e">
                        <span class="visitor-results-summary-label">إجمالي زيارات العميل</span>
                        <span class="visitor-results-summary-value">{{ isset($customerTotal) ? number_format($customerTotal) : '-' }}</span>
                    </div>
                    <div class="visitor-results-summary-card" style="color:#d97706">
                        <span class="visitor-results-summary-label">المناطق المختارة</span>
                        <span class="visitor-results-summary-value">{{ number_format($selectedRegionsCount) }}</span>
                    </div>
                </div>

                <div class="card visitor-results-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="visitor-results-section-title">
                            <span><i class="tio-format-points"></i></span>
                            سجل الزيارات
                        </h5>
                        @isset($sellerTotal)
                            <span class="badge badge-soft-success">زيارات المندوب: {{ number_format($sellerTotal) }}</span>
                        @endisset
                    </div>

                    <div class="table-responsive visitor-results-table-wrap">
                        <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table visitor-results-table">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>العميل</th>
                                <th>المندوب</th>
                                <th>الملاحظة</th>
                                <th>الموقع</th>
                                <th>تاريخ الإنشاء</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($visitors as $index => $v)
                                <tr>
                                    <td><span class="visitor-results-index">{{ $index + $visitors->firstItem() }}</span></td>
                                    <td>{{ $v->customer->name ?? '-' }}</td>
                                    <td>{{ trim(optional($v->seller)->f_name . ' ' . optional($v->seller)->l_name) ?: '-' }}</td>
                                    <td class="visitor-results-note">{{ $v->note ?: '-' }}</td>
                                    <td>
                                        @if($v->lat && $v->lang)
                                            <a href="https://www.google.com/maps?q={{ $v->lat }},{{ $v->lang }}"
                                               target="_blank"
                                               class="btn btn-sm btn-outline-primary visitor-results-map-btn">
                                                <i class="tio-map"></i> عرض الخريطة
                                            </a>
                                        @else
                                            <span class="text-muted">لا توجد إحداثيات</span>
                                        @endif
                                    </td>
                                    <td>{{ $v->created_at->format('d M Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="visitor-results-empty">لا توجد سجلات</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="card-footer visitor-results-pagination">
                        <div class="d-flex justify-content-center justify-content-sm-end">
                            {{ $visitors->withQueryString()->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
<script>
    function printTable() {
        const tableContent = document.getElementById('product-table').innerHTML;
        const printWindow = window.open('', '_blank', 'width=900,height=700');

        printWindow.document.write(`
            <!DOCTYPE html>
            <html lang="ar" dir="rtl">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>تقرير الزيارات المنفذة</title>
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

                    .visitor-results-summary {
                        display: grid;
                        grid-template-columns: repeat(3, 1fr);
                        gap: 10px;
                        margin-bottom: 16px;
                    }

                    .visitor-results-summary-card {
                        border: 1px solid #dbe7f2;
                        border-radius: 8px;
                        padding: 10px;
                    }

                    .visitor-results-summary-label {
                        display: block;
                        color: #60758b;
                        font-size: 12px;
                        margin-bottom: 6px;
                    }

                    .visitor-results-summary-value {
                        font-size: 18px;
                        font-weight: 800;
                    }

                    .card-header,
                    .visitor-results-actions,
                    .visitor-results-pagination,
                    .admin-table-scroll-proxy {
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
                        padding: 8px;
                        text-align: right;
                        vertical-align: top;
                        font-size: 12px;
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
                <h2>تقرير الزيارات المنفذة</h2>
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
</script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/js/select2.min.js"></script>
    <script>
        $(function () {
            $('.visitor-select2').select2({
                width: '100%',
                dir: 'rtl',
                placeholder: 'اختر',
                allowClear: true
            });
        });
    </script>
@endpush
