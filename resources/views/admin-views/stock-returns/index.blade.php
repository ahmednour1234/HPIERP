{{-- resources/views/admin-views/stock-returns/index.blade.php --}}
@extends('layouts.admin.app')

@section('title', 'طلبات إرجاع البضاعة')

@section('content')
<div class="container my-5 pt-5" style="padding-right:180px;">
  <h1 class="mb-4">طلبات إرجاع البضاعة</h1>

  <table class="table table-bordered table-hover">
    <thead class="thead-light">
      <tr>
        <th>#</th>
        <th>المندوب</th>
        <th>عدد الأصناف</th>
        <th>الحالة</th>
        <th>تاريخ الطلب</th>
        <th>الإجراءات</th>
      </tr>
    </thead>
    <tbody>
      @forelse($requests as $req)
        <tr>
          <td>{{ $req->id }}</td>
          <td>{{ trim(optional($req->seller)->f_name . ' ' . optional($req->seller)->l_name) }}</td>
          <td>{{ $req->items->count() }}</td>
          <td>
            @if($req->status === 'pending')
              <span class="badge bg-warning text-dark">بانتظار الموافقة</span>
            @elseif($req->status === 'approved')
              <span class="badge bg-success">تمت الموافقة</span>
            @else
              <span class="badge bg-danger">مرفوض</span>
            @endif
          </td>
          <td>{{ optional($req->created_at)->format('Y-m-d H:i') }}</td>
          <td>
            <a href="{{ route('admin.stock-returns.show', $req->id) }}" class="btn btn-sm btn-info">
              مراجعة
            </a>
          </td>
        </tr>
      @empty
        <tr><td colspan="6" class="text-center text-muted">لا توجد طلبات</td></tr>
      @endforelse
    </tbody>
  </table>

  {{ $requests->links() }}
</div>
@endsection
