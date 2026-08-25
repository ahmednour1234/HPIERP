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
                    <i class="tio-files"></i> <span>{{ \App\CPU\translate('كشف المنتجات') }}
                    <span class="badge badge-soft-dark ml-2">{{ $products->total() }}</span></span>
                </h1>
                <div class="ml-auto">
                    <a href="{{ route('admin.product.add') }}" class="btn btn-primary"><i class="tio-add-circle"></i> {{ \App\CPU\translate('اضافة') }} {{ \App\CPU\translate('منتج') }} {{ \App\CPU\translate('جديد') }}</a>
                </div>
                <div class="ml-auto">
                    <a href="{{ route('admin.product.addexpire') }}" class="btn btn-primary"><i class="tio-add-circle"></i> {{ \App\CPU\translate('اضافة') }} {{ \App\CPU\translate('منتج') }} {{ \App\CPU\translate('هالك') }}</a>
                </div>
         
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
                                <select name="sort_orderQty" class="form-control" onchange="location.href='{{ url('/') }}/admin/product/list/?sort_orderQty='+this.value">
                                    <option value="default" {{ $sort_orderQty == "default" ? 'selected' : '' }}>
                                        {{ \App\CPU\translate('افتراضي') }}
                                    </option>
                                    <option value="quantity_asc" {{ $sort_orderQty == "quantity_asc" ? 'selected' : '' }}>
                                        {{ \App\CPU\translate('بالكمية من الاقل للاعلي') }}
                                    </option>
                                    <option value="quantity_desc" {{ $sort_orderQty == "quantity_desc" ? 'selected' : '' }}>
                                        {{ \App\CPU\translate('بالكمية من الاعلي للاقل') }}
                                    </option>
                                    <option value="quantity_expire_asc" {{ $sort_orderQty == "quantity_expire_asc" ? 'selected' : '' }}>
                                        {{ \App\CPU\translate('بكمية الهالك من الاقل للاعلي') }}
                                    </option>
                                    <option value="quantity_expire_desc" {{ $sort_orderQty == "quantity_expire_desc" ? 'selected' : '' }}>
                                        {{ \App\CPU\translate('بكمية الهالك من الاعلي للاقل') }}
                                    </option>
                                    <option value="order_asc" {{ $sort_orderQty == "order_asc" ? 'selected' : '' }}>
                                        {{ \App\CPU\translate('بالمبيعات من الاقل للاعلي') }}
                                    </option>
                                    <option value="order_desc" {{ $sort_orderQty == "order_desc" ? 'selected' : '' }}>
                                        {{ \App\CPU\translate('بالمبيعات من الاعلي للاقل') }}
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
                                <th>{{ \App\CPU\translate('تاريخ الانتهاء') }}</th>
                                <th>{{ \App\CPU\translate('الاسم') }}</th>
                                <th>{{ \App\CPU\translate('الكود') }}</th>
                                <th>{{ \App\CPU\translate('الكمية المشتراه') }}</th>
                                <th>{{ \App\CPU\translate('الكمية  المباعة') }}</th>
                                <th>{{ \App\CPU\translate('هالك') }}</th>
                                <th>{{ \App\CPU\translate('مرتجع بيع') }}</th>
                                <th>{{ \App\CPU\translate('مرتجع شراء') }}</th>
                                <th>{{ \App\CPU\translate('الاجراءات') }}</th>
                            </tr>
                            </thead>

                            <tbody id="set-rows">
                                @foreach($products as $key=>$product)
                                    <tr>
                                        <td>{{ $product['id'] }}</td>
                                        <td>{{ \Carbon\Carbon::parse($product['expiry_date'])->format('d-m-Y') }}</td>
                                        <td>
                                            <span class="d-block font-size-sm text-body">
                                                {{ $product['name'] }}
                                            </span>
                                        </td>
                                        <td>{{ $product['product_code'] ?? 0 }}</td>
                                        <td>{{ $product['purchase_count'] ?? 0 }}</td>
                                        <td>{{ $product['order_count'] ?? 0 }}</td>
                                        <td>
                                            @if($product->productexpire->isNotEmpty())
                                                {{ $product->productexpire->sum('quantity') }}
                                            @else
                                                0
                                            @endif
                                        </td>
                                        <td>{{ $product['repurchase_count'] ?? 0 }}</td>
                                        <td>{{ $product['refund_count'] ?? 0 }}</td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <div class="d-inline">
                                                    <a class="btn btn-white mr-1" href="{{ route('admin.product.edit', [$product['id']]) }}"> <span class="tio-edit"></span></a>
                                                </div>
                                                <div class="d-inline">
                                                    <a class="btn btn-white mr-1" href="javascript:" onclick="form_alert('product-{{ $product['id'] }}', 'Want to delete this Product?')"><span class="tio-delete"></span></a>
                                                    <form action="{{ route('admin.product.delete', [$product['id']]) }}" method="post" id="product-{{ $product['id'] }}">
                                                        @csrf @method('delete')
                                                    </form>
                                                </div>
                                                <div class="d-inline">
                                                    <a class="btn btn-white mr-1" data-toggle="tooltip" data-placement="top" title="{{ \App\CPU\translate('generate_barcode') }}" href="{{ route('admin.product.barcode-generate', [$product['id']]) }}" target="_blank">
                                                        <span class="tio-barcode"></span>
                                                    </a>
                                                </div>
                                            </div>
                                        </td>
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
