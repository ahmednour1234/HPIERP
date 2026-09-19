@extends('layouts.admin.app')

@section('title', \App\CPU\translate('الرئيسية'))

@push('css_or_js')
<style>
    :root {
        --hpi-navy:      #14395c;
        --hpi-navy-deep: #0d2840;
        --hpi-blue:      #8ec5ef;
        --hpi-ink:       #1c2b3a;
        --hpi-muted:     #7c8ea1;
        --hpi-line:      #e3ecf4;
    }

    .home-wrap { padding: 1.25rem 1.5rem 2.5rem; }

    /* ---------- الترويسة ---------- */

    .home-hero {
        position: relative;
        overflow: hidden;
        border-radius: 18px;
        padding: clamp(24px, 3vw, 40px);
        margin-bottom: 1.5rem;
        color: #fff;
        background: linear-gradient(135deg, var(--hpi-navy-deep) 0%, var(--hpi-navy) 55%, #1e5280 100%);
        box-shadow: 0 18px 40px rgba(20, 57, 92, .22);
    }

    /* قوس يردّد شكل الشعار، كما في صفحة الدخول. */
    .home-hero::after {
        content: '';
        position: absolute;
        inset-inline-end: -120px;
        bottom: -200px;
        width: 420px;
        height: 420px;
        border-radius: 50%;
        border: 1.5px solid rgba(142, 197, 239, .20);
        pointer-events: none;
    }

    .home-hero h1 {
        font-size: clamp(1.4rem, 2.2vw, 2rem);
        font-weight: 800;
        margin: 0 0 .35rem;
    }

    .home-hero p { margin: 0; color: rgba(255,255,255,.76); font-size: .95rem; }

    .home-hero .hero-mark {
        position: absolute;
        inset-inline-end: clamp(24px, 3vw, 48px);
        top: 50%;
        transform: translateY(-50%);
        width: 92px;
        opacity: .9;
    }

    /* ---------- البطاقات ---------- */

    .stat-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .stat {
        background: #fff;
        border: 1px solid var(--hpi-line);
        border-radius: 14px;
        padding: 1.15rem 1.25rem;
        display: flex;
        align-items: center;
        gap: .9rem;
        transition: transform .15s ease, box-shadow .15s ease;
    }

    .stat:hover { transform: translateY(-2px); box-shadow: 0 12px 26px rgba(20,57,92,.10); }

    .stat-icon {
        width: 46px;
        height: 46px;
        flex: none;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        background: #eaf4fb;
        color: var(--hpi-navy);
    }

    .stat-icon.is-green  { background: #e6f7ef; color: #0f7a4d; }
    .stat-icon.is-amber  { background: #fef4e4; color: #a86412; }
    .stat-icon.is-red    { background: #fdecec; color: #b3261e; }

    .stat-label { font-size: .8rem; color: var(--hpi-muted); margin-bottom: .15rem; }

    .stat-value {
        font-size: 1.35rem;
        font-weight: 800;
        color: var(--hpi-ink);
        line-height: 1.2;
        /* الأرقام تُقرأ يسارًا حتى داخل صفحة عربية. */
        direction: ltr;
        text-align: start;
    }

    .stat-value .unit { font-size: .82rem; font-weight: 600; color: var(--hpi-muted); }

    /* ---------- اللوحات ---------- */

    .home-panel {
        background: #fff;
        border: 1px solid var(--hpi-line);
        border-radius: 14px;
        overflow: hidden;
        height: 100%;
    }

    .home-panel .panel-head {
        padding: .9rem 1.15rem;
        border-bottom: 1px solid var(--hpi-line);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .home-panel .panel-head h2 {
        font-size: .98rem;
        font-weight: 700;
        margin: 0;
        color: var(--hpi-navy);
    }

    .home-panel table { margin: 0; }
    .home-panel td, .home-panel th { padding: .7rem 1.15rem; vertical-align: middle; }
    .home-panel thead th { background: #f6fafd; font-size: .78rem; color: var(--hpi-muted); font-weight: 700; border: 0; }
    .home-panel tbody td { border-top: 1px solid var(--hpi-line); font-size: .87rem; }

    /* ---------- الوصول السريع ---------- */

    .quick-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: .75rem; padding: 1.15rem; }

    .quick {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: .5rem;
        padding: 1rem .6rem;
        border-radius: 12px;
        border: 1px solid var(--hpi-line);
        background: #fbfdff;
        color: var(--hpi-ink);
        font-size: .82rem;
        font-weight: 600;
        text-align: center;
        transition: border-color .15s, transform .15s, color .15s;
    }

    .quick:hover {
        text-decoration: none;
        color: var(--hpi-navy);
        border-color: var(--hpi-blue);
        transform: translateY(-2px);
    }

    .quick i { font-size: 1.4rem; color: var(--hpi-navy); }

    .home-empty { padding: 2.5rem 1rem; text-align: center; color: var(--hpi-muted); font-size: .88rem; }
</style>
@endpush

@section('content')
<div class="home-wrap">

    @php($me = auth()->guard('admin')->user())

    <div class="home-hero">
        <img class="hero-mark d-none d-md-block"
             src="{{ asset('public/assets/admin/img/brand/hpi-mark.png') }}" alt="">
        <h1>{{ \App\CPU\translate('أهلاً') }}، {{ trim(($me->f_name ?? '') . ' ' . ($me->l_name ?? '')) }}</h1>
        <p>{{ \App\CPU\translate('ملخص') }} {{ $summary['month_label'] }}</p>
    </div>

    <div class="stat-grid">
        <div class="stat">
            <div class="stat-icon"><i class="tio-shopping-cart"></i></div>
            <div>
                <div class="stat-label">{{ \App\CPU\translate('مبيعات الشهر') }}</div>
                <div class="stat-value">{{ number_format($summary['sales'], 2) }} <span class="unit">ج.م</span></div>
            </div>
        </div>

        <div class="stat">
            <div class="stat-icon is-green"><i class="tio-wallet"></i></div>
            <div>
                <div class="stat-label">{{ \App\CPU\translate('المحصَّل') }}</div>
                <div class="stat-value">{{ number_format($summary['collected'], 2) }} <span class="unit">ج.م</span></div>
            </div>
        </div>

        <div class="stat">
            <div class="stat-icon is-amber"><i class="tio-time"></i></div>
            <div>
                <div class="stat-label">{{ \App\CPU\translate('المتبقّي') }}</div>
                <div class="stat-value">{{ number_format($summary['remaining'], 2) }} <span class="unit">ج.م</span></div>
            </div>
        </div>

        <div class="stat">
            <div class="stat-icon"><i class="tio-receipt"></i></div>
            <div>
                <div class="stat-label">{{ \App\CPU\translate('عدد الفواتير') }}</div>
                <div class="stat-value">{{ number_format($summary['orders']) }}</div>
            </div>
        </div>

        <div class="stat">
            <div class="stat-icon"><i class="tio-user-big"></i></div>
            <div>
                <div class="stat-label">{{ \App\CPU\translate('العملاء') }}</div>
                <div class="stat-value">{{ number_format($summary['customers']) }}</div>
            </div>
        </div>

        <div class="stat">
            <div class="stat-icon"><i class="tio-group-equal"></i></div>
            <div>
                <div class="stat-label">{{ \App\CPU\translate('المناديب') }}</div>
                <div class="stat-value">{{ number_format($summary['sellers']) }}</div>
            </div>
        </div>

        @if($summary['low_stock'] > 0)
        <div class="stat">
            <div class="stat-icon is-red"><i class="tio-warning"></i></div>
            <div>
                <div class="stat-label">{{ \App\CPU\translate('منتجات تحت الحد') }}</div>
                <div class="stat-value">{{ number_format($summary['low_stock']) }}</div>
            </div>
        </div>
        @endif
    </div>

    <div class="row g-3">
        <div class="col-lg-7 mb-3 mb-lg-0">
            <div class="home-panel">
                <div class="panel-head">
                    <h2><i class="tio-receipt mr-1"></i> {{ \App\CPU\translate('أحدث الفواتير') }}</h2>
                    @cangroup('invoices')
                        <a href="{{ route('admin.pos.orders') }}" class="btn btn-sm btn-outline-primary">
                            {{ \App\CPU\translate('عرض الكل') }}
                        </a>
                    @endcangroup
                </div>

                @if($summary['recent_orders']->isEmpty())
                    <div class="home-empty">{{ \App\CPU\translate('لا توجد فواتير بعد') }}</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ \App\CPU\translate('العميل') }}</th>
                                    <th>{{ \App\CPU\translate('المندوب') }}</th>
                                    <th>{{ \App\CPU\translate('الإجمالي') }}</th>
                                    <th>{{ \App\CPU\translate('التاريخ') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($summary['recent_orders'] as $order)
                                    <tr>
                                        <td>{{ $order->id }}</td>
                                        <td>{{ optional($order->customer)->name ?: '-' }}</td>
                                        <td>{{ trim(optional($order->seller)->f_name . ' ' . optional($order->seller)->l_name) ?: '-' }}</td>
                                        <td dir="ltr">{{ number_format((float) $order->order_amount, 2) }}</td>
                                        <td>{{ optional($order->created_at)->format('Y-m-d') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <div class="col-lg-5">
            <div class="home-panel">
                <div class="panel-head">
                    <h2><i class="tio-flash mr-1"></i> {{ \App\CPU\translate('وصول سريع') }}</h2>
                </div>

                {{-- كل اختصار محروس بصلاحيته، فلا يُعرض رابط يؤدي إلى 403. --}}
                <div class="quick-grid">
                    @cangroup('invoices')
                        <a class="quick" href="{{ route('admin.pos.orders') }}">
                            <i class="tio-receipt"></i> {{ \App\CPU\translate('الفواتير') }}
                        </a>
                    @endcangroup

                    @cangroup('customers')
                        <a class="quick" href="{{ route('admin.customer.list') }}">
                            <i class="tio-user-big"></i> {{ \App\CPU\translate('العملاء') }}
                        </a>
                    @endcangroup

                    @cangroup('products')
                        <a class="quick" href="{{ route('admin.product.list') }}">
                            <i class="tio-shopping-basket"></i> {{ \App\CPU\translate('المنتجات') }}
                        </a>
                    @endcangroup

                    @cangroup('visits')
                        <a class="quick" href="{{ route('admin.visitor.indexresult') }}">
                            <i class="tio-pin"></i> {{ \App\CPU\translate('الزيارات') }}
                        </a>
                    @endcangroup

                    @cangroup('reports')
                        <a class="quick" href="{{ route('admin.reports.monthly-sales') }}">
                            <i class="tio-chart-bar-4"></i> {{ \App\CPU\translate('التقارير') }}
                        </a>
                    @endcangroup

                    @cangroup('accounts')
                        <a class="quick" href="{{ route('admin.account.list') }}">
                            <i class="tio-wallet"></i> {{ \App\CPU\translate('الحسابات') }}
                        </a>
                    @endcangroup
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
