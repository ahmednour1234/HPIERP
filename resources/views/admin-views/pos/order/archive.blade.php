@extends('layouts.admin.app')

@section('title', 'أرشيف الفواتير')

@section('content')
<div class="content container-fluid" dir="rtl">

    <div class="page-header">
        <h1 class="page-header-title">
            <i class="tio-archive"></i>
            {{ \App\CPU\translate('أرشيف الفواتير') }}
        </h1>
        <p class="text-muted mb-0">
            {{ \App\CPU\translate('الأرشفة تُخفي الفاتورة من قائمة الفواتير فقط. تبقى في كل التقارير والأرصدة، ويمكن إعادتها في أي وقت.') }}
        </p>
    </div>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-start border-success shadow-sm">
                <div class="card-body text-center">
                    <div class="text-muted small">{{ \App\CPU\translate('فواتير مؤرشفة') }}</div>
                    <div class="h3 mb-0">{{ number_format($counts['archived']) }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card border-start border-warning shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small">
                                {{ \App\CPU\translate('مؤهَّلة للأرشفة') }}
                                ({{ \App\CPU\translate('محصلة بالكامل أو مرتجعة بالكامل، وأقدم من') }}
                                {{ $minAgeDays }} {{ \App\CPU\translate('يوم') }})
                            </div>
                            <div class="h3 mb-0">{{ number_format($counts['eligible']) }}</div>
                        </div>

                        {{-- الأرشفة الجماعية: تأكيد قبل التنفيذ لأنها تمس عددًا كبيرًا --}}
                        <form action="{{ route('admin.pos.orders.archive.all') }}" method="post"
                              onsubmit="return confirm('سيتم أرشفة {{ $counts['eligible'] }} فاتورة مكتملة. يمكن إعادتها لاحقًا. متابعة؟');">
                            @csrf
                            <div class="form-inline">
                                <label class="mr-2 mb-0">{{ \App\CPU\translate('الأقدم من (يوم)') }}</label>
                                <input type="number" name="min_age_days" min="0" max="3650"
                                       value="{{ $minAgeDays }}" class="form-control mr-2" style="width:100px;">
                                <button type="submit" class="btn btn-warning"
                                        {{ $counts['eligible'] == 0 ? 'disabled' : '' }}>
                                    <i class="tio-archive"></i>
                                    {{ \App\CPU\translate('أرشفة كل المؤهَّلة') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- الفلاتر --}}
    <form method="GET" action="{{ route('admin.pos.orders.archive') }}" class="card card-body mb-4">
        <div class="row">
            <div class="col-md-3 mb-2">
                <label class="form-label">{{ \App\CPU\translate('بحث') }}</label>
                <input type="search" name="search" class="form-control"
                       placeholder="{{ \App\CPU\translate('رقم الفاتورة أو اسم العميل') }}"
                       value="{{ request('search') }}">
            </div>

            <x-multi-filter name="region_id" label="{{ \App\CPU\translate('المنطقة') }}"
                            :options="$regions" :selected="(array) request('region_id', [])" />

            <x-multi-filter name="seller_id" label="{{ \App\CPU\translate('المندوب') }}"
                            :options="$sellers" :selected="(array) request('seller_id', [])"
                            label-key="email" />

            <div class="col-md-3 mb-2">
                <label class="form-label">{{ \App\CPU\translate('من تاريخ') }}</label>
                <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">

                <label class="form-label mt-2">{{ \App\CPU\translate('إلى تاريخ') }}</label>
                <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
            </div>
        </div>

        <div class="mt-2">
            <button type="submit" class="btn btn-primary px-4">{{ \App\CPU\translate('بحث') }}</button>
            <a href="{{ route('admin.pos.orders.archive') }}" class="btn btn-light border px-3">
                {{ \App\CPU\translate('تصفية جديدة') }}
            </a>
            <x-export-button route="admin.pos.orders.archive.export" />
            <a href="{{ route('admin.pos.orders') }}" class="btn btn-outline-secondary px-3">
                {{ \App\CPU\translate('رجوع إلى الفواتير') }}
            </a>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover table-align-middle">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>{{ \App\CPU\translate('رقم الفاتورة') }}</th>
                        <th>{{ \App\CPU\translate('تاريخ الفاتورة') }}</th>
                        <th>{{ \App\CPU\translate('العميل') }}</th>
                        <th>{{ \App\CPU\translate('المنطقة') }}</th>
                        <th>{{ \App\CPU\translate('المندوب') }}</th>
                        <th>{{ \App\CPU\translate('القيمة') }}</th>
                        <th>{{ \App\CPU\translate('المحصل') }}</th>
                        <th>{{ \App\CPU\translate('سبب الأرشفة') }}</th>
                        <th>{{ \App\CPU\translate('تاريخ الأرشفة') }}</th>
                        <th>{{ \App\CPU\translate('إجراءات') }}</th>
                    </tr>
                </thead>

                <tbody>
                @forelse($orders as $key => $order)
                    <tr>
                        <td>{{ $orders->firstItem() + $key }}</td>
                        <td>{{ $order->id }}</td>
                        <td>{{ optional($order->created_at)->format('Y-m-d H:i') }}</td>
                        <td>{{ $order->customer->name ?? '' }}</td>
                        <td>{{ optional($order->customer->regions ?? null)->name ?? '' }}</td>
                        <td>{{ $order->seller->email ?? '' }}</td>
                        <td>{{ number_format((float) $order->order_amount, 2) }}</td>
                        <td>{{ number_format((float) $order->transaction_reference, 2) }}</td>
                        <td>
                            <span class="badge badge-soft-success">{{ $order->archive_reason }}</span>
                        </td>
                        <td>{{ optional($order->archived_at)->format('Y-m-d H:i') }}</td>
                        <td>
                            {{-- التراجع متاح دائمًا: الأرشفة وسم لا حذف --}}
                            <form action="{{ route('admin.pos.orders.unarchive', [$order->id]) }}"
                                  method="post"
                                  onsubmit="return confirm('إعادة الفاتورة {{ $order->id }} إلى قائمة الفواتير؟');">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-white">
                                    <i class="tio-undo"></i> {{ \App\CPU\translate('إعادة') }}
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="text-center p-4 text-muted">
                            {{ \App\CPU\translate('لا توجد فواتير مؤرشفة') }}
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer">
            {!! $orders->links() !!}
        </div>
    </div>
</div>
@endsection
