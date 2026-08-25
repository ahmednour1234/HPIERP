{{-- resources/views/admin-views/purchases/index.blade.php --}}
@extends('layouts.admin.app')

@section('title', 'قائمة فواتير الشراء')

@push('css_or_js')
<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
  body { background: #f0f2f5; }
  .page-header {
    background: #4e73df;
    color: #fff;
    padding: 1rem 1.5rem;
    border-radius: .5rem;
    margin-bottom: 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  .page-header h1 { margin: 0; font-size: 1.75rem; }
  .btn-create {
    background: #1cc88a;
    color: #fff;
    border: none;
  }
  .btn-create:hover { background: #17a673; }
  .filter-card, .table-card {
    box-shadow: 0 0.25rem 1rem rgba(0,0,0,0.1);
    border: none;
    border-radius: .5rem;
    margin-bottom: 1.5rem;
  }
  .filter-card .form-label { font-weight: 600; }
  .table-card thead { background: #e9ecef; }
  .table-card tbody tr:nth-child(odd) { background: #f8f9fc; }
  .badge-status { font-size: 0.85rem; padding: .4em .6em; }
</style>
@endpush

@section('content')
<div class="container py-4">
  {{-- Header --}}
  <div class="page-header">
    <h1>قائمة فواتير الشراء</h1>
    <a href="{{ route('admin.purchases.create') }}" class="btn btn-create px-4 py-2">
      <i class="fa fa-plus-circle me-1"></i> إنشاء فاتورة جديدة
    </a>
  </div>

  {{-- Filters --}}
  <div class="card filter-card p-4">
    <form method="GET" class="row g-3">
      <div class="col-md-3">
        <label class="form-label">المورد</label>
        <select name="supplier_id" class="form-control">
          <option value="">كل الموردين</option>
          @foreach($suppliers as $id => $name)
            <option value="{{ $id }}" @selected(request('supplier_id')==$id)> {{ $name }} </option>
          @endforeach
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">المسؤول</label>
        <select name="admin_id" class="form-control">
          <option value="">كل المسؤولين</option>
          @foreach($admins as $id => $name)
            <option value="{{ $id }}" @selected(request('admin_id')==$id)> {{ $name }} </option>
          @endforeach
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">من تاريخ</label>
        <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
      </div>
      <div class="col-md-2">
        <label class="form-label">إلى تاريخ</label>
        <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
      </div>
      <div class="col-md-2 d-grid">
        <button type="submit" class="btn btn-primary">
          <i class="fa fa-filter me-1"></i> تصفية
        </button>
      </div>
    </form>
  </div>

  {{-- Table --}}
  <div class="card table-card">
    <div class="table-responsive">
      <table class="table mb-0">
        <thead>
          <tr>
            <th>#</th>
            <th>التاريخ</th>
            <th>المورد</th>
            <th>المسؤول</th>
            <th>الفرعي</th>
            <th>الخصم</th>
            <th>الضريبة</th>
            <th>الإجمالي</th>
            <th>المدفوع</th>
            <th>الماليه</th>
            <th>الحالة</th>
            <th>إجراءات</th>
          </tr>
        </thead>
        <tbody>
          @foreach($purchases as $purchase)
            @php
              $subtotal = $purchase->sub_total;
              $discount = $purchase->total_discount;
              $tax      = $purchase->tax_amount;
              $total    = $subtotal - $discount + $tax;
              $paid     = $purchase->paid_amount;
              $finLabel = $paid >= $total ? 'مدفوعة' : 'غير مدفوعة';
              $statusLabel = $purchase->status === 'draft' ? 'مسودة' : 'منفذة';
            @endphp
            <tr>
              <td>{{ $purchase->id }}</td>
              <td>{{ $purchase->created_at->format('Y-m-d') }}</td>
              <td>{{ $purchase->supplier->name }}</td>
              <td>{{ $purchase->admin->f_name . ' ' . $purchase->admin->l_name }}</td>
              <td>{{ number_format($subtotal,2) }}</td>
              <td>{{ number_format($discount,2) }}</td>
              <td>{{ number_format($tax,2) }}</td>
              <td>{{ number_format($total,2) }}</td>
              <td>{{ number_format($paid,2) }}</td>
              <td>
                <span class="badge badge-status bg-{{ $paid >= $total ? 'success' : 'warning' }}">
                  {{ $finLabel }}
                </span>
              </td>
              <td>
                <span class="badge badge-status bg-{{ $purchase->status==='draft'?'secondary':'primary' }}">
                  {{ $statusLabel }}
                </span>
              </td>
              <td>
                <a href="{{ route('admin.purchases.show',$purchase) }}" class="btn btn-sm btn-info">
                  <i class="fa fa-eye"></i> عرض
                </a>
              </td>
            </tr>
          @endforeach
        </tbody>
        <tfoot>
          @php
            $sumSub   = $purchases->sum('sub_total');
            $sumDisc  = $purchases->sum('total_discount');
            $sumTax   = $purchases->sum('tax_amount');
            $sumTotal = $purchases->sum(fn($p)=>$p->sub_total - $p->total_discount + $p->tax_amount);
            $sumPaid  = $purchases->sum('paid_amount');
          @endphp
          <tr>
            <td colspan="4" class="text-end">المجموع الكلي:</td>
            <td>{{ number_format($sumSub,2) }}</td>
            <td>{{ number_format($sumDisc,2) }}</td>
            <td>{{ number_format($sumTax,2) }}</td>
            <td>{{ number_format($sumTotal,2) }}</td>
            <td>{{ number_format($sumPaid,2) }}</td>
            <td colspan="3"></td>
          </tr>
        </tfoot>
      </table>
    </div>
    <div class="card-footer text-center">
      {{ $purchases->withQueryString()->links() }}
    </div>
  </div>
</div>
@endsection
