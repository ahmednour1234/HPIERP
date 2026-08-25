{{-- resources/views/admin-views/purchases/create.blade.php --}}
@extends('layouts.admin.app')

@section('title', 'إنشاء فاتورة شراء')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
<style>
  body { background: #f0f8ff; }
  .invoice-card { border-radius: .5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
  .invoice-header { background: #87cefa; color: #fff; padding:1rem 1.5rem; display:flex; justify-content:space-between; }
  .invoice-table th, .invoice-table td { vertical-align: middle; }
  .invoice-table thead { background: #e6f7ff; }
  .invoice-table tfoot { background: #fafafa; font-weight:600; }
  .btn-add { background: #1cc88a; color:#fff; }
  .btn-remove { background: #e74a3b; color:#fff; }
  .summary-box { margin-top:1rem; font-size:1.05rem; }
</style>
@endpush

@section('content')
<div class="container py-4">
  <div class="card invoice-card">
    <div class="invoice-header">
      <h3>فاتورة شراء جديدة</h3>
      <div id="currentDateTime"></div>
    </div>
    <div class="card-body">
      <form id="purchaseForm" enctype="multipart/form-data">
        @csrf

        <!-- Header -->
        <div class="row g-3 mb-4">
          <div class="col-md-3">
            <label>المورد</label>
            <select name="supplier_id" class="form-control" required>
              <option value="">اختر المورد</option>
              @foreach($suppliers as $s)
                <option value="{{ $s->id }}">{{ $s->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <label>الحالة</label>
            <select name="status" class="form-control" required>
              <option value="draft">مسودة</option>
              <option value="executed">منفذة</option>
            </select>
          </div>
          <div class="col-md-2">
            <label>نوع الدفع</label>
            <select name="payment_type" id="paymentType" class="form-control" required>
              <option value="cash">كاش</option>
              <option value="credit">آجل</option>
            </select>
          </div>
          <div class="col-md-2">
            <label>الحساب</label>
            <select name="account_id" id="accountSelect" class="form-control" required>
              <option value="">اختر الحساب</option>
              @foreach($accounts as $acct)
                <option value="{{ $acct->id }}">{{ $acct->account }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label>المبلغ المدفوع</label>
            <input name="paid_amount" id="paidAmount" type="number" step="0.01" class="form-control" required>
          </div>
        </div>

        <!-- Items -->
        <div class="table-responsive mb-3">
          <table class="table table-bordered invoice-table" id="itemsTable">
            <thead>
              <tr>
                <th>مادة</th><th>وحدة</th><th>كمية</th><th>سعر الوحدة</th>
                <th>خصم</th><th>ضريبة</th><th>قيمة الضريبة</th>
                <th>تاريخ الانتهاء</th><th>كود الدفعة</th><th>الإجمالي</th>
                <th><button type="button" id="addRow" class="btn btn-sm btn-add">+</button></th>
              </tr>
            </thead>
            <tbody>
              <tr class="item-row">
                <td>
                  <select name="items[0][material]" class="form-control material-select" required>
                    <option value="">اختر</option>
                    @foreach($materials as $m)
                      <option value="{{ $m->id }}"
                        data-tax-id="{{ $m->tax->id }}"
                        data-tax-name="{{ $m->tax->name }}"
                        data-tax-rate="{{ $m->tax->rate }}">
                        {{ $m->name }}
                      </option>
                    @endforeach
                  </select>
                </td>
                <td><select name="items[0][unit]" class="form-control unit-select" required><option>اختر</option></select></td>
                <td><input name="items[0][quantity]" type="number" step="0.001" class="form-control qty" required></td>
                <td><input name="items[0][unit_price]" type="number" step="0.01" class="form-control price" required></td>
                <td><input name="items[0][discount]" type="number" step="0.01" class="form-control discount" value="0" required></td>
                <td>
                  <select name="items[0][tax_id]" class="form-control tax-select" readonly>
                    <option>--</option>
                  </select>
                  <input type="hidden" class="tax-rate" name="items[0][tax_rate]" value="0">
                </td>
                <td class="tax-value">0.00</td>
                <td><input name="items[0][expiration_date]" type="date" class="form-control"></td>
                <td><input name="items[0][unique_code]" type="text" class="form-control" value="سيتم توليده" readonly></td>
                <td class="line-total">0.00</td>
                <td><button type="button" class="btn btn-sm btn-remove removeRow">–</button></td>
              </tr>
            </tbody>
            <tfoot>
              <tr>
                <td colspan="6" class="text-end">المجموع الفرعي</td>
                <td id="sumTax">0.00</td>
                <td colspan="2"></td>
                <td id="sumTotal">0.00</td>
                <td></td>
              </tr>
            </tfoot>
          </table>
        </div>

        <!-- Summary -->
        <div class="row summary-box">
          <div class="col-md-4">الإجمالي الفرعي: <span id="sumSub">0.00</span></div>
          <div class="col-md-4">إجمالي الخصم: <span id="sumDisc">0.00</span></div>
          <div class="col-md-4">إجمالي الضريبة: <span id="sumTaxTotal">0.00</span></div>
        </div>

        <div class="text-end mt-4">
          <button type="submit" class="btn btn-primary px-4">حفظ الفاتورة</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script>
$(function(){
  function showNow(){
    const now = new Date();
    $('#currentDateTime').text(now.toLocaleDateString('ar-EG')+' '+now.toLocaleTimeString('ar-EG'));
  }
  showNow(); setInterval(showNow,60000);

  let rowIdx = 1;

  function bindMaterialChange($row){
    $row.find('.material-select').on('change', function(){
      const opt      = $(this).find('option:selected'),
            matId    = opt.val(),
            $unit    = $row.find('.unit-select'),
            $taxSel  = $row.find('.tax-select'),
            rate     = opt.data('tax-rate'),
            taxId    = opt.data('tax-id'),
            taxName  = opt.data('tax-name');

      $taxSel.html(`<option value="${taxId}">${taxName} (${rate}%)</option>`);
      $row.find('.tax-rate').val(rate);

      $unit.html('<option>جاري...</option>');
      if (! matId) return $unit.html('<option>اختر</option>');

      $.getJSON(`/api/materials/${matId}/units`, function(units){
        let html = '<option>اختر</option>';
        units.forEach(u => html += `<option value="${u.id}">${u.unit_type}</option>`);
        $unit.html(html);
      });
    });
  }

  function recalcRow($row){
    const qty   = parseFloat($row.find('.qty').val())   || 0,
          price = parseFloat($row.find('.price').val()) || 0,
          disc  = parseFloat($row.find('.discount').val()) || 0,
          taxR  = parseFloat($row.find('.tax-rate').val()) || 0;

    const sub    = qty * price,
          after  = sub - disc,
          taxVal = after * taxR / 100,
          total  = after + taxVal;

    $row.find('.tax-value').text(taxVal.toFixed(2));
    $row.find('.line-total').text(total.toFixed(2));

    return { sub, disc, taxVal, total };
  }

  function recalcAll(){
    let sSub=0, sDisc=0, sTax=0, sTot=0;

    $('#itemsTable tbody tr').each(function(){
      const vals = recalcRow($(this));
      sSub  += vals.sub;
      sDisc += vals.disc;
      sTax  += vals.taxVal;
      sTot  += vals.total;
    });

    $('#sumSub').text(sSub.toFixed(2));
    $('#sumDisc').text(sDisc.toFixed(2));
    $('#sumTaxTotal').text(sTax.toFixed(2));
    $('#sumTax').text(sTax.toFixed(2));
    $('#sumTotal').text(sTot.toFixed(2));

    if ($('#paymentType').val() === 'cash') {
      $('#paidAmount').val(sTot.toFixed(2)).prop('readonly', true);
    } else {
      $('#paidAmount').prop('readonly', false).attr('max', sTot.toFixed(2));
    }
  }

  $('#addRow').click(function(){
    const $new = $('#itemsTable tbody tr.item-row:first').clone();
    $new.find('input, select').each(function(){
      const name = $(this).attr('name').replace(/\d+/, rowIdx);
      $(this).attr('name', name).val('');
    });
    $new.find('.tax-value, .line-total').text('0.00');
    bindMaterialChange($new);
    $('#itemsTable tbody').append($new);
    rowIdx++;
    recalcAll();
  });

  $('#itemsTable').on('click', '.removeRow', function(){
    if ($('#itemsTable tbody tr').length > 1) {
      $(this).closest('tr').remove();
      recalcAll();
    }
  });

  $('#itemsTable').on('input', '.qty, .price, .discount', recalcAll);
  $('#paymentType, #accountSelect').on('change', recalcAll);

  bindMaterialChange($('#itemsTable tbody tr'));
  recalcAll();

  $('#purchaseForm').submit(function(e){
    e.preventDefault();
    const fd = new FormData(this);
    $.ajax({
      url: "{{ route('admin.purchases.store') }}",
      method: 'POST',
      data: fd,
      processData: false,
      contentType: false,
      success(){
        toastr.success('تم حفظ الفاتورة');
        window.location = "{{ route('admin.purchases.index') }}";
      },
      error(xhr){
        const msg = xhr.responseJSON?.message;
        if (msg) toastr.error(msg);
        if (xhr.status === 422) {
          $.each(xhr.responseJSON.errors, (_, v) => toastr.error(v[0]));
        }
      }
    });
  });
});
</script>
