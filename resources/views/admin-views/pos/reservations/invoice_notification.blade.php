@extends('layouts.admin.app')

@section('title', 'طلب صرف مخزون')

@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/custom.css') }}"/>
    <style>
        .reservation-invoice-page {
            background: #f4f8fb;
            min-height: calc(100vh - 70px);
            padding-top: 22px;
            padding-bottom: 34px;
        }
        .ri-shell {
            max-width: 1500px;
            margin: 0 auto;
        }
        .ri-hero,
        .ri-card,
        .ri-panel,
        .ri-summary {
            background: #fff;
            border: 1px solid #d9e6f2;
            border-radius: 8px;
            box-shadow: 0 12px 30px rgba(22, 48, 76, .08);
        }
        .ri-hero {
            padding: 18px 20px;
            margin-bottom: 16px;
        }
        .ri-title {
            color: #142f51;
            font-size: 24px;
            font-weight: 800;
            margin: 0;
        }
        .ri-subtitle {
            color: #60758c;
            font-size: 13px;
            margin: 6px 0 0;
        }
        .ri-chip {
            background: #e8f1fb;
            border: 1px solid #d2e3f4;
            border-radius: 8px;
            color: #17365f;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-weight: 800;
            padding: 8px 12px;
        }
        .ri-card {
            padding: 14px;
        }
        .ri-label {
            color: #697f97;
            display: block;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 7px;
        }
        .ri-value {
            color: #102f51;
            font-size: 16px;
            font-weight: 800;
        }
        .ri-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
            margin: 16px 0;
        }
        .ri-toolbar-group {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .ri-panel {
            overflow: hidden;
        }
        .ri-panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 16px;
            border-bottom: 1px solid #d9e6f2;
            background: linear-gradient(135deg, #eef6fb 0%, #fff 100%);
        }
        .ri-panel-title {
            color: #17365f;
            font-size: 18px;
            font-weight: 800;
            margin: 0;
        }
        .ri-panel-kicker {
            color: #637992;
            font-size: 12px;
            margin: 4px 0 0;
        }
        .ri-table-wrap {
            overflow: auto;
        }
        .ri-table {
            border-collapse: separate;
            border-spacing: 0;
            margin: 0;
            min-width: 1220px;
            width: 100%;
        }
        .ri-table th,
        .ri-table td {
            border-bottom: 1px solid #dbe7f3;
            border-left: 1px solid #dbe7f3;
            padding: 11px 10px;
            text-align: center;
            vertical-align: middle;
        }
        .ri-table thead th {
            background: #17365f;
            color: #fff;
            font-size: 12px;
            font-weight: 800;
            position: sticky;
            top: 0;
            z-index: 2;
        }
        .ri-table tbody tr:nth-child(even) td {
            background: #f7fbff;
        }
        .ri-table tbody tr:hover td {
            background: #eef7ff;
        }
        .ri-product-name {
            color: #15345b;
            font-weight: 800;
            line-height: 1.5;
            text-align: right !important;
            min-width: 150px;
        }
        .ri-muted {
            color: #7a8da3;
            font-size: 12px;
            font-weight: 600;
        }
        .ri-table .form-control {
            border-color: #d8e5f2;
            border-radius: 7px;
            height: 38px;
            min-width: 86px;
            text-align: center;
        }
        .ri-summary {
            padding: 16px;
            position: sticky;
            top: 85px;
        }
        .ri-summary-title {
            color: #17365f;
            font-size: 17px;
            font-weight: 800;
            margin-bottom: 12px;
        }
        .ri-total-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            border-bottom: 1px solid #e5eef7;
            padding: 9px 0;
        }
        .ri-total-row label,
        .ri-total-row span {
            color: #637992;
            font-weight: 700;
            margin: 0;
        }
        .ri-total-row input {
            border: 0;
            color: #102f51;
            font-size: 16px;
            font-weight: 800;
            max-width: 150px;
            text-align: left;
            background: transparent;
        }
        .ri-total-final {
            background: #17365f;
            border-radius: 8px;
            margin-top: 12px;
            padding: 12px;
        }
        .ri-total-final label,
        .ri-total-final input {
            color: #fff;
        }
        .ri-discount-box {
            background: #f8fbfe;
            border: 1px solid #e1edf7;
            border-radius: 8px;
            margin-top: 14px;
            padding: 12px;
        }
        .ri-radio-group {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }
        .ri-radio-group label {
            color: #17365f;
            font-weight: 700;
            margin: 0;
        }
        .ri-submit-bar {
            display: flex;
            justify-content: flex-end;
            margin-top: 14px;
        }
        #productModal {
            align-items: center;
            background: rgba(15, 35, 62, .45);
            inset: 0;
            justify-content: center;
            padding: 18px;
            position: fixed;
            z-index: 1060;
        }
        #productModal[style*="block"] {
            display: flex !important;
        }
        #productModal .modal-content {
            border: 0;
            border-radius: 8px;
            box-shadow: 0 20px 60px rgba(15, 35, 62, .28);
            max-width: 520px;
            padding: 20px;
            width: 100%;
        }
        #productModal .close {
            cursor: pointer;
            font-size: 24px;
            line-height: 1;
            position: absolute;
            inset-inline-end: 16px;
            top: 12px;
        }
        @media (max-width: 991.98px) {
            .ri-summary {
                position: static;
            }
        }
        @media print {
            @page {
                size: A4 landscape;
                margin: 7mm;
            }
            #headerMain,
            #headerFluid,
            #headerDouble,
            #sidebarMain,
            .navbar,
            .navbar-vertical-aside,
            .direction-toggle,
            .footer,
            footer,
            #loading,
            .modal,
            .modal-backdrop,
            .non-printable {
                display: none !important;
            }
            body,
            #content,
            .reservation-invoice-page {
                background: #fff !important;
            }
            #content,
            main#content,
            .main {
                margin: 0 !important;
                max-width: none !important;
                min-height: 0 !important;
                padding: 0 !important;
                width: 100% !important;
            }
            .reservation-invoice-page {
                padding: 0 !important;
            }
            .ri-hero,
            .ri-card,
            .ri-panel,
            .ri-summary {
                border: 1px solid #9fb5cb !important;
                box-shadow: none !important;
            }
            .ri-hero {
                margin-bottom: 5mm !important;
                padding: 5mm !important;
            }
            .ri-title {
                font-size: 18px !important;
            }
            .ri-card,
            .ri-summary {
                padding: 7px !important;
            }
            .ri-panel-header,
            .ri-panel-kicker,
            .ri-submit-bar,
            .ri-toolbar {
                display: none !important;
            }
            .ri-table-wrap {
                overflow: visible !important;
            }
            .ri-table {
                border-collapse: collapse !important;
                font-size: 7px;
                min-width: 0 !important;
            }
            .ri-table th,
            .ri-table td {
                border: 1px solid #9fb5cb !important;
                padding: 2px 3px !important;
            }
            .ri-table thead th {
                background: #dcebf7 !important;
                color: #111 !important;
                position: static !important;
            }
            .ri-table .form-control,
            .ri-total-row input {
                border: 0 !important;
                height: auto !important;
                min-width: 0 !important;
                padding: 0 !important;
            }
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $details = collect(json_decode($reserveProduct->data) ?: []);
        $sub_total = 0;
        $total_tax = 0;
        $reservationType = (int) $reserveProduct->type;
    @endphp

    <div class="content container-fluid reservation-invoice-page" dir="rtl">
        <div class="ri-shell">
            @if(session('message'))
                <div class="alert alert-danger">{{ session('message') }}</div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <div class="ri-hero">
                <div class="row align-items-center">
                    <div class="col-lg-7 mb-3 mb-lg-0">
                        <h1 class="ri-title">مراجعة طلب صرف المخزون</h1>
                        <p class="ri-subtitle">راجع الكميات والأرصدة قبل تأكيد الطلب وتحويله إلى مخزون المندوب.</p>
                    </div>
                    <div class="col-lg-5 text-lg-left">
                        <span class="ri-chip">
                            <i class="tio-receipt-outlined"></i>
                            رقم الطلب: {{ $reserveProduct['id'] }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <div class="ri-card">
                        <span class="ri-label">المندوب الطالب</span>
                        <div class="ri-value">{{ $reserveProduct->seller->f_name . ' ' . $reserveProduct->seller->l_name }}</div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="ri-card">
                        <span class="ri-label">تاريخ الطلب</span>
                        <div class="ri-value">{{ date('Y/m/d h:i A', strtotime($reserveProduct['created_at'])) }}</div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="ri-card">
                        <span class="ri-label">نوع الحركة</span>
                        <div class="ri-value">{{ $reservationType === 7 ? 'مرتجع من المندوب' : 'صرف إلى المندوب' }}</div>
                    </div>
                </div>
            </div>

            <form id="reservation-form" method="POST" action="{{ route('admin.pos.storeplaceorder') }}">
                @csrf
                <input type="hidden" name="cart" id="cart-input">
                <input type="hidden" name="reservation_id" value="{{ $reserveProduct['id'] }}">
                <input type="hidden" name="id" value="{{ $reserveProduct->id }}">
                <input type="hidden" name="type" value="{{ $reserveProduct->type }}">
                <input type="hidden" name="user_id" value="{{ $reserveProduct->customer_id }}">
                <input type="hidden" name="payment_id" value="9">

                <div class="ri-panel mb-3">
                    <div class="ri-panel-header">
                        <div>
                            <h2 class="ri-panel-title">مندوب التسليم</h2>
                            <p class="ri-panel-kicker">اختر المندوب الذي سيتم تسجيل المخزون عليه.</p>
                        </div>
                    </div>
                    <div class="p-3">
                        <label class="ri-label" for="owner_id">مندوب التسليم</label>
                        <select name="owner_id" id="owner_id" class="form-control">
                            <option value="">اختر مندوب التسليم</option>
                            @foreach ($sellers as $seller)
                                <option value="{{ $seller->id }}" {{ $reserveProduct->seller_id == $seller->id ? 'selected' : '' }}>
                                    {{ trim(($seller->f_name ?? '') . ' ' . ($seller->l_name ?? '')) ?: $seller->f_name }}
                                </option>
                            @endforeach
                        </select>

                        @if (!empty($reserveProduct->note))
                            <div class="alert alert-warning mt-3 mb-0">
                                <strong>ملاحظة المندوب:</strong>
                                {{ $reserveProduct->note }}
                            </div>
                        @endif
                    </div>
                </div>

                <div class="ri-toolbar non-printable">
                    <div class="ri-toolbar-group">
                        <button type="button" id="select-zero-stock" class="btn btn-outline-secondary">
                            <i class="tio-checkmark-circle-outlined"></i> تحديد الكميات الصفرية
                        </button>
                        <button type="button" id="remove-zero-stock" class="btn btn-danger">
                            <i class="tio-delete-outlined"></i> حذف الكميات الصفرية
                        </button>
                    </div>
                    <div class="ri-toolbar-group">
                        <button type="button" id="add-product" class="btn btn-primary">
                            <i class="tio-add"></i> إضافة منتج
                        </button>
                        @if ($reserveProduct->active === 1)
                            <button type="submit" id="confirm-order" class="btn btn-success">
                                <i class="tio-done"></i> تأكيد الطلب
                            </button>
                        @endif
                    </div>
                </div>

                <div class="row">
                    <div class="col-xl-9 mb-3 mb-xl-0">
                        <div class="ri-panel">
                            <div class="ri-panel-header">
                                <div>
                                    <h2 class="ri-panel-title">تفاصيل المنتجات</h2>
                                    <p class="ri-panel-kicker">الكميات والأسعار والضريبة يتم تحديثها تلقائيًا عند تعديل الكمية.</p>
                                </div>
                                <span class="badge badge-soft-primary">{{ $details->count() }} منتج</span>
                            </div>

                            <div class="ri-table-wrap">
                                <table class="ri-table" id="product-table">
                                    <thead>
                                        <tr>
                                            <th>م</th>
                                            <th>المنتج</th>
                                            <th>الكود</th>
                                            <th>السعر</th>
                                            <th>الكمية</th>
                                            <th>الرصيد</th>
                                            <th>رصيد العميل</th>
                                            <th>رصيد الشركة</th>
                                            <th>الخصم</th>
                                            <th>الإجمالي</th>
                                            <th>الضريبة</th>
                                            <th>الصافي</th>
                                            @if ($reserveProduct->active === 1)
                                                <th class="non-printable">إجراء</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($details as $key => $detail)
                                            @php
                                                $product = \App\Models\Product::find($detail->product_id);
                                            @endphp
                                            @continue(!$product)
                                            @php
                                                $totalQuantity = \App\Models\Product::where('id', $product->id)->sum('quantity');
                                                $priceValue = (float) ($detail->price ?? 0);
                                                $price = number_format($priceValue, 2);
                                                $stock = (float) ($detail->stock ?? 0);
                                                $balance = (float) ($detail->balance ?? 0);
                                                $productTax = (float) ($product->tax ?? 0);
                                                $totalPrice = $stock * $priceValue;
                                                $taxAmount = $totalPrice * $productTax / 100;
                                                $netTotal = $totalPrice + $taxAmount;
                                                $customerStock = $reservationType === 4 ? $stock + $balance : $balance - $stock;
                                                $sub_total += $totalPrice;
                                                $total_tax += $taxAmount;
                                            @endphp

                                            <tr data-id="{{ $key }}">
                                                <td>{{ $key + 1 }}</td>
                                                <td class="ri-product-name">
                                                    {{ $product->name }}
                                                    <div class="ri-muted">ضريبة: {{ $productTax }}%</div>
                                                </td>
                                                <td><strong id="product-code">{{ $product->product_code }}</strong></td>
                                                <td>
                                                    <input type="text" name="price" class="form-control form-control-sm" value="{{ $price }}" readonly>
                                                </td>
                                                <td>
                                                    <input type="number" name="data[{{ $key }}][stock]" id="stock_{{ $key }}" value="{{ $stock }}" class="form-control form-control-sm quantity" min="1" max="1000000000">
                                                    <input type="hidden" name="data[{{ $key }}][product_id]" value="{{ $product->id }}">
                                                    <input type="hidden" name="data[{{ $key }}][product_name]" value="{{ $product->name }}">
                                                </td>
                                                <td>
                                                    <input type="number" name="data[{{ $key }}][balance]" value="{{ $balance }}" class="form-control form-control-sm" min="0">
                                                </td>
                                                <td>
                                                    <input type="text" name="data[{{ $key }}][totalQtyBalance]" value="{{ $customerStock }}" class="form-control form-control-sm" readonly>
                                                </td>
                                                <td>
                                                    <input type="text" name="total_quantity" value="{{ $totalQuantity }}" class="form-control form-control-sm" readonly>
                                                    <input type="hidden" name="typeproduct" value="{{ $reserveProduct->type }}">
                                                </td>
                                                <td><strong id="discount-amount">0.00</strong></td>
                                                <td><span class="total-price">{{ number_format($totalPrice, 2) }}</span></td>
                                                <td>
                                                    <span class="tax-amount">{{ number_format($taxAmount, 2) }}</span>
                                                    <input type="hidden" name="tax_amount" value="{{ $taxAmount }}">
                                                </td>
                                                <td>
                                                    <strong class="net-total">{{ number_format($netTotal, 2) }}</strong>
                                                    <input type="hidden" name="tax_amount" value="{{ $taxAmount }}">
                                                </td>
                                                @if ($reserveProduct->active === 1)
                                                    <td class="non-printable">
                                                        <button type="button" class="btn btn-danger btn-sm remove-product" data-id="{{ $key }}">
                                                            حذف
                                                        </button>
                                                    </td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3">
                        <div class="ri-summary">
                            <div class="ri-summary-title">ملخص الفاتورة</div>

                            <div class="ri-total-row">
                                <label for="subtotal">الإجمالي</label>
                                <input type="text" name="subtotal" id="subtotal" value="{{ number_format($sub_total, 2, '.', '') }}" readonly>
                            </div>

                            <div class="ri-total-row">
                                <label for="discount-total">إجمالي الخصم</label>
                                <input type="text" name="extra_discount" id="discount-total" value="0.00" readonly>
                            </div>

                            <div class="ri-total-row">
                                <label for="tax">الضريبة</label>
                                <input type="text" name="tax" id="tax" value="{{ number_format($total_tax, 2, '.', '') }}" readonly>
                            </div>

                            <div class="ri-discount-box">
                                <span class="ri-label">نوع الخصم</span>
                                <div class="ri-radio-group">
                                    <label>
                                        <input type="radio" name="discount_type" value="percentage" checked> نسبة
                                    </label>
                                    <label>
                                        <input type="radio" name="discount_type" value="flat"> مبلغ
                                    </label>
                                </div>
                                <label class="ri-label" for="invoice-discount">قيمة الخصم</label>
                                <input type="number" id="invoice-discount" name="he" value="0" class="form-control" min="0">
                            </div>

                            <div class="ri-total-row ri-total-final">
                                <label for="total-invoice">صافي الفاتورة</label>
                                <input type="text" id="total-invoice" name="total_invoice" value="{{ number_format($sub_total + $total_tax, 2, '.', '') }}" readonly>
                            </div>

                            @if ($reserveProduct->active === 1)
                                <div class="ri-submit-bar non-printable">
                                    <button type="submit" class="btn btn-success btn-block">
                                        <i class="tio-done"></i> تأكيد الطلب
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </form>

            <div id="productModal" class="modal" style="display: none;">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h4 class="mb-3">اختيار منتج</h4>
                    <input type="text" id="product-search" class="form-control mb-2" placeholder="ابحث باسم المنتج أو الكود">
                    <select id="modal-product-select" class="form-control">
                        <option value="">اختر المنتج</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}" data-name="{{ $product->name }}" data-code="{{ $product->product_code }}">
                                {{ $product->name }} ({{ $product->product_code }})
                            </option>
                        @endforeach
                    </select>
                    <button id="select-product" class="btn btn-primary mt-3">اختيار المنتج</button>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('script_2')
<script>

let products = @json($products);
let productIndex = {{ count(json_decode($reserveProduct->data)) }};

document.addEventListener('DOMContentLoaded', function () {
    // Function to update the cart input field with the current cart data
    function updateCartInput() {
        const cart = [];
        const rows = document.querySelectorAll('#product-table tbody tr');
        rows.forEach(row => {
            const productId = row.querySelector('input[name^="data"][name$="[product_id]"]').value;
            const quantity = row.querySelector('input[name^="data"][name$="[stock]"]').value;
            const balanceInput = row.querySelector('input[name$="[balance]"]');
            const balance = parseFloat(balanceInput.value) || 0;
            const stock = row.querySelector('input[name$="[balance]"]') + row.querySelector('input[name^="data"][name$="[stock]"]').value;
            cart.push({ id: productId, quantity: parseInt(quantity), balance: parseFloat(balance) });
        });
        document.getElementById('cart-input').value = JSON.stringify(cart);
    }

    // Function to update the total invoice amount
function updateInvoiceTotal() {
    let subtotal = 0;
    let totalTax = 0;
    let discountAmount = 0;

    const discountType = document.querySelector('input[name="discount_type"]:checked').value;
    const discountInput = document.querySelector('#invoice-discount');
    const discountValue = parseFloat(discountInput.value) || 0;

    // Calculate subtotal before discount and tax
    document.querySelectorAll('#product-table tbody tr').forEach(row => {
        const productId = row.querySelector('input[name^="data"][name$="[product_id]"]').value;
        const selectedProduct = products.find(p => p.id == productId);
        const quantity = parseInt(row.querySelector('input[name^="data"][name$="[stock]"]').value) || 0;
        const price = parseFloat(selectedProduct.selling_price) || 0;

        subtotal += price * quantity;
    });

    // Set discount input max value based on discount type
    if (discountType === 'flat') {
        discountInput.max = subtotal;
    } else if (discountType === 'percentage') {
        discountInput.max = 100;
    }

    // Apply discount
    let total = subtotal;
    if (discountType === 'flat') {
        discountAmount = Math.min(discountValue, subtotal);
        total -= discountAmount;
    } else if (discountType === 'percentage') {
        discountAmount = subtotal * (Math.min(discountValue, 100) / 100);
        total -= discountAmount;
    }

    // Calculate tax on the discounted total
    document.querySelectorAll('#product-table tbody tr').forEach(row => {
        const productId = row.querySelector('input[name^="data"][name$="[product_id]"]').value;
        const selectedProduct = products.find(p => p.id == productId);
        const taxRate = parseFloat(selectedProduct.tax) || 0;
        const quantity = parseInt(row.querySelector('input[name^="data"][name$="[stock]"]').value) || 0;
        const price = parseFloat(selectedProduct.selling_price) || 0;

        const priceQuantity = price * quantity;

        // Apply discount to the product price
        let individualDiscount = 0;
        if (discountType === 'percentage') {
            individualDiscount = priceQuantity * (Math.min(discountValue, 100) / 100);
        } else if (discountType === 'flat') {
            individualDiscount = (discountValue / subtotal) * priceQuantity;
        }

        const discountedPrice = priceQuantity - individualDiscount;
        const taxAmount = discountedPrice * (taxRate / 100);
        totalTax += taxAmount;

        // Update row-specific values
        row.querySelector('#discount-amount').textContent = individualDiscount.toFixed(2);
        row.querySelector('.tax-amount').textContent = taxAmount.toFixed(2);
        row.querySelector('.total-price').textContent = discountedPrice.toFixed(2);
        row.querySelector('.net-total').textContent = (discountedPrice + taxAmount).toFixed(2);
        
    });

// Update the summary totals
document.getElementById('subtotal').value = subtotal.toFixed(2);
document.getElementById('tax').value = totalTax.toFixed(2);
document.getElementById('discount-total').value = discountAmount.toFixed(2);
document.getElementById('total-invoice').value = (total + totalTax).toFixed(2);

}
function updateStock(row) {
    const quantityInput = row.querySelector('input[name^="data"][name$="[stock]"]');
    const balanceInput = row.querySelector('input[name^="data"][name$="[balance]"]');
    const typeInput = row.querySelector('input[name="typeproduct"]');
    const totalQtyBalanceInput = row.querySelector('input[name$="[totalQtyBalance]"]');

    if (!quantityInput || !balanceInput || !typeInput || !totalQtyBalanceInput) return;

    const quantity = parseFloat(quantityInput.value) || 0;
    const balance = parseFloat(balanceInput.value) || 0;
    const type = parseInt(typeInput.value) || 0;

    if (type === 4) {
        totalQtyBalanceInput.value = quantity + balance;
    } else {
        totalQtyBalanceInput.value = balance - quantity;
    }
}    // Function to attach event listeners
function attachEventListeners() {
    // Event listeners for stock quantity and balance inputs
    document.querySelectorAll('#product-table .quantity, #product-table input[name$="[balance]"]').forEach(input => {
        input.addEventListener('input', function() {
   const row = this.closest('tr');
                updateStock(row);  // Update stock when quantity changes
                updateInvoiceTotal();
            updateCartInput();
        });
    });

    // Event listener for discount input
    document.getElementById('invoice-discount').addEventListener('input', updateInvoiceTotal);

    // Event listeners for discount type radio buttons
    document.querySelectorAll('input[name="discount_type"]').forEach(radio => {
        radio.addEventListener('change', updateInvoiceTotal);
    });

    // Event listener for add-product button
    document.getElementById('add-product').addEventListener('click', function() {
        document.getElementById('productModal').style.display = 'block';
    });

    // Event listener for closing the modal
    document.querySelector('.close').addEventListener('click', function() {
        document.getElementById('productModal').style.display = 'none';
    });

    // Event listener for selecting a product in the modal
document.getElementById('select-product').addEventListener('click', function() {
    const selectedProductId = document.getElementById('modal-product-select').value;
    
    if (selectedProductId) {
        // Check if the row already exists
        const existingRow = document.querySelector(`tr[data-id="${selectedProductId}"]`);
        if (existingRow) {
            return;
        }

        const selectedProduct = products.find(p => p.id == selectedProductId);
        const newRow = document.createElement('tr');
        newRow.dataset.id = selectedProductId;

        const quantity = 1;
const balance = parseFloat(selectedProduct.balance) || 0; // Ensure balance is a number
        const price = parseFloat(selectedProduct.selling_price) || 0;
        const taxRate = parseFloat(selectedProduct.tax) || 0;
        const stock = quantity + balance;
        const priceQuantity = price * quantity;
        let individualDiscount = 0;
        const discountType = document.querySelector('input[name="discount_type"]:checked').value;
        const discountValue = parseFloat(document.querySelector('#invoice-discount').value) || 0;

        if (discountType === 'percentage') {
            individualDiscount = priceQuantity * discountValue / 100;
        } else {
            individualDiscount = discountValue / ((priceQuantity * (taxRate / 100)) + priceQuantity) * 100;
        }

        const productTotalPrice = priceQuantity - individualDiscount;
        const taxAmount = (productTotalPrice * taxRate) / 100;
        const netProductPrice = productTotalPrice + taxAmount;
         // Assuming productStockData is already defined in your script
const productStockData = @json(\App\Models\Product::all()->groupBy('id')->mapWithKeys(function ($group, $id) {
    return [$id => $group->sum('quantity')];
}));

newRow.innerHTML = `
    <td>${productIndex + 1}</td>
    <td class="ri-product-name">
        ${selectedProduct.name}
        <div class="ri-muted">ضريبة: ${selectedProduct.tax}%</div>
    </td>
    <td><strong id="product-code">${selectedProduct.product_code}</strong></td>
    <td><input type="text" name="price" class="form-control form-control-sm" value="${selectedProduct.selling_price}" readonly></td>
    <td>
        <input type="number" name="data[${productIndex}][stock]" value="${quantity}" class="form-control form-control-sm quantity" min="1" max="100000">
        <input type="hidden" name="data[${productIndex}][product_id]" value="${selectedProduct.id}">
        <input type="hidden" name="data[${productIndex}][product_name]" value="${selectedProduct.name}">
    </td>
    <td>
        <input type="number" name="data[${productIndex}][balance]" value="0" class="form-control form-control-sm">
    </td>
    <td>
        <input type="text" name="data[${productIndex}][totalQtyBalance]" value="${stock}" class="form-control form-control-sm" readonly>
    </td>
    <td>
        <input type="text" name="total_quantity" value="${productStockData[selectedProduct.id] || 0}" class="form-control form-control-sm" readonly>
        <input type="hidden" name="typeproduct" value="{{ $reserveProduct->type }}">
    </td>
    <td><strong id="discount-amount">${individualDiscount.toFixed(2)}</strong></td>
    <td>
        <span class="total-price">${netProductPrice.toFixed(2)}</span>
        <input type="hidden" name="total_price" value="${netProductPrice.toFixed(2)}">
    </td>
    <td>
        <span class="tax-amount">${taxAmount.toFixed(2)}</span>
        <input type="hidden" name="tax_amount" value="${taxAmount.toFixed(2)}">
    </td>
    <td>
        <strong class="net-total">${netProductPrice.toFixed(2)}</strong>
    </td>
    <td class="non-printable">
        <button type="button" class="btn btn-danger btn-sm remove-product" data-id="${productIndex}">
            حذف
        </button>
    </td>
`;

        document.querySelector('#product-table tbody').appendChild(newRow);
        productIndex++;

        attachEventListeners();
        updateInvoiceTotal();
        updateCartInput();
        document.getElementById('productModal').style.display = 'none';
    }
});

    // Event listener for 'Select Products with Stock = 0' button
    document.getElementById('select-zero-stock').addEventListener('click', function() {
        document.querySelectorAll('#product-table tbody tr').forEach(row => {
            const stockInput = row.querySelector('input[name$="[stock]"]');
            const balanceInput = row.querySelector('input[name$="[balance]"]');
            const stock = parseInt(stockInput.value) || 0;
            const balance = parseInt(balanceInput.value) || 0;
            const totalQtyBalanceInput = row.querySelector('input[name$="[totalQtyBalance]"]');
            const totalQtyBalance = parseInt(totalQtyBalanceInput.value) || 0;
            if (stock === 0 || totalQtyBalance === 0) {
                row.style.backgroundColor = 'lightgray';
            }
        });
    });

    // Event listener for 'Remove Products with Stock = 0' button
    document.getElementById('remove-zero-stock').addEventListener('click', function() {
        const rowsToRemove = [];
        document.querySelectorAll('#product-table tbody tr').forEach(row => {
            const stockInput = row.querySelector('input[name$="[stock]"]');
            const balanceInput = row.querySelector('input[name$="[balance]"]');
            const stock = parseInt(stockInput.value) || 0;
            const balance = parseInt(balanceInput.value) || 0;
            const totalQtyBalanceInput = row.querySelector('input[name$="[totalQtyBalance]"]');
            const totalQtyBalance = parseInt(totalQtyBalanceInput.value) || 0;
            if (stock === 0 || totalQtyBalance === 0) {
                rowsToRemove.push(row);
            }
        });
        rowsToRemove.forEach(row => row.remove());
        updateInvoiceTotal();
        updateCartInput();
    });

    // Event listener for removing a product
    document.querySelectorAll('.remove-product').forEach(button => {
        button.addEventListener('click', function() {
            const row = this.closest('tr');
            row.remove();
            updateInvoiceTotal();
            updateCartInput();
        });
    });
}


    attachEventListeners();
    updateInvoiceTotal();
});
// Add event listener for product search
document.getElementById('product-search').addEventListener('input', function() {
    const query = this.value.toLowerCase();
    const options = document.querySelectorAll('#modal-product-select option');
    options.forEach(option => {
        const name = (option.dataset.name || '').toLowerCase();
        const code = (option.dataset.code || '').toLowerCase();
        if (name.includes(query) || code.includes(query)) {
            option.style.display = '';
        } else {
            option.style.display = 'none';
        }
    });
});
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('product-search');
    const productSelect = document.getElementById('modal-product-select');
    const allOptions = Array.from(productSelect.options);

    searchInput.addEventListener('input', function() {
        const query = searchInput.value.toLowerCase();

        // Clear existing options except the placeholder
        productSelect.innerHTML = '<option value="">اختر المنتج</option>';

        allOptions.forEach(option => {
            if (option.text.toLowerCase().includes(query)) {
                productSelect.appendChild(option);
            }
        });
    });

    attachEventListeners();
});
    document.addEventListener('DOMContentLoaded', function () {
        // Get the modal and the close button
        var modal = document.getElementById('productModal');
        var closeBtn = document.querySelector('.close');

        // Function to close the modal
        function closeModal() {
            modal.style.display = 'none';
            console.log('Modal closed'); // Perform any additional actions here
        }

        // When the user clicks the close button, close the modal
        closeBtn.addEventListener('click', closeModal);

        // Additional way to close modal by clicking outside of the modal content
        window.addEventListener('click', function(event) {
            if (event.target == modal) {
                closeModal();
            }
        });

        // Handle product selection
        document.getElementById('select-product').addEventListener('click', function() {
            var selectedProduct = document.getElementById('modal-product-select').value;
            if (selectedProduct) {
                console.log('Product selected:', selectedProduct);
                closeModal();
            } else {
                alert('Please select a product.');
            }
        });
    });
        document.addEventListener('DOMContentLoaded', function() {
        // Get the form element
        var form = document.getElementById('reservation-form');
        
        // Add an event listener to the form
        form.addEventListener('keydown', function(event) {
            // Check if the key pressed is Enter
            if (event.key === 'Enter') {
                event.preventDefault(); // Prevent form submission
            }
        });
    });
</script>

@endpush
