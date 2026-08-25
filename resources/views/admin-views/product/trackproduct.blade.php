{{-- resources/views/admin-views/product/list-products-by-order-type.blade.php --}}
@extends('layouts.admin.app')

@section('content')
    <div class="container">
        <h1>حركة /متابعة صنف</h1>

        <!-- Filters Form -->
        <form method="GET" action="{{ route('admin.product.listProductsByOrderType') }}">
            <div class="row">
                <div class="form-group col-md-4">
                    <input type="hidden" name="product_id" class="form-control" value="{{ request('product_id') }}">
                </div>

                <div class="form-group col-md-4">
                    <label for="customer_id">العميل</label>
                    <select name="customer_id" class="form-control">
                        <option value="">كل العملاء</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" {{ request('customer_id') == $customer->id ? 'selected' : '' }}>
                                {{ $customer->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="form-group col-md-4">
                    <label for="order_type">نوع اتصنيف</label>
                    <select name="order_type" class="form-control">
                        <option value="">مجمع</option>
                        <option value="7" {{ request('order_type') == 7 ? 'selected' : '' }}>مرتجع مبيعات</option>
                        <option value="4" {{ request('order_type') == 4 ? 'selected' : '' }}>مبيعات</option>
                        <option value="12" {{ request('order_type') == 12 ? 'selected' : '' }}>عينات</option>
                        <option value="24" {{ request('order_type') == 24 ? 'selected' : '' }}>تبرعات</option>
                    </select>
                </div>

                <div class="form-group col-md-4">
                    <label for="date_from">من تاريخ</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>

                <div class="form-group col-md-4">
                    <label for="date_to">الي تاريخ</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
            </div>

            <button type="submit" class="btn btn-primary">بحث</button>
        </form>
@if (request('order_type') !== null && request('order_type') !== 'all')

        <!-- Products Table -->
        <table class="table mt-4">
            <thead>
                <tr>
                    <th>رقم الفاتورة</th>
                    <th>تاريخ الفاتورة</th>
                    <th>اسم العميل</th>
                    <th>اسم الكاتب</th>
                    <th>اسم الصنف</th>
                    <th>كود الصنف</th>
                    <th>الكمية</th>
                    <th>السعر</th>
                    <th>(الاجمالي)</th>
                    <th>تاريخ الصلاحية</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $total_orders = 0;
                    $total_quantity = 0;
                    $total_price = 0;
                @endphp
                @foreach ($products as $product)
                    @php
                        $total = $product->quantity * $product->price;
                        $total_orders++;
                        $total_quantity += $product->quantity;
                        $total_price += $total;
                    @endphp
                    <tr>
                        <td>{{ $product->order_id }}</td>
                        <td>{{ $product->created_at }}</td>
                        <td>{{ $product->order->customer->name }}</td>
                        <td>{{ $product->order->seller->name }}</td>
                        <td>{{ $product->product->name }}</td>
                        <td>{{ $product->product->product_code }}</td>
                        <td>{{ $product->quantity }}</td>
                        <td>{{ $product->price }}</td>
                        <td>{{ $total }}</td>
                        <td>
                            {{ \Carbon\Carbon::parse($product->product->expiry_date)->format('Y-m-d') ?? 'N/A' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Pagination Links -->
        {{ $products->appends(request()->input())->links() }}

        <!-- Summary Table -->
        <div class="mt-4">
            <h3>تلخيص</h3>
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>اجمالي الفواتير</th>
                        <th>اجمالي الكميات</th>
                        <th>اجمالي السعر</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ $total_orders }}</td>
                        <td>{{ $total_quantity }}</td>
                        <td>{{ $total_price }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        @endif

        <!-- Aggregates Section - Conditionally Displayed -->
        @if (request('order_type') === null || request('order_type') === 'all')
            <div class="mt-4">
                <h3>تجميعات</h3>
                <table class="table table-sm">
                    <caption>تجميعات الطلبات حسب نوع التصنيف</caption>
                    <thead>
                        <tr>
                            <th>نوع الفاتورة</th>
                            <th>اجمالي الفواتير</th>
                            <th>اجمالي الكميات</th>
                            <th>اجمالي السعر</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>مبيعات</td>
                            <td>{{ $sales->count() }}</td>
                            <td>{{ $sales->sum('quantity') }}</td>
                            <td>{{ $sales->sum(function($sale) { return $sale->quantity * $sale->price; }) }}</td>
                        </tr>
                        <tr>
                            <td>مرتجع مبيعات</td>
                            <td>{{ $purchaseReturns->count() }}</td>
                            <td>{{ $purchaseReturns->sum('quantity') }}</td>
                            <td>{{ $purchaseReturns->sum(function($return) { return $return->quantity * $return->price; }) }}</td>
                        </tr>
                        <tr>
                            <td>عينات</td>
                            <td>{{ $purchases->count() }}</td>
                            <td>{{ $purchases->sum('quantity') }}</td>
                            <td>{{ $purchases->sum(function($purchase) { return $purchase->quantity * $purchase->price; }) }}</td>
                        </tr>
                        <tr>
                            <td>تبرعات</td>
                            <td>{{ $salesReturns->count() }}</td>
                            <td>{{ $salesReturns->sum('quantity') }}</td>
                            <td>{{ $salesReturns->sum(function($return) { return $return->quantity * $return->price; }) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endif

        <!-- Other Details Section - Conditionally Displayed -->
        @if (request('order_type') === null || request('order_type') === 'all')
            <div class="mt-4">
                <h3>تفاصيل اخرى</h3>
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>اخر سعر بيع</th>
                            <th>اقل كمية بيع</th>
                            <th>رصيد المخزن</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ $last_sale_price ?? 'N/A' }}</td>
                        
                            <td>{{ $min_sale_quantity ?? 'N/A' }}</td>
                            <td>{{ $total_stock_quantity ?? 'N/A' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endif

    </div>
@endsection
