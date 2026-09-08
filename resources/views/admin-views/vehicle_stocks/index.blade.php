@extends('layouts.admin.app')

@section('title', 'قائمة مخزون السيارات')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/custom.css') }}"/>
    <style>
        .vehicle-stock-page {
            background: #f4f8fb;
            min-height: calc(100vh - 70px);
            padding-top: 24px;
            padding-bottom: 34px;
        }
        .vsl-shell {
            max-width: 1500px;
            margin: 0 auto;
        }
        .vsl-hero,
        .vsl-filter-card,
        .vsl-stat,
        .vsl-panel {
            background: #fff;
            border: 1px solid #d9e6f2;
            border-radius: 8px;
            box-shadow: 0 12px 30px rgba(22, 48, 76, .08);
        }
        .vsl-hero {
            margin-bottom: 16px;
            padding: 18px 20px;
        }
        .vsl-title {
            color: #142f51;
            font-size: 24px;
            font-weight: 800;
            margin: 0;
        }
        .vsl-subtitle {
            color: #60758c;
            font-size: 13px;
            margin: 6px 0 0;
        }
        .vsl-actions,
        .vsl-filter-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        .vsl-filter-card {
            margin-bottom: 16px;
            padding: 16px;
        }
        .vsl-filter-card label {
            color: #17365f;
            font-size: 12px;
            font-weight: 800;
            margin-bottom: 7px;
        }
        .vsl-filter-card .form-control {
            border-color: #d8e5f2;
            border-radius: 7px;
            height: 44px;
        }
        .vsl-stat {
            min-height: 112px;
            overflow: hidden;
            padding: 15px;
            position: relative;
        }
        .vsl-stat::before {
            content: "";
            position: absolute;
            inset-inline-start: 0;
            top: 0;
            width: 5px;
            height: 100%;
            background: #2563eb;
        }
        .vsl-stat.vsl-green::before { background: #16a34a; }
        .vsl-stat.vsl-amber::before { background: #f59e0b; }
        .vsl-stat.vsl-purple::before { background: #7c3aed; }
        .vsl-stat-label {
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .vsl-stat-value {
            color: #102f51;
            font-size: 24px;
            font-weight: 800;
            line-height: 1.2;
        }
        .vsl-stat-note {
            color: #7890a8;
            font-size: 12px;
            margin-top: 7px;
        }
        .vsl-panel {
            overflow: hidden;
        }
        .vsl-panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 16px;
            border-bottom: 1px solid #d9e6f2;
            background: linear-gradient(135deg, #eef6fb 0%, #fff 100%);
        }
        .vsl-panel-title {
            color: #17365f;
            font-size: 18px;
            font-weight: 800;
            margin: 0;
        }
        .vsl-panel-kicker {
            color: #637992;
            font-size: 12px;
            margin: 4px 0 0;
        }
        .vsl-table-wrap {
            overflow: auto;
        }
        .vsl-table {
            border-collapse: separate;
            border-spacing: 0;
            margin: 0;
            min-width: 1120px;
            width: 100%;
        }
        .vsl-table th,
        .vsl-table td {
            border-bottom: 1px solid #dbe7f3;
            border-left: 1px solid #dbe7f3;
            padding: 12px 12px;
            text-align: center;
            vertical-align: middle;
            white-space: nowrap;
        }
        .vsl-table thead th {
            background: #17365f;
            color: #fff;
            font-size: 12px;
            font-weight: 800;
            position: sticky;
            top: 0;
            z-index: 2;
        }
        .vsl-table tbody tr:nth-child(even) td {
            background: #f7fbff;
        }
        .vsl-table tbody tr:hover td {
            background: #eef7ff;
        }
        .vsl-product {
            color: #15345b;
            font-weight: 800;
            text-align: right !important;
            min-width: 170px;
        }
        .vsl-muted {
            color: #7a8da3;
            font-size: 12px;
            font-weight: 600;
        }
        .vsl-seller {
            color: #15345b;
            font-weight: 800;
            text-align: right !important;
        }
        .vsl-status {
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 76px;
            padding: 4px 10px;
            font-size: 12px;
            font-weight: 800;
        }
        .vsl-status-good {
            background: #dcfce7;
            color: #166534;
        }
        .vsl-status-empty {
            background: #fee2e2;
            color: #991b1b;
        }
        .vsl-action-group {
            display: flex;
            gap: 7px;
            justify-content: center;
        }
        .vsl-icon-btn {
            width: 38px;
            height: 38px;
            border: 1px solid #dbe7f3;
            border-radius: 7px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #526f8e;
            background: #fff;
        }
        .vsl-icon-btn:hover {
            background: #eef7ff;
            color: #17365f;
            text-decoration: none;
        }
        .vsl-icon-danger:hover {
            background: #fff1f2;
            color: #be123c;
        }
        .vsl-empty {
            padding: 46px 16px;
            text-align: center;
        }
        .vsl-empty img {
            max-width: 140px;
            margin-bottom: 12px;
        }
        .page-area {
            padding: 12px 16px;
        }
        @media (max-width: 767.98px) {
            .vsl-title {
                font-size: 19px;
            }
            .vsl-actions,
            .vsl-filter-actions {
                justify-content: flex-start;
            }
            .vsl-panel-header {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $qty = fn ($value) => number_format((float) $value, 0);
        $money = fn ($value) => number_format((float) $value, 2) . ' ' . \App\CPU\Helpers::currency_symbol();
        $firstItem = $stocks->firstItem() ?? 1;
    @endphp

    <div class="content container-fluid vehicle-stock-page" dir="rtl">
        <div class="vsl-shell">
            <div class="vsl-hero">
                <div class="row align-items-center">
                    <div class="col-lg-7 mb-3 mb-lg-0">
                        <h1 class="vsl-title">قائمة مخزون السيارات</h1>
                        <p class="vsl-subtitle">متابعة أرصدة المنتجات مع كل مندوب وسيارة، مع البحث والتصفية والتصدير.</p>
                    </div>
                    <div class="col-lg-5">
                        <div class="vsl-actions">
                            <a href="{{ route('admin.stock.export', request()->query()) }}" class="btn btn-success">
                                <i class="tio-file-outlined"></i> تصدير CSV
                            </a>
                            <a href="{{ route('admin.stock.create') }}" class="btn btn-primary">
                                <i class="tio-add-circle"></i> إضافة مخزون جديد
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <form action="{{ url()->current() }}" method="GET" class="vsl-filter-card">
                <div class="row align-items-end">
                    <div class="col-lg-3 col-md-6 mb-3">
                        <label for="search">بحث</label>
                        <input type="search" id="search" name="search" class="form-control"
                               value="{{ request('search') }}" placeholder="المنتج أو المندوب أو الكود">
                    </div>

                    <div class="col-lg-3 col-md-6 mb-3">
                        <label for="seller_id">المندوب</label>
                        <select name="seller_id" id="seller_id" class="form-control">
                            <option value="">كل المناديب</option>
                            @foreach (($sellers ?? []) as $seller)
                                <option value="{{ $seller->id }}" {{ (string) request('seller_id') === (string) $seller->id ? 'selected' : '' }}>
                                    {{ trim(($seller->f_name ?? '') . ' ' . ($seller->l_name ?? '')) }}
                                    @if ($seller->mandob_code) ({{ $seller->mandob_code }}) @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-4 mb-3">
                        <label for="remaining">حالة الرصيد</label>
                        <select name="remaining" id="remaining" class="form-control">
                            <option value="">الكل</option>
                            <option value="yes" {{ request('remaining') === 'yes' ? 'selected' : '' }}>لديه رصيد</option>
                            <option value="no" {{ request('remaining') === 'no' ? 'selected' : '' }}>نفد</option>
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-4 mb-3">
                        <label for="from">من تاريخ</label>
                        <input type="date" id="from" name="from" class="form-control" value="{{ request('from') }}">
                    </div>

                    <div class="col-lg-2 col-md-4 mb-3">
                        <label for="to">إلى تاريخ</label>
                        <input type="date" id="to" name="to" class="form-control" value="{{ request('to') }}">
                    </div>

                    <div class="col-12">
                        <div class="vsl-filter-actions">
                            <a href="{{ url()->current() }}" class="btn btn-outline-secondary">إعادة ضبط</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="tio-filter-list"></i> بحث
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            <div class="row">
                <div class="col-sm-6 col-xl-3 mb-3">
                    <div class="vsl-stat">
                        <div class="vsl-stat-label">عدد السجلات</div>
                        <div class="vsl-stat-value">{{ $qty($stockSummary['rows'] ?? $stocks->total()) }}</div>
                        <div class="vsl-stat-note">{{ $qty($stockSummary['sellers'] ?? 0) }} مندوب</div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3 mb-3">
                    <div class="vsl-stat vsl-green">
                        <div class="vsl-stat-label">إجمالي المصروف</div>
                        <div class="vsl-stat-value">{{ $qty($stockSummary['issued'] ?? 0) }}</div>
                        <div class="vsl-stat-note">{{ $qty($stockSummary['products'] ?? 0) }} منتج</div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3 mb-3">
                    <div class="vsl-stat vsl-amber">
                        <div class="vsl-stat-label">إجمالي المتبقي</div>
                        <div class="vsl-stat-value">{{ $qty($stockSummary['remaining'] ?? 0) }}</div>
                        <div class="vsl-stat-note">{{ $money($stockSummary['remaining_value'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3 mb-3">
                    <div class="vsl-stat vsl-purple">
                        <div class="vsl-stat-label">إجمالي المباع</div>
                        <div class="vsl-stat-value">{{ $qty($stockSummary['sold'] ?? 0) }}</div>
                        <div class="vsl-stat-note">المصروف ناقص المتبقي</div>
                    </div>
                </div>
            </div>

            <div class="vsl-panel">
                <div class="vsl-panel-header">
                    <div>
                        <h2 class="vsl-panel-title">تفاصيل المخزون</h2>
                        <p class="vsl-panel-kicker">يعرض الجدول المنتج والمندوب والسيارة والكميات الحالية لكل سجل.</p>
                    </div>
                    <span class="badge badge-soft-primary">{{ $stocks->total() }} سجل</span>
                </div>

                <div class="vsl-table-wrap">
                    <table class="vsl-table">
                        <thead>
                            <tr>
                                <th>م</th>
                                <th>كود السيارة</th>
                                <th>المندوب</th>
                                <th>كود المندوب</th>
                                <th>المنتج</th>
                                <th>المصروف</th>
                                <th>المتبقي</th>
                                <th>المباع</th>
                                <th>الحالة</th>
                                <th>إجراءات</th>
                            </tr>
                        </thead>

                        <tbody id="set-rows">
                            @forelse($stocks as $key => $stock)
                                @php
                                    $issued = (float) ($stock->main_stock ?? 0);
                                    $remaining = (float) ($stock->stock ?? 0);
                                    $sold = max($issued - $remaining, 0);
                                    $seller = $stock->seller;
                                    $product = $stock->product;
                                @endphp
                                <tr>
                                    <td>{{ $firstItem + $key }}</td>
                                    <td><strong>{{ optional($seller)->vehicle_code ?? '-' }}</strong></td>
                                    <td class="vsl-seller">
                                        {{ trim((optional($seller)->f_name ?? '') . ' ' . (optional($seller)->l_name ?? '')) ?: '-' }}
                                    </td>
                                    <td>{{ optional($seller)->mandob_code ?? '-' }}</td>
                                    <td class="vsl-product">
                                        {{ optional($product)->name ?? '-' }}
                                        @if(optional($product)->product_code)
                                            <div class="vsl-muted">كود المنتج: {{ $product->product_code }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $qty($issued) }}</td>
                                    <td>{{ $qty($remaining) }}</td>
                                    <td>{{ $qty($sold) }}</td>
                                    <td>
                                        @if ($remaining > 0)
                                            <span class="vsl-status vsl-status-good">متاح</span>
                                        @else
                                            <span class="vsl-status vsl-status-empty">نفد</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="vsl-action-group">
                                            @if ($remaining > 0)
                                                <a class="vsl-icon-btn" href="javascript:"
                                                   title="رد للمخزن"
                                                   onclick="askReturn({{ $stock['id'] }}, {{ $remaining }})">
                                                    <span class="tio-undo"></span>
                                                </a>
                                                <form action="{{ route('admin.stock.return', [$stock['id']]) }}"
                                                      method="post" id="return-{{ $stock['id'] }}">
                                                    @csrf
                                                    <input type="hidden" name="quantity" value="">
                                                </form>
                                            @endif

                                            <a class="vsl-icon-btn"
                                               title="تعديل"
                                               href="{{ route('admin.stock.edit', [$stock['id']]) }}">
                                                <span class="tio-edit"></span>
                                            </a>
                                            <a class="vsl-icon-btn vsl-icon-danger" href="javascript:"
                                               title="حذف"
                                               onclick="form_alert('stock-{{ $stock['id'] }}','هل تريد حذف هذا المخزون؟')">
                                                <span class="tio-delete"></span>
                                            </a>
                                            <form action="{{ route('admin.stock.delete', [$stock['id']]) }}"
                                                  method="post" id="stock-{{ $stock['id'] }}">
                                                @csrf @method('delete')
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10">
                                        <div class="vsl-empty">
                                            <img src="{{ asset('public/assets/admin/svg/illustrations/sorry.svg') }}"
                                                 alt="{{ \App\CPU\translate('Image Description') }}">
                                            <p class="mb-0">لا توجد بيانات لعرضها.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($stocks->hasPages())
                    <div class="page-area">
                        {!! $stocks->links() !!}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('script_2')
    <script src="{{ asset('public/assets/admin/js/global.js') }}"></script>
@endpush

@push('script')
    <script>
        function askReturn(id, available) {
            const raw = prompt('الكمية المراد ردها (' + available + ')', available);
            if (raw === null) return;

            const qty = parseFloat(raw);

            if (isNaN(qty) || qty <= 0) {
                alert('أدخل كمية صحيحة');
                return;
            }
            if (qty > available) {
                alert('الكمية أكبر من المتاح (' + available + ')');
                return;
            }

            const form = document.getElementById('return-' + id);
            form.querySelector('input[name="quantity"]').value = qty;
            form.submit();
        }
    </script>
@endpush
