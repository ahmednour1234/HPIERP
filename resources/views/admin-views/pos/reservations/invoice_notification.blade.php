@extends('layouts.admin.app')

@section('title', 'Reservations List')

@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/custom.css') }}"/>
    <style>
.center-button {
    display: flex;
    justify-content: center;
    align-items: center;
} 
    </style>
@endpush

@section('content')
@if(session('message'))
    <div class="alert alert-danger">
        {{ session('message') }}
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
@endif

    <div class="reservation-content text-center mb-3">
        <!--<h2 class="line-inone">{{ optional(\App\Models\BusinessSetting::where(['key' => 'shop_name'])->first())->value }}</h2>-->
        <!--<h5 class="style-inone">{{ optional(\App\Models\BusinessSetting::where(['key' => 'shop_address'])->first())->value }}</h5>-->
        <!--<h5 class="style-intwo">{{ \App\CPU\translate('Phone') }} : {{ optional(\App\Models\BusinessSetting::where(['key' => 'shop_phone'])->first())->value }}</h5>-->
        <!--<h5 class="style-intwo">{{ \App\CPU\translate('Email') }} : {{ optional(\App\Models\BusinessSetting::where(['key' => 'shop_email'])->first())->value }}</h5>-->
        <!--<h5 class="style-intwo">{{ \App\CPU\translate('Vat_registration_number') }} : {{ optional(\App\Models\BusinessSetting::where(['key' => 'vat_reg_no'])->first())->value }}</h5>-->

        <hr class="line-dot">

        <center class="mt-3">
            <h5>{{ \App\CPU\translate('reservation_ID') }} : {{ $reserveProduct['id'] }}</h5>
            <h5>{{ \App\CPU\translate('seller_name') }} : {{ $reserveProduct->seller->f_name . ' ' . $reserveProduct->seller->l_name }}</h5>
            <h5 class="font-inone fz-10">{{ date('d/M/Y h:i a', strtotime($reserveProduct['created_at'])) }}</h5>
        </center>

        <hr class="line-dot">

<form id="reservation-form" method="POST" action="{{ route('admin.pos.storeplaceorder') }}" class="p-4 border rounded bg-light shadow-sm">
    @csrf
    <input type="hidden" name="cart" id="cart-input">
    <input type="hidden" name="reservation_id" value="{{ $reserveProduct['id'] }}">
    <input type="hidden" name="id" value="{{ $reserveProduct->id }}">
    <input type="hidden" name="type" value="{{ $reserveProduct->type }}">
                <h5>{{ \App\CPU\translate('delviery_name') }} :Delivery Name</h5>

<select name="owner_id" class="form-control">
    <option value="">Select a Delivery</option>
    @foreach ($sellers as $seller)
        <option value="{{ $seller->id }}" {{ $reserveProduct->seller_id == $seller->id ? 'selected' : '' }}>
            {{ $seller->f_name }}
        </option>
    @endforeach
</select>
    <input type="hidden" name="user_id" value="{{ $reserveProduct->customer_id }}">
    <input type="hidden" name="payment_id" value="9">

    {{-- ملاحظة المندوب المكتوبة من التطبيق عند الطلب. تظهر قبل الجدول
         ليقرأها من يصرف البضاعة قبل التأكيد. --}}
    @if (!empty($reserveProduct->note))
        <div class="alert alert-warning mt-3 mb-0">
            <strong>{{ \App\CPU\translate('ملاحظة المندوب') }}:</strong>
            {{ $reserveProduct->note }}
        </div>
    @endif

    <div class="table-responsive">
        <table class="table table-bordered table-hover mt-3" id="product-table">
            <div class="mt-4 d-flex justify-content-between">
                <button type="button" id="select-zero-stock" class="btn btn-warning">Select Products</button>
                                <button type="button" id="add-product" class="btn btn-primary">Add Product</button>
                <button type="button" id="remove-zero-stock" class="btn btn-danger">Remove Products with Stock = 0</button>
                    <button type="button" id="confirm-order" class="btn btn-secondary">Confirm Order</button>
            </div>
            <thead class="thead-dark">
                <tr>
                    <th>{{ \App\CPU\translate('SL') }}</th>
                    <th>{{ \App\CPU\translate('DESC') }}</th>
                    <th>{{ \App\CPU\translate('Code') }}</th>
                    <th>{{ \App\CPU\translate('Price') }}</th>
                    <th>{{ \App\CPU\translate('QTY') }}</th>
                    <th>{{ \App\CPU\translate('Balance') }}</th>
                    <th>{{ \App\CPU\translate('Stock Customer') }}</th>
                    <th>{{ \App\CPU\translate('Stock Company') }}</th>
                    <th>{{ \App\CPU\translate('Discount Amount') }}</th>
                    <th>{{ \App\CPU\translate('Sub Total') }}</th>
                    <th>{{ \App\CPU\translate('Tax Amount') }}</th>
                    <th>{{ \App\CPU\translate('Net Total') }}</th>
                    @if ($reserveProduct->active === 1)
                        <th>{{ \App\CPU\translate('Actions') }}</th>
                    @endif
                </tr>
            </thead>
<tbody>
    @php
        $sub_total = 0;
        $total_tax = 0;
    @endphp

    @foreach (json_decode($reserveProduct->data) as $key => $detail)
        @php
            $product = \App\Models\Product::find($detail->product_id);
            $totalQuantity = \App\Models\Product::where('id', $product->id)->sum('quantity');
            $price = number_format($detail->price ?? 0, 2);
            $stock = $detail->stock;
            $balance = $detail->balance;
            $productTax = $product->tax;
            $totalPrice = $stock * ($detail->price ?? 0);
            $taxAmount = $totalPrice * $productTax / 100;
            $netTotal = $totalPrice + $taxAmount;
        @endphp

        <tr data-id="{{ $key }}">
            <td>{{ $key + 1 }}</td>
            <td>
                <span>{{ $product->name }}</span><br />
                {{ \App\CPU\translate('Tax') }} : {{ $productTax }}%
            </td>
            <td>
                <h5><span id="product-code">{{ $product->product_code }}</span></h5>
            </td>
            <td>
                <input type="text" name="price" class="form-control form-control-sm" value="{{ $price }}" style="width: 70px;" readonly>
            </td>
            <td>
                <input type="number" name="data[{{ $key }}][stock]" id="stock_{{ $key }}" value="{{ $stock }}" class="form-control form-control-sm quantity" min="1" max="1000000000" style="width: 70px;">
                <input type="hidden" name="data[{{ $key }}][product_id]" value="{{ $product->id }}">
                <input type="hidden" name="data[{{ $key }}][product_name]" value="{{ $product->name }}">
                <input type="hidden" name="data[{{ $key }}][balance]" value="{{ $balance }}">
            </td>
            <td>
                <input type="number" name="data[{{ $key }}][balance]" value="{{ $balance }}" class="form-control form-control-sm" min="0" >
            </td>
         <td>
    @if($reserveProduct->type == 4)
        <input type="text" name="data[{{ $key }}][totalQtyBalance]" value="{{ $stock + $balance }}" class="form-control form-control-sm" readonly>
    @else
        <input type="text" name="data[{{ $key }}][totalQtyBalance]" value="{{  $balance - $stock  }}" class="form-control form-control-sm" readonly>
    @endif
</td>

            <td>
                <input type="text" name="total_quantity" value="{{ $totalQuantity }}" class="form-control form-control-sm" readonly>
            </td>
          <td style="display: none;">
    <input type="hidden" name="typeproduct" value="{{ $reserveProduct->type }}">
</td>

            <td>
                <h5><span id="discount-amount">0.00</span></h5>
            </td>
            <td>
                <span class="total-price">{{ $totalPrice }}</span>
            </td>
            <td>
                <span class="tax-amount">{{ $taxAmount }}</span>
                <input type="hidden" name="tax_amount" value="{{ $taxAmount }}">
            </td>
            <td>
                <span class="net-total">{{ $netTotal }}</span>
                <input type="hidden" name="tax_amount" value="{{ $taxAmount }}">
            </td>
            @if ($reserveProduct->active === 1)
                <td>
                    <button type="button" class="btn btn-danger btn-sm remove-product" data-id="{{ $key }}">
                        {{ \App\CPU\translate('Remove') }}
                    </button>
                </td>
            @endif
        </tr>

        @php
            $sub_total += $totalPrice;
            $total_tax += $taxAmount;
        @endphp
    @endforeach
</tbody>
        </table>
    </div>

    <div class="mt-4">
<h5>{{ \App\CPU\translate('Total') }}: 
    <input type="text" name="subtotal" id="subtotal" class="font-weight-bold" value="{{ $sub_total }}" readonly>
</h5>

<h5>{{ \App\CPU\translate('Discount Total') }}: 
    <input type="text" name="extra_discount" id="discount-total" class="font-weight-bold" value="0.00" readonly>
</h5>

<h5>{{ \App\CPU\translate('Tax') }}: 
    <input type="text" name="tax" id="tax" class="font-weight-bold" value="{{ $total_tax }}" readonly>
</h5>

        @if ($reserveProduct->active === 1)
        <div class="form-group">
            <label>{{ \App\CPU\translate('Discount Type') }}:</label>
            <div>
                <label class="mr-2">
                    <input type="radio" name="discount_type" value="percentage" checked> {{ \App\CPU\translate('Percentage') }}
                </label>
                <label>
                    <input type="radio" name="discount_type" value="flat"> {{ \App\CPU\translate('Flat') }}
                </label>
            </div>
        </div>
        <div class="form-group text-center">
            <label class="d-block">{{ \App\CPU\translate('Discount') }}:</label>
            <input type="number" id="invoice-discount" name="he" value="0" class="form-control form-control-xl mx-auto" min="0" style="width: 150px;">
        </div>
        @endif
        <h5>{{ \App\CPU\translate('Total Invoice') }}:<input type="text" id="total-invoice" name="total_invoice" class="font-weight-bold" value="{{ $sub_total }}" readonly></h5>
    </div>

    @if ($reserveProduct->active === 1)
    <div class="mt-4 d-flex justify-content-center">
        <button type="submit" class="btn btn-success">Submit Reservation</button>
    </div>
    @endif
</form>

    <!-- Modal Structure -->
<div id="productModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h4>Select a Product</h4>
        <input type="text" id="product-search" class="form-control mb-2" placeholder="Search for products by name or code...">
        <select id="modal-product-select" class="form-control">
            <option value="">Select a product</option>
            @foreach($products as $product)
                <option value="{{ $product->id }}" data-name="{{ $product->name }}" data-code="{{ $product->product_code }}">
                    {{ $product->name }} ({{ $product->product_code }})
                </option>
            @endforeach
        </select>
        <button id="select-product" class="btn btn-primary mt-2">Select Product</button>
    </div>
</div>
<hr class="line-dot">
        <h5 class="text-center">{{ \App\CPU\translate('THANK YOU') }}</h5>
        <hr class="line-dot">
    </div>
</div>
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
    <td>
        <span class="style-inthree">${selectedProduct.name}</span><br />
        {{ \App\CPU\translate('Tax') }} : ${selectedProduct.tax}%
    </td>
    <td><h5><span id="product-code">${selectedProduct.product_code}</span></h5></td>
    <td><input type="text" name="price" class="form-control form-control-sm" value="${selectedProduct.selling_price}" readonly></td>
    <td>
        <input type="number" name="data[${productIndex}][stock]" value="${quantity}" class="form-control quantity" min="1" max="100000">
        <input type="hidden" name="data[${productIndex}][product_id]" value="${selectedProduct.id}">
        <input type="hidden" name="data[${productIndex}][product_name]" value="${selectedProduct.name}">
        <input type="hidden" name="data[${productIndex}][balance]" value="${balance}">
    </td>
    <td>
        <input type="number" name="data[${productIndex}][balance]" value="0" class="form-control form-control-sm"  style="width: 70px;">
    </td>
    <td>
        <input type="text" name="data[${productIndex}][totalQtyBalance]" value="${stock}" class="form-control" readonly>
    </td>
              <td style="display: none;">
    <input type="hidden" name="typeproduct" value="{{ $reserveProduct->type }}">
</td>

    <td>
        <input type="text" name="data[${productIndex}][totalQtyBalance]" value="${productStockData[selectedProduct.id] || 0}" class="form-control" readonly>
    </td>
    
    <td>
        <h5><span id="discount-amount">${individualDiscount.toFixed(2)}</span></h5>
    </td>
    <td>
        <span class="total-price">${netProductPrice.toFixed(2)}</span>
        <input type="hidden" name="total_price" value="${netProductPrice.toFixed(2)}">
    </td>
    <td>
        <span class="tax-amount">${taxAmount.toFixed(2)}</span>
        <input type="hidden" name="tax_amount" value="${taxAmount.toFixed(2)}">
    </td>
    <td>
        <span class="net-total">${netProductPrice.toFixed(2)}</span>
    </td>
    <td>
        <button type="button" class="btn btn-danger btn-sm remove-product" data-id="${productIndex}">
            {{ \App\CPU\translate('Remove') }}
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
        const name = option.dataset.name.toLowerCase();
        const code = option.dataset.code.toLowerCase();
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
        productSelect.innerHTML = '<option value="">Select Product</option>';

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



@endsection