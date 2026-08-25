{{-- resources/views/admin/supply_orders/create.blade.php --}}
@extends('layouts.admin.app')

@section('title', 'إنشاء أمر توريد')

@push('styles')
<style>
  body { background: #f5f8fa; }
  .page-header { background: #bee0ec; color: #000; padding:1rem; border-radius:.375rem; margin-bottom:1.5rem; }
  .card { box-shadow:0 .25rem .75rem rgba(0,0,0,0.1); }
  .btn-submit { background:#002147; color:#fff; }
  .btn-submit:hover { background:#001631; }
  .section { background:#fff; padding:1rem; border-radius:.375rem; margin-bottom:1rem; }
  .section h5 { color:#002147; }
  .table thead th { background:#bee0ec; color:#000; }
  .btn-sm { font-size:.85rem; }
  #expectedTotal { font-size: 1.25rem; color: #002147; }
</style>
@endpush

@section('content')
<div class="container py-4">
  <div class="page-header d-flex justify-content-between align-items-center">
    <h2 class="h5 mb-0">إنشاء أمر توريد جديد</h2>
    <a href="{{ route('admin.supply_orders.index') }}" class="btn btn-light">
      <i class="fa fa-arrow-left"></i> العودة
    </a>
  </div>

  <div class="card">
    <div class="card-body">
      <form id="supplyForm" action="{{ route('admin.supply_orders.store') }}" method="POST">
        @csrf

        {{-- بيانات الأمر --}}
        <div class="row g-3 mb-4">
          <div class="col-md-4">
            <label>المصنع</label>
            <select name="factory_id" class="form-control" required>
              <option value="">اختر المصنع</option>
              @foreach($factories as $id=>$name)
                <option value="{{ $id }}">{{ $name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4">
            <label>تاريخ الأمر</label>
            <input type="date" name="order_date" class="form-control" required>
          </div>
          <div class="col-md-4">
            <label>الحالة</label>
            <select name="status" class="form-control" required>
              <option value="draft">مسودة</option>
              <option value="issued">صادر</option>
            </select>
          </div>
        </div>
        <div class="mb-4">
          <label>ملاحظة (اختياري)</label>
          <textarea name="note" class="form-control" rows="2"></textarea>
        </div>

        {{-- زر إضافة منتج --}}
        <div class="mb-3 d-flex justify-content-between align-items-center">
          <h5>المنتجات المستهدفة</h5>
          <button type="button" id="addProduct" class="btn btn-sm btn-success">
            <i class="fa fa-plus"></i> إضافة منتج
          </button>
        </div>

        <div id="productsWrapper"></div>

        {{-- عرض التكلفة الإجمالية المتوقعة --}}
        <div class="mb-4 text-end">
          <strong>التكلفة الإجمالية المتوقعة: <span id="expectedTotal">0.00</span></strong>
        </div>

        <div class="text-end">
          <button type="submit" class="btn btn-submit px-4">حفظ الأمر</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(function(){
  let prodIdx = 0;

  // حساب التكلفة الإجمالية: مجموع (target_qty * unit_cost) لكل منتج
  function calculateTotal() {
    let total = 0;
    $('#productsWrapper .section').each(function(){
      const qty = parseFloat($(this).find('input[name$="[target_qty]"]').val()) || 0;
      const cost = parseFloat($(this).find('input[name$="[unit_cost]"]').val()) || 0;
      total += qty * cost;
    });
    $('#expectedTotal').text(total.toFixed(2));
  }

  function productSection(idx){
    return `
    <div class="section" data-prod="${idx}">
      <div class="d-flex justify-content-between mb-3">
        <h5>منتج #${idx+1}</h5>
        <button type="button" class="btn btn-sm btn-danger removeProduct">
          <i class="fa fa-trash"></i> حذف
        </button>
      </div>
      <div class="row g-3 mb-3">
        <div class="col-md-4">
          <label>المنتج</label>
          <select name="products[${idx}][product_id]" class="form-control product-select" required>
            <option value="">اختر المنتج</option>
            @foreach($products as $id=>$name)
              <option value="{{ $id }}">{{ $name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-4">
          <label>الكمية المستهدفة</label>
          <input type="number" name="products[${idx}][target_qty]" step="0.001" class="form-control" required>
        </div>
        <div class="col-md-4">
          <label>تكلفة الوحدة المتوقعة</label>
          <input type="number" name="products[${idx}][unit_cost]" step="0.01" class="form-control" required>
        </div>
      </div>
      <div class="mb-2 d-flex justify-content-between align-items-center">
        <h6>المكوّنات</h6>
        <button type="button" class="btn btn-sm btn-primary addComp" data-prod="${idx}">
          <i class="fa fa-plus"></i> إضافة مكوّن
        </button>
      </div>
      <table class="table table-bordered mb-3" id="compTable-${idx}">
        <thead>
          <tr>
            <th>المادة الخام</th>
            <th>الكمية المُطبقة</th>
            <th>تكلفة الوحدة</th>
            <th class="text-center">أفعال</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>`;
  }

  $('#addProduct').click(()=>{
    $('#productsWrapper').append(productSection(prodIdx));
    prodIdx++;
  });

  $('#productsWrapper')
    // حذف منتج
    .on('click','.removeProduct',function(){
      $(this).closest('.section').remove();
      calculateTotal();
    })
    // تحديث المجموع عند تغيير Qty أو Cost
    .on('input','input[name$="[target_qty]"], input[name$="[unit_cost]"]', calculateTotal)
    // إضافة مكوّن
    .on('click','.addComp',function(){
      const p = $(this).data('prod');
      const compIdx = $(`#compTable-${p} tbody tr`).length;
      let matOpts = '<option value="">اختر المادة</option>';
      @foreach($materials as $m)
        matOpts += `<option value="{{ $m->id }}">{{ $m->name }}</option>`;
      @endforeach
      const row = `
        <tr data-prod="${p}" data-comp="${compIdx}">
          <td>
            <select name="products[${p}][comps][${compIdx}][material_id]" class="form-control mat-select" required>
              ${matOpts}
            </select>
          </td>
          <td>
            <input type="number" name="products[${p}][comps][${compIdx}][req_qty]" class="form-control" step="0.001" required>
          </td>
          <td>
            <input type="number" name="products[${p}][comps][${compIdx}][comp_cost]" class="form-control" step="0.01" required>
          </td>
          <td class="text-center">
            <button type="button" class="btn btn-sm btn-danger removeComp">
              <i class="fa fa-trash"></i>
            </button>
            <button type="button" class="btn btn-sm btn-info toggleBatches" data-prod="${p}" data-comp="${compIdx}">
              دفعات
            </button>
          </td>
        </tr>
        <tr id="batches-${p}-${compIdx}" style="display:none;">
          <td colspan="4" class="p-0">
            <table class="table table-sm table-bordered mb-0">
              <thead>
                <tr><th>دفعة</th><th>الوحدة</th><th>الكمية</th><th></th></tr>
              </thead>
              <tbody></tbody>
              <tfoot>
                <tr>
                  <td colspan="4" class="text-center">
                    <button type="button" class="btn btn-sm btn-success addBatch" data-prod="${p}" data-comp="${compIdx}">
                      <i class="fa fa-plus"></i> دفعة
                    </button>
                  </td>
                </tr>
              </tfoot>
            </table>
          </td>
        </tr>`;
      $(`#compTable-${p} tbody`).append(row);
    })
    // حذف مكوّن
    .on('click','.removeComp',function(){
      const tr = $(this).closest('tr');
      const p = tr.data('prod'), c = tr.data('comp');
      $(`#batches-${p}-${c}`).remove();
      tr.remove();
    })
    // تبديل عرض دفعات
    .on('click','.toggleBatches',function(){
      const p = $(this).data('prod'), c = $(this).data('comp');
      $(`#batches-${p}-${c}`).toggle();
    })
    // إضافة دفعة
    .on('click','.addBatch',function(){
      const p = $(this).data('prod'), c = $(this).data('comp');
      const tbody = $(`#batches-${p}-${c} tbody`);
      const compMatId = $(`select[name="products[${p}][comps][${c}][material_id]"]`).val();
      if(!compMatId) { alert('اختر المادة أولاً'); return; }
      $.getJSON(`/api/materials/${compMatId}/batches`, batches=>{
        let opts = '<option value="">اختر الدفعة</option>';
        batches.forEach(b=>{
          opts += `<option value="${b.id}" data-unit="${b.unit_id}" data-qty="${b.qty}">${b.code} (${b.qty})</option>`;
        });
        const idx = tbody.children().length;
        const row = `
          <tr>
            <td>
              <select name="products[${p}][comps][${c}][batches][${idx}][batch_id]" class="form-control batch-select" required>
                ${opts}
              </select>
            </td>
            <td>
              <select name="products[${p}][comps][${c}][batches][${idx}][unit_id]" class="form-control unit-select" required>
                <option value="">اختر الوحدة</option>
              </select>
            </td>
            <td>
              <input type="number" name="products[${p}][comps][${c}][batches][${idx}][qty]" class="form-control" step="0.001" required>
            </td>
            <td class="text-center">
              <button type="button" class="btn btn-sm btn-danger removeBatch">
                <i class="fa fa-trash"></i>
              </button>
            </td>
          </tr>`;
        tbody.append(row);
      });
    })
    // عند اختيار دفعة، جلب الوحدات وتعيين الحد الأقصى
    .on('change','.batch-select',function(){
      const $sel = $(this);
      const batchId = $sel.val();
      const $unitSel = $sel.closest('tr').find('.unit-select');
      $unitSel.html('<option>جاري التحميل…</option>');
      if(!batchId) { return $unitSel.html('<option value="">اختر الوحدة</option>'); }
      $.getJSON(`/api/materials/units/${batchId}`, units=>{
        let html = '<option value="">اختر الوحدة</option>';
        units.forEach(u=> html += `<option value="${u.id}">${u.name}</option>`);
        $unitSel.html(html);
        const avail = $sel.find('option:selected').data('qty');
        $sel.closest('tr').find('input[name$="[qty]"]').attr('max', avail);
      });
    });
});
</script>
