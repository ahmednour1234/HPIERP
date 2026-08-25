@extends('layouts.admin.app')

@section('title', \App\CPU\translate('product_list_Unlike'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="d-flex align-items-center g-2px align-items-center mb-3">
            <h1 class="page-header-title d-flex align-items-center g-2px text-capitalize">
                <i class="tio-files"></i> <span>{{ \App\CPU\translate('كشف الركود') }}</span>
            </h1>
       
        </div>
        <!-- End Page Header -->

        <!-- Search Form -->
<!--<form action="{{ route('admin.ordernotification.Productunlike') }}" method="GET">-->
<!--    <div class="row mb-3">-->
        <!-- User ID Filter -->
<!--        <div class="col-md-3">-->
<!--            <div class="form-group">-->
<!--                <label for="user_id">{{ \App\CPU\translate('عميل') }}</label>-->
<!--                <select name="user_id" id="user_id" class="form-control">-->
<!--                    <option value="">{{ \App\CPU\translate('اختر العميل') }}</option>-->
<!--                    @foreach ($customers as $customer)-->
<!--                        <option value="{{ $customer->id }}" {{ request('user_id') == $customer->id ? 'selected' : '' }}>-->
<!--                            {{ $customer->name }}-->
<!--                        </option>-->
<!--                    @endforeach-->
<!--                </select>-->
<!--            </div>-->
<!--        </div>-->

        <!-- Date Range Filters -->
<!--        <div class="col-md-3">-->
<!--            <div class="form-group">-->
<!--                <label for="start_date">{{ \App\CPU\translate('تاريخ البدء') }}</label>-->
<!--                <input type="date" name="start_date" id="start_date" class="form-control" value="{{ request('start_date') }}">-->
<!--            </div>-->
<!--        </div>-->

<!--        <div class="col-md-3">-->
<!--            <div class="form-group">-->
<!--                <label for="end_date">{{ \App\CPU\translate('تاريخ الانتهاء') }}</label>-->
<!--                <input type="date" name="end_date" id="end_date" class="form-control" value="{{ request('end_date') }}">-->
<!--            </div>-->
<!--        </div>-->

        <!-- Source Filter -->
<!--        <div class="col-md-3">-->
<!--            <div class="form-group">-->
<!--                <label for="source">{{ \App\CPU\translate('المصدر') }}</label>-->
<!--                <select name="source" id="source" class="form-control">-->
<!--                    <option value="order">{{ \App\CPU\translate('اختر المصدر') }}</option>-->
<!--                    <option value="order" {{ request('source') === 'order' ? 'selected' : '' }}>{{ \App\CPU\translate('مبيعات') }}</option>-->
<!--                </select>-->
<!--            </div>-->
<!--        </div>-->

        <!-- Order By Filter -->
<!--        <div class="col-md-3">-->
<!--            <div class="form-group">-->
<!--                <label for="order_by">{{ \App\CPU\translate('ترتيب') }}</label>-->
<!--                <select name="order_by" id="order_by" class="form-control">-->
<!--                    <option value="asc" {{ request('order_by') === 'asc' ? 'selected' : '' }}>{{ \App\CPU\translate('الاقل ركودا') }}</option>-->
<!--                    <option value="desc" {{ request('order_by') === 'desc' ? 'selected' : '' }}>{{ \App\CPU\translate('الاكثر ركودا') }}</option>-->
<!--                </select>-->
<!--            </div>-->
<!--        </div>-->

        <!-- Submit Button -->
<!--        <div class="col-md-3 d-flex align-items-end">-->
<!--            <button type="submit" class="btn btn-primary">{{ \App\CPU\translate('بحث') }}</button>-->
<!--        </div>-->
<!--    </div>-->
<!--</form>-->
        <!-- End Search Form -->

        <div class="row">
            <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
                <!-- Card -->
                <div class="card">
                    <!-- Table -->
                    <div class="table-responsive datatable-custom">
<table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
    <thead class="thead-light">
        <tr>
            <th>{{ \App\CPU\translate('اسم الصنف') }}</th>
            <th>{{ \App\CPU\translate('كود الصنف') }}</th>
            <th>{{ \App\CPU\translate('الكمية') }}</th>
            <th>{{ \App\CPU\translate('تاريخ اخر مرة بيع') }}</th>
            <th>{{ \App\CPU\translate('مدة الركود') }}</th>
        </tr>
    </thead>
    <tbody id="set-rows">
        @foreach($products as $product)
            <tr>
                <td>{{ $product->name }}</td>
                <td>{{ $product->product_code }}</td>
                <td>{{ $product->quantity }}</td>
            <td>
    {{ $product->last_sale_date ? \Carbon\Carbon::parse($product->last_sale_date)->format('Y-m-d') : 'N/A' }}
</td>
<td>
    {{ $product->stagnation_period ? $product->stagnation_period . ' ' . \App\CPU\translate('days') : 'N/A' }}
</td>

            </tr>
        @endforeach
    </tbody>
</table>

                        <div class="page-area">
                            <table>
                                <tfoot class="border-top">
                                </tfoot>
                            </table>
                            <!--@if(count($products) == 0)-->
                            <!--    <div class="text-center p-4">-->
                            <!--        <img class="mb-3 img-two-plst" src="{{ asset('public/assets/admin/svg/illustrations/sorry.svg') }}" alt="Image Description">-->
                            <!--        <p class="mb-0">{{ \App\CPU\translate('لاتوجد بيانات لعرضها') }}</p>-->
                            <!--    </div>-->
                            <!--@endif-->
                        </div>
                    </div>
                    <!-- End Table -->
                </div>
                <!-- End Card -->
            </div>
        </div>
    </div>
    <div class="modal fade" id="update-quantity" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ \App\CPU\translate('update_product_quantity') }} <br>
                        <span class="text-danger">({{ \App\CPU\translate('to_decrease_product_quantity_use_minus_before_number._Ex: -10') }})</span>
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form>
                        <!-- Your form elements for updating product quantity go here -->
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
    <script src="{{ asset('public/assets/admin/js/global.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const userSearch = document.getElementById('user_search');
        const userIdInput = document.getElementById('user_id');
        const userList = document.getElementById('user_list');
        const customers = @json($customers); // Assuming $customers is passed to the view as a JSON-encoded variable

        userSearch.addEventListener('input', function () {
            const query = userSearch.value.toLowerCase();
            userList.innerHTML = '';

            if (query.length > 0) {
                const filteredCustomers = customers.filter(customer =>
                    customer.name.toLowerCase().includes(query) ||
                    (customer.mobile && customer.mobile.toLowerCase().includes(query))
                );

                filteredCustomers.forEach(customer => {
                    const listItem = document.createElement('a');
                    listItem.href = '#';
                    listItem.className = 'list-group-item list-group-item-action';
                    listItem.textContent = `${customer.name}(${customer.mobile})`;
                    listItem.dataset.id = customer.id;
                    userList.appendChild(listItem);
                });

                userList.style.display = 'block';
            } else {
                userList.style.display = 'none';
            }
        });

        userList.addEventListener('click', function (e) {
            if (e.target && e.target.nodeName === 'A') {
                userSearch.value = e.target.textContent;
                userIdInput.value = e.target.dataset.id;
                userList.style.display = 'none';
            }
        });

        document.addEventListener('click', function (e) {
            if (!userList.contains(e.target) && !userSearch.contains(e.target)) {
                userList.style.display = 'none';
            }
        });
    });
</script>

@endpush
