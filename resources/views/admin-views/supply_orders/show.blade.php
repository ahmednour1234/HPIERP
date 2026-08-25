@extends('layouts.admin.app')

@section('title', 'تفاصيل أمر توريد')

<style>
  body { font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; }

  .invoice-card {
    border-radius: 0.5rem;
    box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
  }
  .invoice-header {
    background: #2596be;
    color: #fff;
    padding: 1rem 1.5rem;
    border-top-left-radius: 0.5rem;
    border-top-right-radius: 0.5rem;
  }
  .invoice-header h3 {
    margin: 0;
    font-weight: 600;
    font-size: 1.25rem;
  }

  .btn-back, .btn-issue {
    border-radius: 0.25rem;
    padding: 0.5rem 1rem;
    font-size: 0.95rem;
    font-weight: 500;
    line-height: 1;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
  }

  .btn-back {
    background: #ffffff;
    color: #2596be;
    border: 2px solid #2596be;
  }
  .btn-back:hover {
    background: #e6f0ff;
  }

  .btn-issue {
    background: #1cc88a;
    color: #ffffff;
    border: 2px solid #17a673;
  }
  .btn-issue:hover {
    background: #17a673;
  }

  .info-block {
    background: #f8f9fc;
    padding: 1rem;
    border-radius: 0.25rem;
  }
  .info-block h6 {
    margin-bottom: 0.75rem;
    font-weight: 600;
    color: #224abe;
  }

  .table-details th {
    background: #e6f7ff;
    font-weight: 600;
  }
  .table-details tr:nth-child(odd) {
    background: #fafbfd;
  }
  .table-details td, .table-details th {
    vertical-align: middle;
  }
  .h1, .h2, .h3, .h4, .h5, .h6, h1, h2, h3, h4, h5, h6 {
    color: #fff;
}
</style>

@section('content')
<div class="container py-4">
  <div class="card invoice-card mb-4">
    {{-- Header with Back + Issue --}}
    <div class="invoice-header d-flex justify-content-between align-items-center" style="color:white;">
      <h3  style="color:white;">أمر توريد #{{ $order->id }}</h3>
      <div class="d-flex">
        <a href="{{ route('admin.supply_orders.index') }}" class="btn-back">
          <i class="fa fa-arrow-left"></i> العودة
        </a>
      @if($order->status == 'draft')
  <form
    action="{{ route('admin.supply_orders.issue', $order->id) }}"
    method="POST"
    class="ms-2"
    onsubmit="return confirm('هل أنت متأكد من تنفيذ هذا الأمر؟')"
  >
    @csrf
    <button type="submit" class="btn-issue">
      <i class="fa fa-check-circle"></i> تنفيذ الأمر
    </button>
  </form>
    
@endif
  @if($order->status === 'completed'||$order->status === 'issued')

        <a href="{{ route('admin.production_orders.create') }}?supply_order_id={{ $order->id }}" class="btn btn-success">بدء إنتاج</a>
@endif
      </div>
    </div>

    <div class="card-body">
      {{-- بيانات عامة --}}
      <div class="row g-3 mb-4">
        <div class="col-md-4 info-block">
          <h6>بيانات الأمر</h6>
          <p><strong>التاريخ:</strong> {{ $order->order_date->format('Y-m-d') }}</p>
          <p>
            <strong>الحالة:</strong>
           <span class="badge 
    @if($order->status === 'draft')  
    @elseif($order->status === 'ended') 
    @else bg-success 
    @endif">
  @if($order->status === 'draft')
    مسودة
  @elseif($order->status === 'ended')
    منتهية (تم الإنتاج)
  @else
    منفذة
  @endif
</span>

          </p>
          <p><strong>التكلفة المتوقعة:</strong> {{ number_format($order->expected_cost, 2) }}</p>
        </div>
        <div class="col-md-4 info-block">
          <h6>بيانات المصنع</h6>
          <p><strong>المصنع:</strong> {{ optional($order->factory)->name }}</p>
          <p><strong>المنشئ:</strong> {{ optional($order->admin)->f_name }}</p>
        </div>
        <div class="col-md-4 info-block">
          <h6>ملاحظات</h6>
          <p class="mb-0">{{ $order->note ?? '—' }}</p>
        </div>
      </div>

      {{-- بنود الأمر --}}
      <h5 class="mb-3">بنود الأمر</h5>
      <div class="table-responsive mb-4">
        <table class="table table-bordered table-details">
          <thead>
            <tr>
              <th>#</th>
              <th>المنتج</th>
              <th>الكمية</th>
              <th>التكلفة للوحدة</th>
              <th>إجمالي التكلفة</th>
            </tr>
          </thead>
          <tbody>
            @foreach($order->items as $i)
              <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $i->product->name }}</td>
                <td>{{ $i->product_quantity }}</td>
                <td>{{ number_format($i->expected_cost_per_unit, 2) }}</td>
                <td>{{ number_format($i->product_quantity * $i->expected_cost_per_unit, 2) }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      {{-- استهلاك الدفعات والمكوّنات --}}
      <h5 class="mb-3">استهلاك الدفعات والمكوّنات</h5>
      <div class="table-responsive">
        <table class="table table-bordered table-details">
          <thead>
            <tr>
              <th>#</th>
              <th>المنتج</th>
              <th>المكوّن</th>
              <th>دفعة المادة</th>
              <th>الكمية المسحوبة</th>
              <th>الوحدة</th>
            </tr>
          </thead>
          <tbody>
            @foreach($order->items as $i)
              @foreach($i->batches as $b)
                <tr>
                  <td>{{ $loop->parent->iteration }}</td>
                  <td>{{ $i->product->name }}</td>
                  <td>{{ optional($b->materialBatch->material)->name }}</td>
                  <td>{{ $b->materialBatch->unique_code }}</td>
                  <td>{{ $b->quantity }}</td>
                  <td>{{ optional($b->unit)->unit_type }}</td>
                </tr>
              @endforeach
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection
