@extends('layouts.admin.app')

@section('title', \App\CPU\translate('product_list'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.css">
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="">
            <div class="d-flex align-items-center g-2px align-items-center mb-3">
                <h1 class="page-header-title d-flex align-items-center g-2px text-capitalize">
                    <i class="tio-files"></i> <span>{{ \App\CPU\translate('كشف الصلاحية') }}
                    <span class="badge badge-soft-dark ml-2">{{ $products->total() }}</span></span>
                </h1>
            </div>
        </div>
        <!-- End Page Header -->

        <!-- Content Here -->
        <div class="row">
            <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
                <!-- Card -->
                <div class="card">
                    <!-- Header -->
                    <div class="card-header">
                        <div class="row justify-content-between align-items-center flex-grow-1">
                            <div class="col-12 col-sm-8 col-md-6">
                                <form action="{{ url()->current() }}" method="GET">
                                    <!-- Search -->
                                    <div class="input-group input-group-merge input-group-flush">
                                        <div class="input-group-prepend">
                                            <div class="input-group-text">
                                                <i class="tio-search"></i>
                                            </div>
                                        </div>
                                        <input id="datatableSearch_" type="search" name="search" class="form-control"
                                               placeholder="{{ \App\CPU\translate('بحث باسم او كود المنتج') }}" aria-label="{{ \App\CPU\translate('Search') }}" value="{{ $search }}" required>
                                        <button type="submit" class="btn btn-primary">{{ \App\CPU\translate('بحث') }}</button>
                                    </div>
                                    <!-- End Search -->
                                </form>
                            </div>
                            <div class="mt-1 col-12 col-sm-4">
                                <select name="sort_orderQty" class="form-control" onchange="location.href='{{ url('/') }}/admin/product/listreportexpire/?sort_orderQty='+this.value">
                                    <option value="default" {{ $sort_orderQty == "default" ? 'selected' : '' }}>
                                        {{ \App\CPU\translate('افتراضي') }}
                                    </option>
                                   <option value="name_asc" {{ $sort_orderQty == "name_asc" ? 'selected' : '' }}>
    {{ \App\CPU\translate('بالاسم من الاقل للاعلي') }}
</option>
<option value="name_desc" {{ $sort_orderQty == "name_desc" ? 'selected' : '' }}>
    {{ \App\CPU\translate('بالاسم من الاعلي للاقل') }}
</option>
<option value="price_asc" {{ $sort_orderQty == "price_asc" ? 'selected' : '' }}>
    {{ \App\CPU\translate('بالسعر من الاقل للاعلي') }}
</option>
<option value="price_desc" {{ $sort_orderQty == "price_desc" ? 'selected' : '' }}>
    {{ \App\CPU\translate('بالسعر من الاعلي للاقل') }}
</option>
<option value="expire_date_asc" {{ $sort_orderQty == "expire_date_asc" ? 'selected' : '' }}>
    {{ \App\CPU\translate('تاريخ الصلاحية من الاقل للاعلي') }}
</option>
<option value="expire_date_desc" {{ $sort_orderQty == "expire_date_desc" ? 'selected' : '' }}>
    {{ \App\CPU\translate('تاريخ الصلاحية من الاعلي للاقل') }}
</option>

                                </select>
                            </div>
                        </div>
                    </div>
                    <!-- End Header -->

                    <!-- Table -->
                    <div class="table-responsive datatable-custom" id="product-table">
                        <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                            <thead class="thead-light">
                            <tr>
                                <th>{{ \App\CPU\translate('#') }}</th>
                                <th>{{ \App\CPU\translate('الكود') }}</th>
                                <th>{{ \App\CPU\translate('الصلاحية') }}</th>
                                <th>{{ \App\CPU\translate('الكمية') }}</th>
                                <th>{{ \App\CPU\translate('الاسم') }}</th>
                                <th>{{ \App\CPU\translate('المخزن') }}</th>
                                <th>{{ \App\CPU\translate('السعر') }}</th>
                            </tr>
                            </thead>

                            <tbody id="set-rows">
                                @foreach($products as $key=>$product)
                                    <tr>
                                        <td>{{ $product['id'] }}</td>
                                        <td>{{ $product['product_code'] ?? 0 }}</td>
                                        <td>{{ \Carbon\Carbon::parse($product['expiry_date'])->format('d-m-Y') }}</td>
                                        <td>{{ $product['quantity'] ?? 0 }}</td>
                                        <td>
                                            <span class="d-block font-size-sm text-body">
                                                {{ $product['name'] }}
                                            </span>
                                        </td>
<td>{{ $product->stock->store->store_name1 ?? 'No Store Available' }}</td>
                                        <td>{{ $product['selling_price'] ?? 0 }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <div class="page-area">
                            <table>
                                <tfoot class="border-top">
                                {!! $products->links() !!}
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <!-- End Table -->
                </div>
                <!-- End Card -->
            </div>
        </div>
    </div>
@endsection

@push('script_2')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

@endpush
