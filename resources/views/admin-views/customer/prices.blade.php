@extends('layouts.admin.app')

@section('title', \App\CPU\translate('add_price'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('public/assets/admin') }}/css/custom.css" />
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="">
            <div class="row align-items-center mb-3">
                <div class="col-sm mb-2 mb-sm-0">
                    <h1 class="page-header-title d-flex align-items-center g-2px text-capitalize">
                        <i class="tio-add-circle-outlined"></i>
                        <span>{{ \App\CPU\translate('اضافة اسعار خاصة للعميل علي المنتجات') }}</span>
                    </h1>
                </div>
            </div>
        </div>
        <!-- End Page Header -->
        <div class="row gx-2 gx-lg-3">
            <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
                <div class="card">
                    <div class="card-body">
<form action="{{ route('admin.customer.prices', $customer_id) }}" method="post" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="customer_id" value="{{ $customer_id }}">

    <div class="row">
        <div class="col-12 col-sm-6 col-md-6 col-lg-6">
            <div class="form-group">
                <label>{{ \App\CPU\translate('اختار منتج') }}</label>
                <select name="product_id" id="product-select" class="form-control js-select2-custom" required>
                    <option value="" hidden>---{{ \App\CPU\translate('اختار') }}---</option>
                    @foreach(\App\Models\Product::all() as $p)
                        <option value="{{ $p->id }}" data-code="{{ $p->product_code }}">{{ $p->name }} ({{ $p->product_code }})</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-6 col-lg-6">
            <div class="form-group">
                <label>{{ \App\CPU\translate('السعر') }}</label>
                <input type="text" name="price" class="form-control" placeholder="{{ \App\CPU\translate('add_price') }}" required>
            </div>
        </div>
    </div>
    <button type="submit" class="btn btn-primary">{{ \App\CPU\translate('حفظ') }}</button>
</form>                    </div>
                </div>
            </div>

<div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
    <div class="card">
        <div class="card-header">
            <div class="w-100">
                <div class="row">
                    <div class="col-12 col-sm-4 col-md-6 col-lg-7 col-xl-8">
                        <h5>{{ \App\CPU\translate('جدول اسعار المنتجات') }}
                            <span class="badge badge-soft-dark">{{$prices->total()}}</span>
                        </h5>
                    </div>
                </div>
            </div>
        </div>
        <!-- Search Input -->
        <div class="mb-3">
            <input type="text" id="search-input" class="form-control" placeholder="{{ \App\CPU\translate('بحث بالاسم او بالكود المنتج') }}" onkeyup="filterTable()">
        </div>
        <!-- Table -->
        <div class="table-responsive">
            <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                <thead class="thead-light">
                    <tr>
                        <th>{{ \App\CPU\translate('#') }}</th>
                        <th>{{ \App\CPU\translate('المنتج') }}</th>
                        <th>{{ \App\CPU\translate('الكود') }}</th>
                        <th>{{ \App\CPU\translate('السعر') }}</th>
                        <th>{{ \App\CPU\translate('اجراءات') }}</th>
                    </tr>
                </thead>

                <tbody id="product-table-body">
                    @foreach ($prices as $key => $price)
                        <tr>
                            <td>{{ $key + 1 }}</td>
                            <td>
                                <span class="d-block font-size-sm text-body">
                                    {{ $price->product->name }}
                                </span>
                            </td>
                            <td>
                                <span class="d-block font-size-sm text-body">
                                    {{ $price->product->product_code }} <!-- Assuming you have a product_code attribute -->
                                </span>
                            </td>
                            <td>
                                <span class="d-block font-size-sm text-body">
                                    {{ $price->price }}
                                </span>
                            </td>
                            <td>
                                <a class="btn btn-white mr-1" href="{{ route('admin.customer.prices.edit', [$customer_id, $price->id]) }}">
                                    <span class="tio-edit"></span>
                                </a>
                                <a class="btn btn-white mr-1" href="javascript:" onclick="form_alert('price-{{ $price['id'] }}','Want to delete this price?')">
                                    <span class="tio-delete"></span>
                                </a>
                                <form action="{{ route('admin.customer.prices.delete', [$price->id]) }}" method="post" id="price-{{ $price['id'] }}">
                                    @csrf @method('delete')
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <hr>
            <table>
                <tfoot>
                    {!! $prices->links() !!}
                </tfoot>
            </table>
            @if (count($prices) == 0)
                <div class="text-center p-4">
                    <img class="mb-3 w-one-cati" src="{{ asset('public/assets/admin') }}/svg/illustrations/sorry.svg" alt="Image Description">
                    <p class="mb-0">{{ \App\CPU\translate('لاتوجد بيانات لعرضها') }}</p>
                </div>
            @endif
        </div>
    </div>
</div>            <!-- End Table -->
        </div>
    </div>
@endsection

@push('script_2')
<script>
    document.getElementById('search-input').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const productSelect = document.getElementById('product-select');
        const options = productSelect.querySelectorAll('option');

        options.forEach(option => {
            const code = option.getAttribute('data-code').toLowerCase();
            if (code.includes(searchTerm)) {
                option.style.display = '';
            } else {
                option.style.display = 'none';
            }
        });

        // Reset the select to the first visible option
        productSelect.value = '';
        options.forEach(option => {
            if (option.style.display !== 'none') {
                productSelect.value = option.value;
                return;
            }
        });
    });
</script>
<script>
function filterTable() {
    const input = document.getElementById('search-input');
    const filter = input.value.toLowerCase();
    const tableBody = document.getElementById('product-table-body');
    const rows = tableBody.getElementsByTagName('tr');

    for (let i = 0; i < rows.length; i++) {
        const tdProductName = rows[i].getElementsByTagName('td')[1]; // Product name column
        const tdProductCode = rows[i].getElementsByTagName('td')[2]; // Product code column

        const productName = tdProductName.textContent || tdProductName.innerText;
        const productCode = tdProductCode.textContent || tdProductCode.innerText;

        // Check if product name or product code matches the search query
        if (productName.toLowerCase().indexOf(filter) > -1 || productCode.toLowerCase().indexOf(filter) > -1) {
            rows[i].style.display = ""; // Show the row
        } else {
            rows[i].style.display = "none"; // Hide the row
        }
    }
}
</script>
    <script src={{ asset('public/assets/admin/js/global.js') }}></script>
@endpush
