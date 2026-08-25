@extends('layouts.admin.app')

@section('title', 'قائمة أوامر الإنتاج')

@section('content')
<style>
    .table th, .table td {
        vertical-align: middle;
    }

    .badge {
        padding: 6px 12px;
        font-size: 0.9rem;
    }

    .form-section {
        background-color: #f8f9fa;
        border-radius: 0.5rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }

    .form-section h5 {
        margin-bottom: 1rem;
        font-weight: bold;
        color: #224abe;
    }

    .btn-search {
        width: 100%;
        margin-top: 2rem;
    }

    .card-header h3 {
        color: #224abe;
        font-weight: 600;
    }

    .table thead {
        background-color: #224abe;
        color: #fff;
    }

    .table tfoot {
        font-weight: bold;
        background-color: #eef0f4;
    }

    .table tbody tr:hover {
        background-color: #f1f3f5;
    }
</style>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">قائمة أوامر الإنتاج</h3>
    </div>

    <div class="card-body">
        <div class="form-section">
            <h5>بحث وتصفية</h5>
            <form method="GET" action="{{ route('admin.production_orders.index') }}">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label>من تاريخ</label>
                        <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-3">
                        <label>إلى تاريخ</label>
                        <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                    </div>
                    <div class="col-md-3">
                        <label>المصنع</label>
                        <select name="factory_id" class="form-select">
                            <option value="">كل المصانع</option>
                            @foreach($factories as $factory)
                                <option value="{{ $factory->id }}" {{ request('factory_id') == $factory->id ? 'selected' : '' }}>
                                    {{ $factory->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>رقم أمر التوريد</label>
                        <input type="number" name="supply_order_id" class="form-control" value="{{ request('supply_order_id') }}">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-search">
                            <i class="fa fa-search"></i> بحث
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered text-center align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>المسؤول</th>
                        <th>أمر التوريد</th>
                        <th>المصنع</th>
                        <th>الإجمالي</th>
                        <th>المدفوع</th>
                        <th>الحالة</th>
                        <th>تاريخ الإنشاء</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($orders as $order)
                    <tr>
                        <td>{{ $order->id }}</td>
                        <td>{{ $order->admin->f_name }} {{ $order->admin->l_name }}</td>
                        <td>
                            <a href="{{ route('admin.supply_orders.show', $order->supplyOrder->id) }}">
                                {{ $order->supplyOrder->id }}
                            </a>
                        </td>
                        <td>{{ $order->factory->name }}</td>
                        <td>{{ number_format($order->total_cash, 2) }}</td>
                        <td>{{ number_format($order->paid, 2) }}</td>
                        <td>
                            <span class="badge bg-{{ $order->status == 'cash' ? 'success' : 'warning' }}">
                                {{ $order->status == 'cash' ? 'نقدي' : 'آجل' }}
                            </span>
                        </td>
                        <td>{{ $order->created_at->format('Y-m-d') }}</td>
                        <td>
                            <a href="{{ route('admin.production_orders.show', $order->id) }}" class="btn btn-sm btn-outline-info">
                                <i class="fa fa-eye"></i> عرض
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4">الإجمالي</td>
                        <td>{{ number_format($totals['total_cash'], 2) }}</td>
                        <td>{{ number_format($totals['paid'], 2) }}</td>
                        <td colspan="3"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="mt-4 d-flex justify-content-center">
            {{ $orders->withQueryString()->links() }}
        </div>
    </div>
</div>
@endsection
