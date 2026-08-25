{{-- resources/views/admin/supply_orders/index.blade.php --}}
@extends('layouts.admin.app')

@section('title', 'أوامر التوريد')

<style>
  body {
    background: #f5f8fa;
  }
  .page-header {
    background: #bee0ec;
    color: #000;
    padding: 1rem 1.5rem;
    border-radius: 0.375rem;
    margin-bottom: 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 0.25rem 0.75rem rgba(0,0,0,0.1);
  }
  .btn-create {
    background: #002147;
    color: #fff;
    font-weight: 500;
  }
  .btn-create:hover {
    background: #001631;
  }
  .card-table th {
    background: #bee0ec;
    color: #000;
    font-weight: 600;
  }
  .table-responsive {
    padding: 1rem;
  }
  .status-badge.draft {
    background: #6c757d;
    color: #fff;
  }
  .status-badge.issued {
    background: #20c997;
    color: #fff;
  }
  .action-btn {
    margin-right: 0.25rem;
  }
  .table td, .table th {
    color: #000;
  }
</style>

@section('content')
<div class="container py-4">
  <div class="page-header">
    <h2 class="h4 mb-0">قائمة أوامر التوريد</h2>
    <a href="{{ route('admin.supply_orders.create') }}" class="btn btn-create">
      <i class="fa fa-plus-circle me-1"></i> إنشاء أمر جديد
    </a>
  </div>

  <div class="card shadow-sm">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table align-middle mb-0 card-table">
          <thead>
            <tr>
              <th class="text-center">#</th>
              <th>التاريخ</th>
              <th>المصنع</th>
              <th>المنشئ</th>
              <th>الحالة</th>
              <th class="text-end">التكلفة المتوقعة</th>
              <th class="text-center">إجراءات</th>
            </tr>
          </thead>
          <tbody>
            @forelse($orders as $o)
            <tr>
              <td class="text-center">{{ $o->id }}</td>
              <td>{{ $o->order_date->format('Y-m-d') }}</td>
              <td>{{ optional($o->factory)->name }}</td>
              <td>{{ optional($o->admin)->f_name }}</td>
          <td>
  <span class="badge status-badge {{ $o->status }}">
    @switch($o->status)
      @case('draft')
        مسودة
        @break

      @case('ended')
        منتهي (تم إنتاجه)
        @break

      @default
        صادر
    @endswitch
  </span>
</td>

              <td class="text-end">{{ number_format($o->expected_cost,2) }}</td>
              <td class="text-center">
                <a href="{{ route('admin.supply_orders.show',$o) }}"
                   class="btn btn-sm btn-info action-btn" title="عرض">
                  <i class="fa fa-eye"></i>
                </a>
                <!--<a href="{{ route('admin.supply_orders.edit',$o) }}"-->
                <!--   class="btn btn-sm btn-warning action-btn" title="تعديل">-->
                <!--  <i class="fa fa-edit"></i>-->
                <!--</a>-->
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="7" class="text-center py-4">لا توجد بيانات</td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    @if($orders->hasPages())
    <div class="card-footer d-flex justify-content-center">
      {{ $orders->links() }}
    </div>
    @endif
  </div>
</div>
@endsection
