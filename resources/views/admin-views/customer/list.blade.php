@extends('layouts.admin.app')

@section('title',\App\CPU\translate('customer_list'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/custom.css"/>
@endpush

@section('content')
<style>
    :root {
        --hp-navy: #001B63;
        --hp-blue: #3a5fa8;
        --hp-blue-soft: #eef2fb;
        --hp-amber: #ffce00;
        --hp-border: #e6eaf2;
        --hp-text: #2b3445;
        --hp-muted: #7a869a;
        --hp-radius: 16px;
        --hp-shadow: 0 8px 28px rgba(0, 27, 99, .07);
        --hp-shadow-hover: 0 12px 34px rgba(0, 27, 99, .13);
    }

    body {
        direction: rtl;
        background: linear-gradient(180deg, #f7f9fc 0%, #eef1f7 100%);
        color: var(--hp-text);
    }

    .content.container-fluid { padding-top: 1.25rem; }

    /* ---------- ترويسة الصفحة ---------- */
    .page-header {
        position: relative;
        background: linear-gradient(120deg, var(--hp-navy) 0%, var(--hp-blue) 100%);
        padding: 1.75rem 2rem;
        border-radius: var(--hp-radius);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
        box-shadow: 0 10px 30px rgba(0, 27, 99, .22);
        margin-bottom: 1.75rem;
        overflow: hidden;
        border: none;
    }
    .page-header::before {
        content: '';
        position: absolute;
        inset: auto -60px -120px auto;
        width: 320px;
        height: 320px;
        background: radial-gradient(circle, rgba(255,255,255,.14) 0%, rgba(255,255,255,0) 70%);
        border-radius: 50%;
        clip-path: none;
    }
    .page-header::after {
        content: '';
        position: absolute;
        inset: -140px auto auto -80px;
        width: 260px;
        height: 260px;
        background: radial-gradient(circle, rgba(255,206,0,.18) 0%, rgba(255,206,0,0) 70%);
        border-radius: 50%;
    }
    .page-header h1 {
        display: flex;
        align-items: center;
        gap: .85rem;
        margin: 0;
        font-size: 1.5rem;
        font-weight: 700;
        color: #fff;
        position: relative;
        z-index: 1;
    }
    .page-header h1 i {
        background: rgba(255, 255, 255, .16);
        border: 1px solid rgba(255, 255, 255, .25);
        color: #fff;
        width: 46px;
        height: 46px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
        border-radius: 14px;
    }
    .page-header .badge-soft-dark {
        background: var(--hp-amber);
        color: var(--hp-navy);
        font-weight: 700;
        font-size: .85rem;
        margin-inline-start: .5rem;
        padding: .35rem .8rem;
        border-radius: 999px;
        box-shadow: 0 4px 12px rgba(255, 206, 0, .35);
    }
    .add-customer-btn {
        background: #fff;
        color: var(--hp-navy);
        padding: .75rem 1.4rem;
        border: none;
        border-radius: 12px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        box-shadow: 0 6px 18px rgba(0, 0, 0, .18);
        transition: transform .18s ease, box-shadow .18s ease, color .18s ease;
        position: relative;
        z-index: 1;
        text-decoration: none;
    }
    .add-customer-btn:hover {
        color: var(--hp-blue);
        transform: translateY(-2px);
        box-shadow: 0 10px 24px rgba(0, 0, 0, .24);
        text-decoration: none;
    }

    /* ---------- بطاقة الفلاتر ---------- */
    .filter-card {
        border: 1px solid var(--hp-border);
        border-radius: var(--hp-radius);
        box-shadow: var(--hp-shadow);
        background: #fff;
        margin-bottom: 1.75rem;
        overflow: hidden;
    }
    .filter-card .card-header {
        background: var(--hp-blue-soft);
        border: none;
        border-bottom: 1px solid var(--hp-border);
        padding: 1rem 1.5rem;
    }
    .filter-card .card-header h5 {
        color: var(--hp-navy) !important;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: .5rem;
    }
    .filter-card .card-body { padding: 1.5rem; }
    .filter-card .form-label {
        font-weight: 600;
        color: var(--hp-muted);
        font-size: .82rem;
        letter-spacing: .2px;
        margin-bottom: .4rem;
    }
    .filter-card .input-group-text {
        background: #fff;
        border: 1px solid var(--hp-border);
        border-inline-end: none;
        border-radius: 12px 0 0 12px;
    }
    .filter-card .form-control,
    .filter-card .form-select,
    .filter-card .btn-white {
        border: 1px solid var(--hp-border);
        border-radius: 12px;
        min-height: 48px;
        padding: .5rem 1rem;
        font-size: .92rem;
        background-color: #fff;
        transition: border-color .18s ease, box-shadow .18s ease;
    }
    .filter-card .input-group .form-control {
        border-inline-start: none;
        border-radius: 0 12px 12px 0;
    }
    .filter-card .form-control:focus,
    .filter-card .form-select:focus {
        border-color: var(--hp-blue);
        box-shadow: 0 0 0 .2rem rgba(58, 95, 168, .15);
    }
    .filter-card .input-group:focus-within .input-group-text {
        border-color: var(--hp-blue);
    }
    .filter-card .btn {
        border-radius: 12px;
        min-height: 48px;
        font-size: .92rem;
        font-weight: 600;
        transition: transform .16s ease, filter .16s ease;
    }
    .filter-card .btn-primary {
        background: linear-gradient(120deg, var(--hp-navy), var(--hp-blue));
        border: none;
        box-shadow: 0 6px 16px rgba(58, 95, 168, .3);
    }
    .filter-card .btn-primary:hover { filter: brightness(1.08); }
    .filter-card .btn-success {
        background: linear-gradient(120deg, #12a37a, #1cc98d);
        border: none;
        box-shadow: 0 6px 16px rgba(28, 201, 141, .28);
    }
    .filter-card .btn-outline-secondary {
        border-color: var(--hp-border);
        color: var(--hp-text);
    }
    .filter-card .btn-outline-secondary:hover {
        background: var(--hp-blue-soft);
        border-color: var(--hp-blue);
        color: var(--hp-navy);
    }
    .filter-card .btn:hover { transform: translateY(-1px); }
    .filter-card hr {
        border-top: 1px dashed var(--hp-border);
        opacity: 1;
        margin: 1.5rem 0 1.25rem;
    }
    .filter-card .dropdown-menu {
        border: 1px solid var(--hp-border);
        border-radius: 12px;
        box-shadow: var(--hp-shadow-hover);
    }
    .filter-card .dropdown-menu label {
        padding: .25rem .4rem;
        cursor: pointer;
    }
    .filter-card .dropdown-menu label:hover {
        background: var(--hp-blue-soft);
        border-radius: 8px;
    }

    /* ---------- الجدول ---------- */
    .card-table {
        background: #fff;
        border: 1px solid var(--hp-border);
        border-radius: var(--hp-radius);
        box-shadow: var(--hp-shadow);
        overflow: hidden;
    }
    .card-table table { margin-bottom: 0; }
    .card-table thead th {
        background: linear-gradient(120deg, var(--hp-navy), var(--hp-blue));
        color: #fff;
        font-weight: 600;
        font-size: .85rem;
        letter-spacing: .3px;
        text-align: center;
        border: none;
        padding: 1rem .75rem;
        white-space: nowrap;
    }
    .card-table tbody td {
        padding: .85rem .75rem;
        border-bottom: 1px solid #f0f2f7;
        font-size: .9rem;
        vertical-align: middle;
    }
    .card-table tbody td.p-5 { padding: .85rem .75rem !important; }
    .card-table tbody tr {
        transition: background .16s ease, box-shadow .16s ease;
    }
    .card-table tbody tr:nth-child(even) { background: #fbfcfe; }
    .card-table tbody tr:hover {
        background: var(--hp-blue-soft);
        box-shadow: inset 3px 0 0 var(--hp-blue);
    }
    .card-table tbody td a.text-primary {
        color: var(--hp-navy) !important;
        font-weight: 600;
        text-decoration: none;
    }
    .card-table tbody td a.text-primary:hover {
        color: var(--hp-blue) !important;
        text-decoration: underline;
    }
    .card-table tfoot th {
        background: var(--hp-blue-soft) !important;
        color: var(--hp-navy);
        border: none;
        padding: .9rem .75rem;
        font-size: .88rem;
    }
    .card-table .btn-sm {
        border-radius: 9px;
        transition: transform .15s ease;
    }
    .card-table .btn-sm:hover { transform: translateY(-1px); }
    .card-table .btn-group .btn-white {
        border: 1px solid var(--hp-border);
        color: var(--hp-blue);
    }
    .card-table .btn-group .btn-white:hover {
        background: var(--hp-blue);
        color: #fff;
        border-color: var(--hp-blue);
    }
    .card-table .btn-info {
        background: var(--hp-blue-soft);
        border: 1px solid var(--hp-border);
        color: var(--hp-blue);
    }
    .card-table .btn-info:hover {
        background: var(--hp-blue);
        border-color: var(--hp-blue);
        color: #fff;
    }
    .toggle-switch { cursor: pointer; }
    .badge {
        font-size: .8rem;
        border-radius: 999px;
        padding: .35rem .7rem;
        font-weight: 600;
    }

    /* ---------- ترقيم الصفحات ---------- */
    .page-link {
        border: 1px solid var(--hp-border);
        color: var(--hp-navy);
        border-radius: 10px !important;
        margin: 0 .15rem;
        min-width: 38px;
        text-align: center;
    }
    .page-item.active .page-link {
        background: linear-gradient(120deg, var(--hp-navy), var(--hp-blue));
        border-color: transparent;
        color: #fff;
    }

    @media (max-width: 767.98px) {
        .page-header { padding: 1.25rem; }
        .page-header h1 { font-size: 1.15rem; }
        .add-customer-btn { width: 100%; justify-content: center; }
        .filter-card .card-body { padding: 1rem; }
    }

    /* ---------- فلتر العملاء ---------- */
    .cf-card { border: 1px solid #e6edf5; border-radius: 18px; overflow: visible; box-shadow: 0 10px 30px -18px rgba(17,36,90,.35); }
    .cf-head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap;
               padding: 1rem 1.5rem; border-bottom: 1px solid #eef3f8;
               background: linear-gradient(180deg, #f8fbff, #fff); border-radius: 18px 18px 0 0; }
    .cf-title { display: flex; align-items: center; gap: .75rem; }
    .cf-title h5 { margin: 0; font-weight: 800; color: #11245a; font-size: 1.05rem; }
    .cf-title small { color: #7a8aa5; }
    .cf-icon { width: 40px; height: 40px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center;
               background: linear-gradient(135deg, #11245a, #2c5cc5); color: #fff; font-size: 1.1rem; flex: none; }
    .cf-reset { color: #d6334b; font-weight: 700; font-size: .85rem; white-space: nowrap; }
    .cf-reset:hover { color: #b0213a; text-decoration: none; }

    .cf-body { padding: 1.25rem 1.5rem 1.5rem; }
    .cf-grid { display: grid; gap: 1rem; grid-template-columns: repeat(6, minmax(0, 1fr)); }
    .cf-field { min-width: 0; display: flex; flex-direction: column; gap: .4rem; }
    .cf-field > label { margin: 0; font-size: .8rem; font-weight: 800; color: #5b6b86; }

    .cf-control { width: 100%; height: 46px; border: 1px solid #dfe7f1; border-radius: 12px; background-color: #fff;
                  padding: 0 .9rem; color: #11245a; font-weight: 600; font-size: .9rem;
                  transition: border-color .15s, box-shadow .15s; }
    .cf-control:focus { outline: none; border-color: #2c5cc5; box-shadow: 0 0 0 4px rgba(44,92,197,.12); }
    select.cf-control { appearance: none; -webkit-appearance: none; padding-left: 2.2rem; text-overflow: ellipsis;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%235b6b86' stroke-width='3'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
        background-repeat: no-repeat; background-position: left .85rem center; }
    .cf-search { position: relative; }
    .cf-search i { position: absolute; top: 50%; right: .9rem; transform: translateY(-50%); color: #2c5cc5; pointer-events: none; }
    .cf-search .cf-control { padding-right: 2.4rem; }

    .cf-region-btn { display: flex; align-items: center; justify-content: space-between; text-align: right; cursor: pointer; }
    .cf-card .dropdown-menu { border-radius: 12px; border: 1px solid #e6edf5; box-shadow: 0 16px 40px -20px rgba(17,36,90,.45); max-width: calc(100vw - 2rem); }

    .cf-actions { display: flex; align-items: center; justify-content: space-between; gap: .75rem; flex-wrap: wrap;
                  margin-top: 1.25rem; padding-top: 1.25rem; border-top: 1px dashed #e3ebf4; }
    .cf-actions-end { display: flex; gap: .6rem; flex-wrap: wrap; }
    .cf-actions-end form { margin: 0; }
    .cf-card .cf-btn { height: 44px; padding: 0 1.25rem; border-radius: 12px; font-weight: 800; display: inline-flex; align-items: center;
              justify-content: center; gap: .45rem; transition: transform .15s, filter .15s; }
    .cf-card .cf-btn:hover { transform: translateY(-1px); }
    .cf-card .cf-btn-primary { min-width: 140px; color: #fff; background: linear-gradient(120deg, #11245a, #2c5cc5); border: 0; }
    .cf-card .cf-btn-primary:hover { color: #fff; filter: brightness(1.08); }
    .cf-card .cf-btn-excel { color: #fff; background: linear-gradient(120deg, #0f9f6e, #22c58b); border: 0; }
    .cf-card .cf-btn-excel:hover { color: #fff; filter: brightness(1.06); }
    .cf-card .cf-btn-ghost { color: #11245a; background: #fff; border: 1px solid #dfe7f1; }
    .cf-card .cf-btn-ghost:hover { background: #f4f8fc; color: #11245a; }

    @media (max-width: 1199.98px) { .cf-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    @media (max-width: 767.98px) {
        .cf-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .cf-field--wide { grid-column: 1 / -1; }
        .cf-head, .cf-body { padding-left: 1rem; padding-right: 1rem; }
    }
    @media (max-width: 479.98px) {
        .cf-grid { grid-template-columns: 1fr; }
        .cf-actions, .cf-actions-end { flex-direction: column; align-items: stretch; }
        .cf-actions-end form, .cf-actions-end .cf-btn, .cf-btn-primary { width: 100%; }
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
{{-- الفلتر شبكة تلتفّ وحدها: خمسة أعمدة على الشاشة الكبيرة، وثلاثة أو
     عمودان على التابلت، وعمود واحد على الهاتف. كانت أعمدة col-md-2 بفئات
     Bootstrap 5 (form-select, g-3) على مشروع Bootstrap 4 فتتداخل الحقول. --}}
<div class="card filter-card cf-card">
    <div class="cf-head">
        <div class="cf-title">
            <span class="cf-icon"><i class="tio-filter-list"></i></span>
            <div>
                <h5>{{ \App\CPU\translate('فلتر العملاء') }}</h5>
                <small>{{ \App\CPU\translate('ابحث وصفِّ قائمة العملاء') }}</small>
            </div>
        </div>
        @if(request()->except('page'))
            <a href="{{ url()->current() }}" class="cf-reset">
                <i class="tio-clear"></i> {{ \App\CPU\translate('مسح الفلاتر') }}
            </a>
        @endif
    </div>

    <div class="cf-body">
        <form action="{{ url()->current() }}" method="GET">
            <div class="cf-grid">
                <div class="cf-field cf-field--wide">
                    <label for="datatableSearch_">{{ \App\CPU\translate('بحث') }}</label>
                    <div class="cf-search">
                        <i class="tio-search"></i>
                        <input id="datatableSearch_" type="search" name="search" class="cf-control"
                               placeholder="{{ \App\CPU\translate('بحث باسم أو كود العميل') }}"
                               value="{{ $search }}">
                    </div>
                </div>

                <div class="cf-field">
                    <label for="seller">{{ \App\CPU\translate('البائع') }}</label>
                    <select name="seller_id" id="seller" class="cf-control">
                        <option value="">{{ \App\CPU\translate('كل البائعين') }}</option>
                        @foreach($sellers as $sellerOption)
                            <option value="{{ $sellerOption->id }}" {{ request('seller_id') == $sellerOption->id ? 'selected' : '' }}>
                                {{ $sellerOption->email }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="cf-field">
                    <label for="specialist">{{ \App\CPU\translate('الفئة') }}</label>
                    <select name="specialist" id="specialist" class="cf-control">
                        <option value="">{{ \App\CPU\translate('كل الفئات') }}</option>
                        <option value="1" {{ request('specialist') == 1 ? 'selected' : '' }}>{{ \App\CPU\translate('صيدلية') }}</option>
                        <option value="2" {{ request('specialist') == 2 ? 'selected' : '' }}>{{ \App\CPU\translate('مركز طبي') }}</option>
                        <option value="3" {{ request('specialist') == 3 ? 'selected' : '' }}>{{ \App\CPU\translate('مستشفى') }}</option>
                        <option value="4" {{ request('specialist') == 4 ? 'selected' : '' }}>{{ \App\CPU\translate('طبيب') }}</option>
                    </select>
                </div>

                {{-- التصنيف A–D: customers.type. الاسم class لا type، فـ type
                     يُستخدم في روابط أخرى بمعنى مختلف. --}}
                <div class="cf-field">
                    <label for="customer_class">{{ \App\CPU\translate('التصنيف') }}</label>
                    <select name="class" id="customer_class" class="cf-control">
                        <option value="">{{ \App\CPU\translate('كل التصنيفات') }}</option>
                        @foreach([1 => 'A', 2 => 'B', 3 => 'C', 4 => 'D'] as $classValue => $classLabel)
                            <option value="{{ $classValue }}" {{ (string) request('class') === (string) $classValue ? 'selected' : '' }}>
                                {{ $classLabel }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- التخصص: أطفال / نسا وتوليد / ... وهو category_id من نوع 0 -->
                <div class="cf-field">
                    <label for="category_id">{{ \App\CPU\translate('التخصص') }}</label>
                    <select name="category_id" id="category_id" class="cf-control">
                        <option value="">{{ \App\CPU\translate('كل التخصصات') }}</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}"
                                {{ (string) request('category_id') === (string) $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- المنطقة: قائمة مطوية بمربعات اختيار، الاسم region_id[] كما هو. --}}
                <div class="cf-field">
                    <label>{{ \App\CPU\translate('المنطقة') }}</label>
                    <div class="dropdown">
                        <button class="cf-control cf-region-btn"
                                type="button" id="regionPicker" data-toggle="dropdown"
                                aria-expanded="false">
                            <span>
                                @if(count($regionIds))
                                    {{ count($regionIds) }} {{ \App\CPU\translate('منطقة') }}
                                @else
                                    {{ \App\CPU\translate('اختر منطقة') }}
                                @endif
                            </span>
                            <i class="tio-chevron-down"></i>
                        </button>

                        <div class="dropdown-menu p-2" aria-labelledby="regionPicker"
                             style="max-height:260px; overflow-y:auto; min-width:260px;">
                            @foreach($regions as $region)
                                <label class="d-block mb-1 small">
                                    <input type="checkbox" name="region_id[]" value="{{ $region->id }}"
                                        {{ in_array((string) $region->id, $regionIds, true) ? 'checked' : '' }}>
                                    {{ $region->name }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="cf-actions">
                <button type="submit" class="btn cf-btn cf-btn-primary">
                    <i class="tio-search"></i> {{ \App\CPU\translate('بحث') }}
                </button>

                <div class="cf-actions-end">
                    <button type="button" class="btn cf-btn cf-btn-ghost" onclick="printTable()">
                        <i class="tio-print"></i> {{ \App\CPU\translate('طباعة') }}
                    </button>
                </div>
            </div>
        </form>

        {{-- التصدير نموذج مستقل يحمل فلاتر الشاشة الحالية، فلا يوضع داخل
             نموذج البحث؛ ينقله السكربت أسفل الصفحة بجانب زرّ الطباعة. --}}
        <div class="cf-export-holder">
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
                <button type="submit" class="btn cf-btn cf-btn-excel">
                    <i class="tio-download-to"></i>
                    {{ \App\CPU\translate('اصدار في اكسل') }}
                </button>
            </form>
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

@push('script_2')
    <script>

        // فتح قائمة المناطق يدويًا: القالب لا يحمّل bootstrap.js مستقلًا،
        // فلا نعتمد على إضافة الـ dropdown وحدها.
        document.addEventListener('DOMContentLoaded', function () {
            var btn = document.getElementById('regionPicker');
            if (!btn) { return; }

            var menu = btn.parentElement.querySelector('.dropdown-menu');

            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                menu.classList.toggle('show');
            });

            // الإبقاء على القائمة مفتوحة أثناء تعليم أكثر من منطقة.
            menu.addEventListener('click', function (e) { e.stopPropagation(); });

            document.addEventListener('click', function () {
                menu.classList.remove('show');
            });
        });
    </script>

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

{{-- كان خارج أي قسم بعد @endsection، فيُطبع في جسم الصفحة
     الخام ويدفع المحتوى كله لأسفل. --}}
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
                            <img src="{{ asset('public/storage/shop/' . optional(\App\Models\BusinessSetting::where(['key' => 'shop_logo'])->first())->value) }}" alt="شعار المتجر">
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
@endpush

@push('script_2')
<script>
    // زرّ التصدير نموذج مستقل؛ يُنقل بجانب الطباعة في شريط الأزرار.
    document.addEventListener('DOMContentLoaded', function () {
        var holder = document.querySelector('.cf-export-holder');
        var end = document.querySelector('.cf-actions-end');
        if (holder && end) {
            while (holder.firstElementChild) end.prepend(holder.firstElementChild);
            holder.remove();
        }
    });
</script>
@endpush
