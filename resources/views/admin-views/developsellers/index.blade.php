@extends('layouts.admin.app')

@section('title', \App\CPU\translate('seller_list'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/custom.css') }}">
    <style>
        .seller-requests-page {
            direction: rtl;
            background: linear-gradient(135deg, rgba(17, 36, 90, .05), rgba(245, 158, 11, .08)), #f4f8fb;
            min-height: calc(100vh - 4rem);
            padding-top: 2rem;
            padding-bottom: 2.5rem;
            color: #102a43;
        }

        .seller-requests-shell {
            display: flex;
            flex-direction: column;
            gap: 1.15rem;
        }

        .seller-requests-hero,
        .seller-requests-card {
            border: 1px solid #d9e6f2;
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 14px 32px rgba(15, 23, 42, .08);
        }

        .seller-requests-hero {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1.35rem 1.5rem;
            background: linear-gradient(135deg, #111857, #1d4ed8);
            color: #fff;
        }

        .seller-requests-title {
            display: flex;
            align-items: center;
            gap: .75rem;
            margin: 0;
            color: #fff;
            font-size: 1.65rem;
            font-weight: 900;
        }

        .seller-requests-title span {
            width: 3rem;
            height: 3rem;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, .14);
        }

        .seller-requests-hero p {
            margin: .4rem 3.75rem 0 0;
            color: rgba(255, 255, 255, .76);
            font-weight: 700;
        }

        .seller-requests-count {
            border-radius: 999px;
            padding: .55rem .9rem;
            background: #facc15;
            color: #111857;
            font-weight: 900;
            white-space: nowrap;
        }

        .seller-requests-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1rem;
            border-bottom: 1px solid #e7eef6;
            background: #fff;
        }

        .seller-requests-search {
            display: flex;
            align-items: stretch;
            width: min(100%, 42rem);
            border: 1px solid #d6e2ef;
            border-radius: 8px;
            overflow: hidden;
            background: #fff;
        }

        .seller-requests-search .input-group-text,
        .seller-requests-search .form-control {
            border: 0;
            background: #fff;
            min-height: 3.1rem;
        }

        .seller-requests-search .form-control {
            font-weight: 800;
        }

        .seller-requests-search-btn,
        .seller-requests-add-btn,
        .seller-requests-approve-btn {
            border: 0;
            border-radius: 8px;
            min-height: 3.1rem;
            padding: .75rem 1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .45rem;
            font-weight: 900;
            white-space: nowrap;
        }

        .seller-requests-search-btn {
            border-radius: 0;
            background: #11245a;
            color: #fff;
        }

        .seller-requests-add-btn {
            background: #0f9f6e;
            color: #fff;
            text-decoration: none;
        }

        .seller-requests-add-btn:hover,
        .seller-requests-search-btn:hover,
        .seller-requests-approve-btn:hover {
            color: #fff;
            text-decoration: none;
            filter: brightness(.96);
        }

        .seller-requests-approve-btn {
            min-height: 2.35rem;
            padding: .45rem .7rem;
            background: #11245a;
            color: #fff;
            font-size: .82rem;
        }

        .seller-requests-table {
            margin: 0;
            color: #506882;
        }

        .seller-requests-table thead th {
            border: 0;
            background: #11245a;
            color: #fff;
            padding: 1rem;
            font-weight: 900;
            white-space: nowrap;
            text-align: right;
        }

        .seller-requests-table tbody td {
            border-top: 1px solid #e7eef6;
            padding: .95rem 1rem;
            vertical-align: middle;
            font-weight: 700;
        }

        .seller-requests-table tbody tr:hover {
            background: #f8fbff;
        }

        .seller-name {
            color: #132f52;
            font-weight: 900;
        }

        .seller-muted {
            color: #71869c;
            font-size: .82rem;
            font-weight: 800;
        }

        .seller-note {
            max-width: 28rem;
            white-space: normal;
            line-height: 1.65;
        }

        .seller-status-pill {
            border-radius: 999px;
            padding: .45rem .65rem;
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            background: #dcfce7;
            color: #166534;
            font-weight: 900;
            font-size: .82rem;
        }

        .seller-action-group {
            display: inline-flex;
            gap: .45rem;
        }

        .seller-action-btn {
            width: 2.45rem;
            height: 2.45rem;
            border: 1px solid #dbe6f2;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #52677f;
            background: #fff;
        }

        .seller-action-btn:hover {
            color: #11245a;
            border-color: #b9cbe0;
            background: #f8fbff;
        }

        .seller-action-btn--danger:hover {
            color: #be123c;
            border-color: #fecdd3;
            background: #fff1f2;
        }

        .seller-requests-pagination {
            padding: 1rem;
            border-top: 1px solid #e7eef6;
            background: #fff;
        }

        .seller-requests-empty {
            padding: 3rem 1rem;
            text-align: center;
            color: #71869c;
            font-weight: 800;
        }

        .seller-requests-empty img {
            width: 7rem;
            margin-bottom: 1rem;
        }

        @media (max-width: 767.98px) {
            .seller-requests-hero,
            .seller-requests-toolbar {
                flex-direction: column;
                align-items: stretch;
            }

            .seller-requests-hero p {
                margin-right: 0;
            }

            .seller-requests-search {
                width: 100%;
            }
        }
    </style>
@endpush

@section('content')
@php
    $titles = [
        0 => 'قائمة ملاحظات التطوير',
        1 => 'قائمة كورسات التطوير',
        2 => 'طلبات الإجازات',
    ];
    $title = $titles[(int) $type] ?? 'طلبات المناديب';
    $noteTitle = (int) $type === 0 ? 'ملاحظة التطوير' : ((int) $type === 1 ? 'طلب الموظف' : 'طلب الإجازة');
@endphp

<div class="content container-fluid seller-requests-page">
    <div class="seller-requests-shell">
        <section class="seller-requests-hero">
            <div>
                <h1 class="seller-requests-title">
                    <span><i class="tio-filter-list"></i></span>
                    {{ $title }}
                </h1>
                <p>متابعة طلبات المناديب والرد عليها من شاشة منظمة وسهلة القراءة.</p>
            </div>
            <div class="seller-requests-count">{{ number_format($developSellers->total()) }} طلب</div>
        </section>

        <section class="seller-requests-card">
            <div class="seller-requests-toolbar">
                <form action="{{ url()->current() }}" method="GET" class="seller-requests-search">
                    <span class="input-group-text"><i class="tio-search"></i></span>
                    <input
                        id="datatableSearch_"
                        type="search"
                        name="search"
                        class="form-control"
                        placeholder="بحث بالاسم أو الإيميل"
                        aria-label="Search"
                        value="{{ request('search') }}">
                    <button type="submit" class="seller-requests-search-btn">
                        <i class="tio-search"></i>
                        بحث
                    </button>
                </form>

                @if((int) $type === 0)
                    <a href="{{ route('admin.developsellers.create', ['type' => 0]) }}" class="seller-requests-add-btn">
                        <i class="tio-add-circle"></i>
                        إضافة ملاحظة للتطوير
                    </a>
                @endif
            </div>

            <div class="table-responsive">
                <table class="table seller-requests-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>الاسم</th>
                            <th>الإيميل</th>
                            <th>المدير</th>
                            <th>{{ $noteTitle }}</th>
                            @if((int) $type === 2)
                                <th>تاريخ الإجازة</th>
                                <th>حالة الإجازة</th>
                            @endif
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($developSellers as $key => $seller)
                            <tr>
                                <td>{{ $developSellers->firstItem() + $key }}</td>
                                <td>
                                    <div class="seller-name">{{ trim(optional($seller->sellers)->f_name . ' ' . optional($seller->sellers)->l_name) ?: '-' }}</div>
                                    <div class="seller-muted">مندوب</div>
                                </td>
                                <td>{{ optional($seller->sellers)->email ?? '-' }}</td>
                                <td>{{ optional($seller->admins)->email ?? '-' }}</td>
                                <td class="seller-note">{{ $seller->note ?: '-' }}</td>
                                @if((int) $type === 2)
                                    <td>{{ $seller->date ?: '-' }}</td>
                                    <td>
                                        @if($seller->active == 0)
                                            <form action="{{ route('admin.developsellers.status', $seller->id) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <button type="submit" class="seller-requests-approve-btn">
                                                    <i class="tio-done"></i>
                                                    موافقة
                                                </button>
                                            </form>
                                        @elseif($seller->active == 1)
                                            <span class="seller-status-pill">
                                                <i class="tio-done"></i>
                                                تمت الموافقة
                                            </span>
                                        @endif
                                    </td>
                                @endif
                                <td>
                                    <div class="seller-action-group">
                                        <a class="seller-action-btn" href="{{ route('admin.developsellers.edit', $seller->id) }}" title="تعديل">
                                            <span class="tio-edit"></span>
                                        </a>
                                        <a
                                            class="seller-action-btn seller-action-btn--danger"
                                            href="javascript:"
                                            onclick="form_alert('seller-{{ $seller->id }}', '{{ \App\CPU\translate('Want to delete this seller?') }}')"
                                            title="حذف">
                                            <span class="tio-delete"></span>
                                        </a>
                                        <form
                                            action="{{ route('admin.developsellers.destroy', $seller->id) }}"
                                            method="post"
                                            id="seller-{{ $seller->id }}">
                                            @csrf
                                            @method('delete')
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ (int) $type === 2 ? 8 : 6 }}">
                                    <div class="seller-requests-empty">
                                        <img src="{{ asset('public/assets/admin/svg/illustrations/sorry.svg') }}" alt="{{ \App\CPU\translate('Image Description') }}">
                                        <div>{{ \App\CPU\translate('No_data_to_show') }}</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="seller-requests-pagination">
                {!! $developSellers->links() !!}
            </div>
        </section>
    </div>
</div>
@endsection

@push('script_2')
    <script src="{{ asset('public/assets/admin/js/global.js') }}"></script>
@endpush
