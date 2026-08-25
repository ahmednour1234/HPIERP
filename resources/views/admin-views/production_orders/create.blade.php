@extends('layouts.admin.app')

@section('title', 'إنهاء أمر إنتاج')

<style>
  body {
    font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f8f9fc;
  }

  .invoice-card {
    border-radius: 1rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
    background-color: #ffffff;
  }

  .invoice-header {
    background: #224abe;
    color: #fff;
    padding: 1.5rem 2rem;
    border-top-left-radius: 1rem;
    border-top-right-radius: 1rem;
  }

  .invoice-header h3 {
    margin: 0;
    font-weight: 700;
    font-size: 1.5rem;
  }

  .btn-back, .btn-issue {
    border-radius: 0.25rem;
    padding: 0.75rem 1.25rem;
    font-size: 1rem;
    font-weight: 600;
    line-height: 1.5;
    display: inline-flex;
    gap: 0.5rem;
    align-items: center;
  }

  .btn-back {
    background: #fff;
    color: #224abe;
    border: 2px solid #224abe;
  }

  .btn-issue {
    background: #1cc88a;
    color: #fff;
    border: 2px solid #17a673;
  }

  .form-control, .form-select {
    border-radius: 0.25rem;
    padding: 0.75rem;
  }

  .mb-4 { margin-bottom: 2rem; }
  .mb-3 { margin-bottom: 1rem; }
</style>

@section('content')
@php
    use App\Models\Unit;
    function getUnitTree($baseId) {
        return \App\Models\Unit::where('base_unit_id', $baseId)
                ->orWhere('id', $baseId)
                ->get();
    }
@endphp

<div class="card invoice-card">
  <div class="invoice-header">
    <h3>
      @isset($order)
        إنهاء أمر إنتاج بناءً على طلب التوريد #{{ $order->id }}
      @else
        أدخل رقم طلب التوريد لإنهاء الإنتاج
      @endisset
    </h3>
  </div>

  @isset($order)
    <div class="card-body">
      <div class="mb-4">
        <h5>بيانات المصنع</h5>
        <p><strong>الاسم:</strong> {{ $order->factory->name ?? '-' }}</p>
        <p><strong>الهاتف:</strong> {{ $order->factory->phone ?? '-' }}</p>
        <p><strong>البريد الإلكتروني:</strong> {{ $order->factory->email ?? '-' }}</p>
      </div>
    </div>
  @endisset

  @empty($order)
    <form action="{{ route('admin.production_orders.create') }}" method="GET">
      <div class="card-body">
        <div class="mb-3">
          <label>رقم طلب التوريد</label>
          <input type="text" name="supply_order_id" class="form-control" required>
        </div>
        <div class="text-end">
          <button type="submit" class="btn btn-primary">متابعة</button>
        </div>
      </div>
    </form>
  @else
    <form method="POST" action="{{ route('admin.production_orders.store') }}">
      @csrf
      <input type="hidden" name="supply_order_id" value="{{ $order->id }}">
      <input type="hidden" name="factory_id" value="{{ $order->factory_id }}">
      <div class="card-body">

        @foreach($order->items as $index => $item)
          <div class="mb-4 p-3 border rounded">
            <h5>{{ $item->product->name }}</h5>
            <input type="hidden" name="products[{{ $index }}][product_id]" value="{{ $item->product->id }}">

            <div class="row">
              <div class="col-md-3">
                <label>الكمية المطلوبة</label>
                <input type="number" name="products[{{ $index }}][target_quantity]" class="form-control" value="{{ $item->product_quantity }}" readonly>
              </div>
              <div class="col-md-3">
                <label>الكمية المنتجة</label>
                <input type="number" name="products[{{ $index }}][produced_quantity]" step="0.001" class="form-control" required>
              </div>
              <div class="col-md-3">
                <label>سعر التكلفة المتوقع</label>
                <input type="number" class="form-control" value="{{ $item->expected_cost_per_unit }}" disabled>
              </div>
              <div class="col-md-3">
                <label>سعر التكلفة الفعلي</label>
                <input type="number" name="products[{{ $index }}][cost_price]" step="0.001" class="form-control" required>
              </div>
              <div class="col-md-3">
                <label>تكلفة إضافية للوحدة</label>
                <input type="number" name="products[{{ $index }}][additional_cost_price]" step="0.001" class="form-control">
              </div>
              <div class="col-md-3">
                <label>تاريخ الإنتاج</label>
                <input type="date" name="products[{{ $index }}][production_date]" class="form-control" required>
              </div>
              <div class="col-md-3">
                <label>تاريخ الانتهاء</label>
                <input type="date" name="products[{{ $index }}][end_date]" class="form-control">
              </div>
              <div class="col-md-3">
                <label>رقم التشغيلة</label>
                <input type="text" name="products[{{ $index }}][batch_number]" class="form-control">
              </div>
            </div>

            @foreach($item->batches as $bIndex => $batch)
              @php
                $baseUnit = $batch->materialBatch->unitRelation;
                $units = getUnitTree($baseUnit->base_unit_id ?? $baseUnit->id);
              @endphp
              <div class="row mt-3 p-2 border bg-light rounded">
                <div class="col-md-12 mb-2">
                  <strong>دفعة #{{ $batch->materialBatch->id }} - {{ $batch->materialBatch->material->name }} - الكمية: {{ $batch->quantity }} {{ $baseUnit->unit_type }}</strong>
                </div>

                @foreach(['used' => 'المستخدمة', 'wasted' => 'الهالك', 'returned' => 'المرتجع'] as $type => $label)
                  <div class="col-md-3">
                    <label>{{ $label }}</label>
                    <div class="input-group">
                      <input type="number" step="0.001" name="components[{{ $index }}][{{ $bIndex }}][details][{{ $type }}][qty]" class="form-control qty-input" data-batch="{{ $index }}-{{ $bIndex }}" data-type="{{ $type }}" required="{{ $type == 'used' ? 'required' : '' }}">
                      <select name="components[{{ $index }}][{{ $bIndex }}][details][{{ $type }}][unit_id]" class="form-select unit-select" data-batch="{{ $index }}-{{ $bIndex }}" data-type="{{ $type }}">
                        @foreach($units as $unit)
                          <option value="{{ $unit->id }}" data-rate="{{ $unit->conversion_rate }}">{{ $unit->unit_type }}</option>
                        @endforeach
                      </select>
                    </div>
                  </div>
                @endforeach

                <input type="hidden" class="batch-qty" value="{{ $batch->quantity }}">
                <input type="hidden" class="base-rate" value="{{ $baseUnit->conversion_rate }}">
                <input type="hidden" name="components[{{ $index }}][{{ $bIndex }}][supply_order_item_id]" value="{{ $item->id }}">
                <input type="hidden" name="components[{{ $index }}][{{ $bIndex }}][material_batch_id]" value="{{ $batch->material_batch_id }}">
              </div>
            @endforeach
          </div>
        @endforeach


        <h5 class="mt-4">التكاليف الإضافية</h5>
        <table class="table" id="extra-costs-table">
          <thead>
            <tr><th>الوصف</th><th>المبلغ</th><th>التاريخ</th><th>إجراء</th></tr>
          </thead>
          <tbody></tbody>
        </table>
        <button type="button" id="add-cost" class="btn btn-secondary mb-3">إضافة تكلفة</button>

<div class="mt-4 p-3 border rounded">
  <h5>بيانات الدفع</h5>
  <div class="row">
    <div class="col-md-4">
      <label>طريقة الدفع</label>
      <select name="status" id="payment-type" class="form-select" required>
        <option value="cash">نقداً</option>
        <option value="agel">آجل</option>
      </select>
    </div>

    <div class="col-md-4">
      <label>الحساب المستخدم</label>
      <select name="account_id" id="account-select" class="form-select" required>
        <option value="">-- اختر الحساب --</option>
        @foreach($accounts as $account)
          <option value="{{ $account->id }}">{{ $account->account }}</option>
        @endforeach
      </select>
    </div>

    <div class="col-md-4">
      <label>إجمالي التكلفة</label>
      <input type="number" name="total_cash" step="0.01" class="form-control" required>
    </div>

    <div class="col-md-4 mt-3">
      <label>المبلغ المدفوع</label>
      <input type="number" name="paid" step="0.01" class="form-control" required>
    </div>
  </div>
</div>

        <div class="text-end mt-4">
          <button type="submit" class="btn btn-primary">حفظ وإنهاء</button>
          <a href="{{ route('admin.production_orders.index') }}" class="btn btn-light">إلغاء</a>
        </div>
      </div>
    </form>
  @endempty
</div>

{{-- التعديلات تبدأ من هنا --}}
<script>
  function convertToBase(value, rate) {
    return parseFloat(value || 0) * parseFloat(rate || 1);
  }

  function calculateTotalInvoice() {
    let total = 0;

    // 1) احسب التكلفة من المنتجات
    document.querySelectorAll('[name^="products"]').forEach((el) => {
      const row = el.closest('.row');
      if (!row) return;

      const producedQty = parseFloat(row.querySelector('[name$="[produced_quantity]"]')?.value || 0);
      const costPrice = parseFloat(row.querySelector('[name$="[cost_price]"]')?.value || 0);
      const addCost = parseFloat(row.querySelector('[name$="[additional_cost_price]"]')?.value || 0);

      total = producedQty * (costPrice +addCost);
    });

    // 2) احسب التكلفة من التكاليف الإضافية
    document.querySelectorAll('#extra-costs-table tbody tr').forEach(row => {
      const cost = parseFloat(row.querySelector('input[name*="[amount]"]')?.value || 0);
      total += cost;
    });

    // 3) ضع القيمة في الحقل
    document.querySelector('[name="total_cash"]').value = total.toFixed(2);

    // 4) إذا كان الدفع نقداً، اجعل المبلغ المدفوع مساوياً للإجمالي
    const paymentType = document.querySelector('[name="status"]').value;
    if (paymentType === 'cash') {
      document.querySelector('[name="paid"]').value = total.toFixed(2);
    }
  }

  document.addEventListener('input', function (e) {
    const fields = ['produced_quantity', 'cost_price', 'additional_cost_price', '[amount]'];
    if (fields.some(name => e.target.name.includes(name))) {
      calculateTotalInvoice();
    }
  });

  document.querySelector('[name="status"]')?.addEventListener('change', calculateTotalInvoice);

  document.getElementById('add-cost')?.addEventListener('click', () => {
    const tbody = document.querySelector('#extra-costs-table tbody');
    const index = tbody.rows.length;
    const row = document.createElement('tr');
    row.innerHTML = `
      <td><input type="text" name="additional_costs[${index}][description]" class="form-control" required></td>
      <td><input type="number" name="additional_costs[${index}][amount]" step="0.01" class="form-control" required></td>
      <td><input type="date" name="additional_costs[${index}][cost_date]" class="form-control" required></td>
      <td><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove(); calculateTotalInvoice()">حذف</button></td>
    `;
    tbody.appendChild(row);
  });

  document.querySelector('form')?.addEventListener('submit', function (e) {
    const batchBlocks = document.querySelectorAll('.batch-qty');
    for (const batch of batchBlocks) {
      const block = batch.closest('.row');
      const expected = convertToBase(batch.value, block.querySelector('.base-rate').value);

      let total = 0;
      ['used', 'wasted', 'returned'].forEach(type => {
        const qtyInput = block.querySelector(`.qty-input[data-type="${type}"]`);
        const unitSelect = block.querySelector(`.unit-select[data-type="${type}"]`);
        const qty = qtyInput?.value || 0;
        const rate = unitSelect?.selectedOptions[0]?.dataset.rate || 1;

        total += convertToBase(qty, rate);
      });

      if (Math.abs(expected - total) > 0.01) {
        alert("خطأ في توزيع الكمية على دفعة الإنتاج. تحقق من المستخدم والهالك والمرتجع.");
        e.preventDefault();
        return false;
      }
    }

    const totalCash = parseFloat(document.querySelector('[name="total_cash"]').value || 0);
    const paid = parseFloat(document.querySelector('[name="paid"]').value || 0);

    if (document.querySelector('[name="status"]').value === 'cash' && Math.abs(totalCash - paid) > 0.01) {
      alert("في حالة الدفع نقدًا، يجب أن يكون المبلغ المدفوع مساويًا لإجمالي الفاتورة.");
      e.preventDefault();
    }
  });

  window.addEventListener('DOMContentLoaded', calculateTotalInvoice);
</script>

@endsection
