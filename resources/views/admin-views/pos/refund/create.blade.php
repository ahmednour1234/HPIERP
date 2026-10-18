@extends('layouts.admin.app')
@section('title', 'مرتجع مبيعات')
@section('content')
<div class="content container-fluid" dir="rtl">
    <div class="mb-4">
        <h2>مرتجع مبيعات</h2>
        <p class="text-muted mb-0">اختر العميل والفاتورة، ثم حدد الكميات وطريقة رد المبلغ.</p>
    </div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())
        <div class="alert alert-danger">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
    @endif
    <div class="card mb-3">
        <div class="card-body">
            <form method="get" action="{{ route('admin.pos.index', ['type' => 7]) }}" class="row align-items-end">
                <div class="col-md-5 form-group">
                    <label for="return-customer">١. العميل</label>
                    <select name="customer_id" id="return-customer" class="form-control js-select2-custom" required onchange="this.form.querySelector('[name=invoice_id]').value='';this.form.submit()">
                        <option value="">اختر العميل</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" @selected($customerId == $customer->id)>{{ $customer->name }} @if($customer->mobile) ({{ $customer->mobile }}) @endif</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5 form-group">
                    <label for="return-invoice">٢. الفاتورة الأصلية</label>
                    <select name="invoice_id" id="return-invoice" class="form-control" @disabled(!$customerId) onchange="this.form.submit()">
                        <option value="">اختر رقم الفاتورة</option>
                        @foreach($invoices as $sale)
                            <option value="{{ $sale->id }}" @selected($invoice && $invoice->id == $sale->id)>#{{ $sale->id }} — {{ optional($sale->created_at)->format('Y-m-d') }} — {{ number_format($sale->order_amount, 2) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 form-group"><button class="btn btn-outline-primary btn-block">عرض الفاتورة</button></div>
            </form>
            @if($customerId && $invoices->isEmpty())<p class="text-muted mb-0">لا توجد فواتير مبيعات لهذا العميل.</p>@endif
        </div>
    </div>
    @if($invoice)
    @php
        $base = $invoice->details->sum(fn ($line) => ($line->price - $line->discount_on_product + $line->tax_amount) * $line->quantity);
        $ratio = $base > 0 ? min(1, max(0, $invoice->order_amount / $base)) : 0;
    @endphp
    <form method="post" action="{{ route('admin.pos.invoice-return.store') }}" id="invoice-return-form">
        @csrf
        <input type="hidden" name="customer_id" value="{{ $customerId }}">
        <input type="hidden" name="invoice_id" value="{{ $invoice->id }}">
        <div class="card mb-3">
            <div class="card-header"><h4 class="mb-0">٣. أصناف الفاتورة #{{ $invoice->id }}</h4></div>
            <div class="table-responsive">
                <table class="table table-align-middle mb-0">
                    <thead class="thead-light"><tr><th>الصنف</th><th>قيمة الوحدة بعد الخصم والضريبة</th><th>الكمية المباعة</th><th>سبق إرجاعه</th><th>المتاح للإرجاع</th><th>كمية المرتجع</th></tr></thead>
                    <tbody>
                    @foreach($invoice->details as $line)
                        @php($remaining = max(0, $line->quantity - $line->quantity_returned))
                        @php($snapshot = json_decode($line->product_details, true))
                        @php($unit = ($line->price - $line->discount_on_product + $line->tax_amount) * $ratio)
                        <tr>
                            <td>{{ $snapshot['name'] ?? 'صنف #'.$line->product_id }}</td>
                            <td>{{ number_format($unit, 2) }} {{ \App\CPU\Helpers::currency_symbol() }}</td>
                            <td>{{ $line->quantity }}</td><td>{{ $line->quantity_returned ?? 0 }}</td><td>{{ $remaining }}</td>
                            <td><input data-refund-unit="{{ $unit }}" aria-label="كمية مرتجع {{ $snapshot['name'] ?? $line->product_id }}" type="number" class="form-control" style="max-width:110px" name="quantities[{{ $line->id }}]" value="{{ old('quantities.'.$line->id, 0) }}" min="0" max="{{ $remaining }}" step="1" @disabled($remaining <= 0)></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer text-muted">تُحسب قيمة المرتجع بسعر الفاتورة الأصلي مع الخصومات والضريبة.</div>
        </div>
        <div class="card">
            <div class="card-header"><h4 class="mb-0">إجمالي المرتجع: <span id="refund-preview">0.00</span> {{ \App\CPU\Helpers::currency_symbol() }}</h4></div>
            <div class="card-body row align-items-end">
                <div class="col-md-4 form-group">
                    <label for="return-method">٤. طريقة الإرجاع</label>
                    <select name="method" id="return-method" class="form-control" required>
                        <option value="cash" @selected(old('method') === 'cash')>رد المبلغ من حساب / خزينة</option>
                        <option value="credit" @selected(old('method') === 'credit')>إضافة المبلغ لرصيد العميل</option>
                    </select>
                </div>
                <div class="col-md-4 form-group" id="refund-account-wrap">
                    <label for="refund-account">حساب رد المبلغ</label>
                    <select name="account_id" id="refund-account" class="form-control">
                        <option value="">اختر الحساب</option>
                        @foreach($accounts as $account)<option value="{{ $account->id }}" @selected(old('account_id') == $account->id)>{{ $account->account }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-4 form-group"><button class="btn btn-success btn-block" id="execute-return">تنفيذ المرتجع</button></div>
            </div>
        </div>
    </form>
    @else
        <div class="card"><div class="card-body text-center text-muted py-5">اختر العميل والفاتورة لعرض الأصناف المتاحة للإرجاع.</div></div>
    @endif
</div>
@endsection
@push('script_2')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const method = document.getElementById('return-method');
    if (!method) return;
    function updateMethod() {
        const cash = method.value === 'cash';
        document.getElementById('refund-account-wrap').hidden = !cash;
        document.getElementById('refund-account').required = cash;
        document.getElementById('refund-account').disabled = !cash;
    }
    method.addEventListener('change', updateMethod);
    updateMethod();
    const quantities = document.querySelectorAll('[data-refund-unit]');
    function updateTotal() {
        let total = 0;
        quantities.forEach(input => { total += Math.max(0, Number(input.value) || 0) * Number(input.dataset.refundUnit); });
        document.getElementById('refund-preview').textContent = total.toFixed(2);
        document.getElementById('execute-return').disabled = !Array.from(quantities).some(input => !input.disabled && Number(input.value) > 0);
    }
    quantities.forEach(input => input.addEventListener('input', updateTotal));
    updateTotal();
    document.getElementById('invoice-return-form').addEventListener('submit', function () {
        document.getElementById('execute-return').disabled = true;
    });
});
</script>
@endpush
