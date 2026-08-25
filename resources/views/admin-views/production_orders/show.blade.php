@extends('layouts.admin.app')

@section('title', 'تفاصيل أمر الإنتاج')

@section('content')
<style>
    .h1, .h2, .h3, .h4, .h5, .h6, h1, h2, h3, h4, h5, h6 {
        color: #fff;
    }
</style>

<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h4>تفاصيل أمر الإنتاج #{{ $order->id }}</h4>
    </div>
    <div class="card-body">
        <p><strong>المسؤول:</strong> {{ $order->admin->f_name }} {{ $order->admin->l_name }}</p>
        <p><strong>المصنع:</strong> {{ $order->factory->name }}</p>
        <p><strong>رقم أمر التوريد:</strong> {{ $order->supplyOrder->id }}</p>
        <p><strong>الحالة:</strong> {{ $order->status == 'cash' ? 'نقدي' : 'آجل' }}</p>
        <p><strong>الإجمالي:</strong> {{ number_format($order->total_cash, 2) }} جنيه</p>
        <p><strong>المدفوع:</strong> {{ number_format($order->paid, 2) }} جنيه</p>
        <p><strong>تاريخ الإنشاء:</strong> {{ $order->created_at->format('Y-m-d') }}</p>
    </div>
</div>

@foreach($order->products as $p)
    <div class="card mb-4">
        <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">المنتج: {{ $p->product->name ?? '-' }}</h5>
            <span>الرمز: {{ $p->batch_number }}</span>
        </div>
        <div class="card-body">
            <p><strong>الكمية المطلوبة:</strong> {{ $p->target_quantity }}</p>
            <p><strong>الكمية المنتجة:</strong> {{ $p->produced_quantity }}</p>
            <p><strong>سعر التكلفة:</strong> {{ number_format($p->cost_price, 2) }} جنيه</p>
            <p><strong>التكلفة الإضافية:</strong> {{ number_format($p->additional_cost_price, 2) }} جنيه</p>
            <p><strong>تاريخ الإنتاج:</strong> من {{ $p->production_date }} إلى {{ $p->end_date }}</p>
        </div>

        @if($p->components && $p->components->count())
            <div class="card-body border-top mt-3">
                <h6 class="text-info">المكونات المستخدمة:</h6>
                <div class="table-responsive">
                    <table class="table table-bordered text-center">
                        <thead class="table-light">
                            <tr>
                                <th>المادة</th>
                                <th>الكمية المستخدمة</th>
                                <th>الوحدة</th>
                                <th>الهالك</th>
                                <th>الوحدة</th>
                                <th>المرتجع</th>
                                <th>الوحدة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($p->components as $c)
                                @php
                                    $usedUnit     = \App\Models\Unit::find($c->details['used']['unit_id'] ?? null);
                                    $wastedUnit   = \App\Models\Unit::find($c->details['wasted']['unit_id'] ?? null);
                                    $returnedUnit = \App\Models\Unit::find($c->details['returned']['unit_id'] ?? null);
                                @endphp
                                <tr>
                                    <td>{{ $c->materialBatch->material->name ?? '-' }}</td>
                                    <td>{{ $c->details['used']['qty'] ?? '-' }}</td>
                                    <td>{{ $usedUnit->unit_type ?? '-' }}</td>
                                    <td>{{ $c->details['wasted']['qty'] ?? '-' }}</td>
                                    <td>{{ $wastedUnit->unit_type ?? '-' }}</td>
                                    <td>{{ $c->details['returned']['qty'] ?? '-' }}</td>
                                    <td>{{ $returnedUnit->unit_type ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
@endforeach

@if($order->additionalCosts && $order->additionalCosts->count())
    <div class="card mb-4">
        <div class="card-header bg-warning text-dark">
            <h5>التكاليف الإضافية</h5>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered text-center">
                <thead class="table-light">
                    <tr>
                        <th>الوصف</th>
                        <th>القيمة</th>
                        <th>تاريخ التكلفة</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->additionalCosts as $cost)
                        <tr>
                            <td>{{ $cost->description }}</td>
                            <td>{{ number_format($cost->amount, 2) }} جنيه</td>
                            <td>{{ $cost->cost_date }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

@endsection
