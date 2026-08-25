{{-- resources/views/admin-views/purchases/show.blade.php --}}


<style>
  body { background: #eef2f7; }
  .invoice-card {
    background: #fff;
    border-radius: .75rem;
    box-shadow: 0 0.75rem 2rem rgba(0,0,0,0.1);
    margin: 2rem auto;
    max-width: 960px;
    overflow: hidden;
  }
  .invoice-header {
    background: linear-gradient(90deg, #4e73df, #224abe);
    color: #fff;
    padding: 1.5rem 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  .invoice-header h2 { margin: 0; font-size: 2rem; }
  .invoice-info {
    padding: 1.5rem 2rem;
    display: flex;
    gap: 2rem;
  }
  .info-block {
    flex: 1;
    background: #f8f9fc;
    border-radius: .5rem;
    padding: 1rem;
  }
  .info-block h5 {
    margin-bottom: .75rem;
    color: #4e73df;
    font-weight: 600;
  }
  .info-block table th {
    width: 40%;
    color: #555;
    padding: .25rem 0;
  }
  .info-block table td {
    padding: .25rem 0;
    font-weight: 600;
  }
  .table-details {
    width: 100%;
    border-collapse: collapse;
    margin: 0 2rem 2rem;
  }
  .table-details th, .table-details td {
    border: 1px solid #dee2e6;
    padding: .75rem;
    text-align: center;
  }
  .table-details thead th {
    background: #f8f9fc;
    color: #333;
    font-weight: 600;
  }
  .table-details tbody tr:nth-child(odd) {
    background: #fafbfc;
  }
  .summary {
    display: flex;
    justify-content: flex-end;
    margin: 0 2rem 2rem;
    gap: 2rem;
  }
  .summary .box {
    background: #f8f9fc;
    border-radius: .5rem;
    padding: 1rem;
    min-width: 200px;
    text-align: right;
  }
  .summary .box h6 {
    margin: 0 0 .5rem;
    color: #4e73df;
    font-weight: 600;
  }
  .actions {
    padding: 0 2rem 2rem;
    text-align: right;
  }
  .actions .btn {
    min-width: 140px;
    margin-left: .5rem;
  }
  .actions .form-control {
    display: inline-block;
    width: auto;
    vertical-align: middle;
  }
  .actions label {
    margin-right: .5rem;
    font-weight: 600;
  }
</style>

<div class="invoice-card">
  <div class="invoice-header">
    <h2>فاتورة شراء #{{ $purchase->id }}</h2>
    <a href="{{ route('admin.purchases.index') }}" class="btn btn-light">
      <i class="fa fa-arrow-left"></i> العودة للقائمة
    </a>
  </div>

  <div class="invoice-info">
    <div class="info-block">
      <h5>بيانات الفاتورة</h5>
      <table class="w-100">
        <tr>
          <th>التاريخ:</th>
          <td>{{ $purchase->created_at}}</td>
        </tr>
        <tr>
          <th>الحالة:</th>
          <td>
            <span class="badge bg-{{ $purchase->status==='draft'?'secondary':'success' }}">
              {{ $purchase->status==='draft'?'مسودة':'منفذة' }}
            </span>
          </td>
        </tr>
        <tr>
          <th>نوع الدفع:</th>
          <td>{{ $purchase->payment_type==='cash'?'كاش':'آجل' }}</td>
        </tr>
      </table>
    </div>
    <div class="info-block">
      <h5>بيانات المورد</h5>
      <table class="w-100">
        <tr>
          <th>المورد:</th>
          <td>{{ $purchase->supplier->name }}</td>
        </tr>
        <tr>
          <th>المسؤول:</th>
          <td>{{ $purchase->admin->f_name . ' ' . $purchase->admin->l_name }}</td>
        </tr>
      </table>
    </div>
  </div>

  <table class="table-details">
    <thead>
      <tr>
        <th>#</th><th>المادة</th><th>الوحدة</th><th>الكمية</th>
        <th>سعر الوحدة</th><th>الخصم</th><th>الضريبة</th><th>الإجمالي</th>
      </tr>
    </thead>
    <tbody>
      @foreach($purchase->details as $d)
        @php
          $line = $d->unit_price * $d->quantity - $d->discount + $d->tax_amount;
        @endphp
        <tr>
          <td>{{ $loop->iteration }}</td>
          <td>{{ $d->material->name }}</td>
          <td>{{ optional($d->unitRelation)->name ?? $d->unit }}</td>
          <td>{{ $d->quantity }}</td>
          <td>{{ number_format($d->unit_price,2) }}</td>
          <td>{{ number_format($d->discount,2) }}</td>
          <td>{{ number_format($d->tax_amount,2) }}</td>
          <td>{{ number_format($line,2) }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>

  <div class="summary">
    <div class="box">
      <h6>الإجمالي الفرعي</h6>
      <div>{{ number_format($purchase->sub_total,2) }}</div>
    </div>
    <div class="box">
      <h6>إجمالي الخصم</h6>
      <div>{{ number_format($purchase->total_discount,2) }}</div>
    </div>
    <div class="box">
      <h6>إجمالي الضريبة</h6>
      <div>{{ number_format($purchase->tax_amount,2) }}</div>
    </div>
    <div class="box">
      <h6>الإجمالي النهائي</h6>
      <div>{{ number_format($purchase->sub_total - $purchase->total_discount + $purchase->tax_amount,2) }}</div>
    </div>
  </div>

  <div class="actions">
    @if($purchase->status === 'draft')
      <form method="POST" action="{{ route('admin.purchases.execute', $purchase->id) }}" class="d-inline-block">
        @csrf
        <div class="mb-3">
          <label>نوع الدفع:</label>
          <select name="payment_type" class="form-control" required>
            <option value="cash" @selected($purchase->payment_type==='cash')>كاش</option>
            <option value="credit" @selected($purchase->payment_type==='credit')>آجل</option>
          </select>
        </div>
        <div class="mb-3">
          <label>اختر الحساب للدفع:</label>
          <select name="account_id" class="form-control" required>
            <option value="">--</option>
            @foreach($accounts as $acct)
              <option value="{{ $acct->id }}">{{ $acct->name }} ({{ number_format($acct->balance,2) }})</option>
            @endforeach
          </select>
        </div>
        <div class="mb-3">
          <label>المبلغ المدفوع:</label>
          <input name="paid_amount" type="number" step="0.01"
                 class="form-control"
                 max="{{ $purchase->sub_total - $purchase->total_discount + $purchase->tax_amount }}"
                 value="{{ old('paid_amount', $purchase->paid_amount) }}"
                 required>
        </div>
        <button type="submit" class="btn btn-success">
          <i class="fa fa-check-circle"></i> تنفيذ الفاتورة
        </button>
      </form>
    @endif
  </div>
</div>
