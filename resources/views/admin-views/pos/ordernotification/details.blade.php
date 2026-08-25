@extends('layouts.admin.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">{{ __('Place Order') }}</div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success" role="alert">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger" role="alert">
                            {{ session('error') }}
                        </div>
                    @endif

<form action="{{ route('admin.ordernotification.placeOrder') }}" method="POST" id="place-order-form">
    @csrf
<input type="hidden" name="cart[]" id="cart-input"> <!-- Changed to array format -->
    <input type="hidden" name="order_id" value="{{ $order->id }}">
    <input type="hidden" name="active" value="1">

    <div class="container">
        <!-- Shop Information Section -->
        <div class="text-center mb-4">
            <h2>{{ \App\Models\BusinessSetting::where('key', 'shop_name')->value('value') }}</h2>
            <h5>{{ \App\Models\BusinessSetting::where('key', 'shop_address')->value('value') }}</h5>
            <div class="row">
                <div class="col-md-6">
                    <h5>Phone:
                        <input type="text" name="shop_phone"
                            value="{{ \App\Models\BusinessSetting::where('key', 'shop_phone')->value('value') }}"
                            class="form-control" readonly>
                    </h5>
                </div>
                <div class="col-md-6">
                    <h5>Email:
                        <input type="text" name="shop_email"
                            value="{{ \App\Models\BusinessSetting::where('key', 'shop_email')->value('value') }}"
                            class="form-control" readonly>
                    </h5>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <h5>Vat Registration Number:
                        <input type="text" name="vat_reg_no"
                            value="{{ \App\Models\BusinessSetting::where('key', 'vat_reg_no')->value('value') }}"
                            class="form-control" readonly>
                    </h5>
                </div>
            </div>
        </div>

        <hr class="my-4">

        <div class="row mb-4">
            <!-- Order Information Section -->
            <div class="col-md-6">
                <h5>Order ID: {{ $order->id }}</h5>
            </div>
            <div class="col-md-6">
                <h5>Seller Name :
                    @if ($order->seller)
                        <input type="text" name="seller_name"
                            value="{{ $order->seller->f_name . ' ' . $order->seller->l_name }}"
                            class="form-control" readonly>
                    @else
                        <input type="text" name="seller_name" value="Seller Not Found"
                            class="form-control">
                    @endif
                </h5>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <h5>Customer Name :
                    <input type="text" name="customer"
                        value="{{ $order->customer ? ($order->customer->name) : 'Customer Deleted' }}"
                        class="form-control" readonly required>
                    <input type="hidden" name="user_id"
                        value="{{ $order->user_id }}" class="form-control" readonly>
                </h5>
            </div>
            <div class="col-md-6">
                <h5>Order Date :
                    <input type="text" name="order_date"
                        value="{{ date('d/M/Y h:i a', strtotime($order->created_at)) }}"
                        class="form-control" readonly>
                </h5>
            </div>
        </div>

        <hr class="my-4">

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>SL</th>
                        <th>Product Name</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="product-table-body">
                    @php
                        $sub_total = 0;
                        $total_tax = 0;
                    @endphp
                    @foreach ($orderDetails as $key => $detail)
                        @if ($detail->product_details)
                            @php
                                $product = json_decode($detail->product_details, true);
                                $amount = ($detail->price - $detail->discount_on_product) * $detail->quantity;
                                $sub_total += $amount;
                                $total_tax += $detail->tax_amount * $detail->quantity;
                            @endphp
                            <tr>
                                <td>{{ $key + 1 }}</td>
                                <td>
                                    <input type="hidden" name="product_id[]"
                                        value="{{ $product['id'] }}">
                                    {{ $product['name'] }}
                                </td>
                                <td>
                                    <input type="number" name="product_quantity[]"
                                        value="{{ $detail->quantity }}" class="form-control product-quantity" data-price="{{ $detail->price }}" data-max="{{ $product['quantity'] }}">
                                </td>
                                <td>
                                    <input type="text" name="product_amount[]" value="{{ $amount }}"
                                        class="form-control product-amount" readonly>
                                </td>
                                  
                                <td>
                                    <button type="button" class="btn btn-danger delete-product">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>

            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="product-select">Select Product</label>
                        <select id="product-select" class="form-control">
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}" data-selling_price="{{ $product->selling_price }}" data-max="{{ $product->quantity }}">
                                    {{ $product->name }} - ${{ $product->selling_price }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <button type="button" class="btn btn-success" id="add-product">Add Product</button>
        </div>

        <hr class="my-4">

        <dl class="row text-black-50">
            <dt class="col-md-7">Items Price:</dt>
            <dd class="col-md-5">
                <input type="text" name="items_price" value="{{ number_format($sub_total, 2) }}"
                    class="form-control" id="items-price" readonly>
            </dd>

            <dt class="col-md-7">Tax / VAT:</dt>
            <dd class="col-md-5">
                <input type="text" name="total_tax" value="{{ number_format($total_tax, 2) }}"
                    class="form-control" id="total-tax" readonly>
            </dd>

            <dt class="col-md-7">Subtotal:</dt>
            <dd class="col-md-5">
                <input type="text" name="subtotal"
                    value="{{ number_format($sub_total + $total_tax, 2) }}" class="form-control"
                    id="subtotal" readonly>
            </dd>

            <dt class="col-md-7">Extra Discount:</dt>
            <dd class="col-md-5">
                <input type="text" name="extra_discount"
                    value="{{ $order->extra_discount ? number_format($order->extra_discount, 2) : 0 }}"
                    class="form-control" id="extra-discount" readonly>
            </dd>

            <dt class="col-md-7">Coupon Discount:</dt>
            <dd class="col-md-5">
                <input type="text" name="coupon_discount_amount"
                    value="{{ $order->coupon_discount_amount }}" class="form-control"
                    id="coupon-discount" readonly>
            </dd>

            <dt class="col-md-7 total">Total:</dt>
            <dd class="col-md-5">
                <input type="text" name="total"
                    value="{{ number_format($sub_total + $total_tax - ($order->coupon_discount_amount + $order->extra_discount), 2) }}"
                    class="form-control" id="total" readonly>
            </dd>
        </dl>

        <hr class="my-4">

        @if($active == 0)
            <div class="form-group row mb-0">
                <div class="col-md-6 offset-md-4">
                    <button type="submit" class="btn btn-primary">
                        Place Order
                    </button>
                    <a href="{{ route('admin.ordernotification.index') }}" class="btn btn-secondary">
                        Cancel
                    </a>
                </div>
            </div>
        @endif
    </div>
</form>
               </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script_2')
<script>
    function updateCartInput() {
        const cart = [];
        const rows = document.querySelectorAll('#product-table-body tr');
        rows.forEach(row => {
            const productId = row.querySelector('input[name="product_id[]"]').value;
            const quantity = row.querySelector('.product-quantity').value;
            cart.push({ id: productId, quantity: parseInt(quantity) });
        });
        document.getElementById('cart-input').value = JSON.stringify(cart);
    }

    function updateAmounts() {
        let subTotal = 0;
        let totalTax = 0;
        const rows = document.querySelectorAll('#product-table-body tr');
        rows.forEach(row => {
            const quantity = parseInt(row.querySelector('.product-quantity').value) || 0; // Ensure quantity is parsed correctly, default to 0 if NaN
            const price = parseFloat(row.querySelector('.product-quantity').dataset.price) || 0; // Ensure price is parsed correctly, default to 0 if NaN
            const maxQuantity = parseInt(row.querySelector('.product-quantity').dataset.max) || 0; // Ensure max quantity is parsed correctly, default to 0 if NaN
            const amount = (price * quantity).toFixed(2);
            row.querySelector('.product-amount').value = amount;
            subTotal += parseFloat(amount) || 0; // Ensure amount is parsed correctly, default to 0 if NaN
            totalTax += parseFloat(row.querySelector('.product-quantity').dataset.tax) * quantity || 0; // Ensure tax is parsed correctly, default to 0 if NaN
        });
        document.getElementById('items-price').value = subTotal.toFixed(2);
        document.getElementById('total-tax').value = totalTax.toFixed(2);
        document.getElementById('subtotal').value = (subTotal + totalTax).toFixed(2);
        const extraDiscount = parseFloat(document.getElementById('extra-discount').value) || 0;
        const couponDiscount = parseFloat(document.getElementById('coupon-discount').value) || 0;
        document.getElementById('total').value = (subTotal + totalTax - extraDiscount - couponDiscount).toFixed(2);
    }

    document.getElementById('add-product').addEventListener('click', () => {
        const select = document.getElementById('product-select');
        const selectedOption = select.options[select.selectedIndex];
        const productId = selectedOption.value;
        const productName = selectedOption.text;
        const productPrice = parseFloat(selectedOption.dataset.selling_price) || 0; // Ensure price is parsed correctly, default to 0 if NaN
        const maxQuantity = parseInt(selectedOption.dataset.max) || 0; // Ensure max quantity is parsed correctly, default to 0 if NaN

        const productRow = `
            <tr>
                <td></td>
                <td>
                    <input type="hidden" name="product_id[]" value="${productId}">
                    ${productName}
                </td>
                <td>
                    <input type="number" name="product_quantity[]" value="1" class="form-control product-quantity" data-price="${productPrice}" data-max="${maxQuantity}">
                </td>
                <td>
                    <input type="text" name="product_amount[]" value="${productPrice.toFixed(2)}" class="form-control product-amount" readonly>
                </td>
                <td>
                    <button type="button" class="btn btn-danger delete-product">Delete</button>
                </td>
            </tr>
        `;

        document.getElementById('product-table-body').insertAdjacentHTML('beforeend', productRow);
        updateCartInput();
        updateAmounts();
    });

    document.getElementById('product-table-body').addEventListener('input', () => {
        updateCartInput();
        updateAmounts();
    });

    document.getElementById('product-table-body').addEventListener('click', (event) => {
        if (event.target.classList.contains('delete-product')) {
            event.target.closest('tr').remove();
            updateCartInput();
            updateAmounts();
        }
    });

    document.getElementById('place-order-form').addEventListener('submit', () => {
        updateCartInput();
    });

    // Initial update on page load
    updateAmounts();
</script>
@endpush
