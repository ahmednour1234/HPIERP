@extends('layouts.admin.app')

@section('title',\App\CPU\translate('vehicle_stocks'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/custom.css"/>
    @include('admin-views.roles._tokens')
    <style>
        .vs-search {
            display: flex;
            gap: .4rem;
            align-items: center;
            min-width: 240px;
        }

        .vs-search input {
            flex: 1;
            border: 1px solid rgba(255,255,255,.28);
            background: rgba(255,255,255,.12);
            color: #fff;
            border-radius: 99px;
            padding: .4rem .85rem;
            font-size: .84rem;
        }

        .vs-search input:focus { outline: 0; background: rgba(255,255,255,.2); }

        /* ---------- الشبكة ---------- */

        .vs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(310px, 1fr));
            gap: 1rem;
        }

        .vs-card {
            display: flex;
            flex-direction: column;
            background: #fff;
            border: 1px solid var(--hpi-line);
            border-radius: 14px;
            color: inherit;
            transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
        }

        .vs-card:hover {
            text-decoration: none;
            color: inherit;
            transform: translateY(-2px);
            border-color: var(--hpi-blue);
            box-shadow: 0 12px 26px rgba(20,57,92,.10);
        }

        .vs-head {
            display: flex;
            align-items: center;
            gap: .7rem;
            padding: .9rem 1.05rem;
            border-bottom: 1px solid var(--hpi-line);
            background: #f9fcfe;
            border-top-left-radius: 14px;
            border-top-right-radius: 14px;
        }

        .vs-avatar {
            width: 38px;
            height: 38px;
            flex: none;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.05rem;
            background: #eaf4fb;
            color: var(--hpi-navy);
        }

        .vs-name { font-size: .9rem; font-weight: 700; color: var(--hpi-ink); margin: 0; }

        .vs-code {
            font-size: .72rem;
            color: var(--hpi-muted);
            direction: ltr;
            text-align: start;
        }

        .vs-car {
            font-size: .7rem;
            font-weight: 700;
            color: var(--hpi-navy);
            background: #eaf4fb;
            border-radius: 99px;
            padding: .1rem .5rem;
            white-space: nowrap;
        }

        /* ---------- الأرقام ---------- */

        .vs-money {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: .1rem .8rem;
            padding: .8rem 1.05rem;
        }

        .vs-money .m-row {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: .5rem;
            padding: .22rem 0;
            font-size: .8rem;
        }

        .vs-money .m-label { color: var(--hpi-muted); white-space: nowrap; }

        .vs-money .m-value {
            font-weight: 700;
            color: var(--hpi-ink);
            direction: ltr;
            white-space: nowrap;
        }

        .vs-money .m-value.is-refund { color: #b3261e; }

        .vs-counts {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            border-top: 1px solid var(--hpi-line);
            background: #fbfdff;
            border-bottom-left-radius: 14px;
            border-bottom-right-radius: 14px;
        }

        .vs-counts .c {
            padding: .6rem .3rem;
            text-align: center;
            border-inline-start: 1px solid var(--hpi-line);
        }

        .vs-counts .c:first-child { border-inline-start: 0; }

        .vs-counts .c-value {
            font-size: 1rem;
            font-weight: 800;
            color: var(--hpi-ink);
            line-height: 1.2;
            direction: ltr;
        }

        /* المتبقي معه هو ما يهم أمين المخزن، فيُميَّز. */
        .vs-counts .c.is-stock .c-value { color: #0f7a4d; }

        .vs-counts .c-label {
            font-size: .66rem;
            color: var(--hpi-muted);
            margin-top: .1rem;
        }

        .vs-empty {
            grid-column: 1 / -1;
            padding: 3rem 1rem;
            text-align: center;
            color: var(--hpi-muted);
            background: #fff;
            border: 1px dashed var(--hpi-line);
            border-radius: 14px;
        }

        @media (max-width: 400px) {
            .vs-money { grid-template-columns: 1fr; }
        }
    </style>
@endpush

@section('content')
<div class="roles-wrap">

    <div class="roles-hero">
        <div class="hero-text">
            <h1><i class="tio-truck mr-1"></i> {{\App\CPU\translate('vehicle_stocks')}}</h1>
            <p>
                {{ $cards->count() }} {{ \App\CPU\translate('عربية') }}
                @if($date) &middot; {{ $date }} @endif
            </p>
        </div>

        <form action="{{ url()->current() }}" method="GET" class="hero-actions">
            <div class="vs-search">
                <input type="date" name="search" value="{{ $date }}" required>
                <button type="submit" class="btn btn-solid">
                    <i class="tio-search mr-1"></i> {{\App\CPU\translate('search')}}
                </button>
            </div>

            @if($date)
                <a href="{{ url()->current() }}" class="btn btn-ghost">{{ \App\CPU\translate('reset') }}</a>
            @endif
        </form>
    </div>

    <div class="vs-grid">
        @forelse($cards as $card)
            @php($seller = $card['seller'])

            <a class="vs-card" href="{{ route('admin.stock.products', $seller->id) }}">
                <div class="vs-head">
                    <div class="vs-avatar"><i class="tio-truck"></i></div>

                    <div class="flex-grow-1" style="min-width:0;">
                        <p class="vs-name">{{ trim($seller->f_name . ' ' . $seller->l_name) }}</p>
                        <div class="vs-code">{{ $seller->mandob_code ?: '—' }}</div>
                    </div>

                    @if($card['store_code'])
                        <span class="vs-car" title="{{ $card['store_name'] }}">{{ $card['store_code'] }}</span>
                    @endif
                </div>

                <div class="vs-money">
                    <div class="m-row">
                        <span class="m-label">{{ \App\CPU\translate('total_cash') }}</span>
                        <span class="m-value">{{ number_format($card['cash'], 2) }}</span>
                    </div>

                    <div class="m-row">
                        <span class="m-label">{{ \App\CPU\translate('total_credit') }}</span>
                        <span class="m-value">{{ number_format($card['credit'], 2) }}</span>
                    </div>

                    <div class="m-row">
                        <span class="m-label">{{ \App\CPU\translate('refund_total') }}</span>
                        <span class="m-value is-refund">{{ number_format($card['refunds'], 2) }}</span>
                    </div>

                    <div class="m-row">
                        <span class="m-label">{{ \App\CPU\translate('installment_total') }}</span>
                        <span class="m-value">{{ number_format($card['installment'], 2) }}</span>
                    </div>
                </div>

                <div class="vs-counts">
                    <div class="c">
                        <div class="c-value">{{ number_format($card['orders']) }}</div>
                        <div class="c-label">{{ \App\CPU\translate('order_count') }}</div>
                    </div>

                    <div class="c">
                        <div class="c-value">{{ number_format($card['lines_held']) }}</div>
                        <div class="c-label">{{ \App\CPU\translate('product_count') }}</div>
                    </div>

                    <div class="c">
                        <div class="c-value">{{ number_format($card['sold']) }}</div>
                        <div class="c-label">{{ \App\CPU\translate('المباع') }}</div>
                    </div>

                    <div class="c is-stock">
                        <div class="c-value">{{ number_format($card['in_hand']) }}</div>
                        <div class="c-label">{{ \App\CPU\translate('remain_stock') }}</div>
                    </div>
                </div>
            </a>
        @empty
            <div class="vs-empty">
                <i class="tio-truck" style="font-size:2rem;opacity:.4"></i>
                <p class="mt-2 mb-0">{{ \App\CPU\translate('No_data_to_show') }}</p>
            </div>
        @endforelse
    </div>
</div>
@endsection

@push('script_2')
    <script src={{asset("public/assets/admin/js/global.js")}}></script>
@endpush
