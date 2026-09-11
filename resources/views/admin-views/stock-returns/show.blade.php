{{-- resources/views/admin-views/stock-returns/show.blade.php --}}
@extends('layouts.admin.app')

@section('title', 'مراجعة طلب إرجاع')

@section('content')
<div class="container my-5 pt-5" style="padding-right:180px;">
  <div class="row justify-content-center">
    <div class="col-md-9">
      <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
          <h5 class="mb-0">طلب إرجاع #{{ $returnRequest->id }}</h5>
        </div>

        <div class="card-body">
          <dl class="row">
            <dt class="col-sm-3">المندوب</dt>
            <dd class="col-sm-9">
              {{ trim(optional($returnRequest->seller)->f_name . ' ' . optional($returnRequest->seller)->l_name) }}
            </dd>

            <dt class="col-sm-3">الحالة</dt>
            <dd class="col-sm-9">
              @if($returnRequest->status === 'pending')
                <span class="badge bg-warning text-dark">بانتظار الموافقة</span>
              @elseif($returnRequest->status === 'approved')
                <span class="badge bg-success">تمت الموافقة</span>
              @else
                <span class="badge bg-danger">مرفوض</span>
              @endif
            </dd>

            <dt class="col-sm-3">تاريخ الطلب</dt>
            <dd class="col-sm-9">{{ optional($returnRequest->created_at)->format('Y-m-d H:i') }}</dd>

            <dt class="col-sm-3">ملاحظة المندوب</dt>
            <dd class="col-sm-9">{{ $returnRequest->note ?: '—' }}</dd>

            @if($returnRequest->reviewed_at)
              <dt class="col-sm-3">تاريخ المراجعة</dt>
              <dd class="col-sm-9">{{ $returnRequest->reviewed_at->format('Y-m-d H:i') }}</dd>

              <dt class="col-sm-3">ملاحظة الأدمن</dt>
              <dd class="col-sm-9">{{ $returnRequest->admin_note ?: '—' }}</dd>
            @endif
          </dl>

          <h6 class="fw-semibold mt-4 mb-3">الأصناف المطلوب إرجاعها</h6>
          <table class="table table-bordered">
            <thead class="thead-light">
              <tr>
                <th>المنتج</th>
                <th>كود المنتج</th>
                <th>الكمية</th>
              </tr>
            </thead>
            <tbody>
              @foreach($returnRequest->items as $item)
                <tr>
                  <td>{{ optional($item->product)->name ?: '#' . $item->product_id }}</td>
                  <td>{{ optional($item->product)->product_code ?: '—' }}</td>
                  <td>{{ $item->quantity }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>

          @if($returnRequest->status === 'pending')
            <div class="alert alert-info">
              الاعتماد ينقل هذه الكميات من عربية المندوب إلى المخزن. الرفض لا يغيّر المخزون.
            </div>

            <div class="row g-3">
              <div class="col-md-6">
                <form action="{{ route('admin.stock-returns.approve', $returnRequest->id) }}" method="POST">
                  @csrf
                  <label class="form-label">ملاحظة (اختيارية)</label>
                  <textarea name="admin_note" class="form-control mb-2" rows="2"></textarea>
                  <button type="submit" class="btn btn-success w-100"
                          onclick="return confirm('اعتماد الطلب ونقل الكميات إلى المخزن؟')">
                    اعتماد
                  </button>
                </form>
              </div>

              <div class="col-md-6">
                <form action="{{ route('admin.stock-returns.reject', $returnRequest->id) }}" method="POST">
                  @csrf
                  <label class="form-label">سبب الرفض <span class="text-danger">*</span></label>
                  <textarea name="admin_note"
                            class="form-control mb-2 @error('admin_note') is-invalid @enderror"
                            rows="2" required></textarea>
                  @error('admin_note')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                  @enderror
                  <button type="submit" class="btn btn-danger w-100">رفض</button>
                </form>
              </div>
            </div>
          @endif

          <a href="{{ route('admin.stock-returns.index') }}" class="btn btn-outline-secondary mt-4">
            العودة للقائمة
          </a>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
