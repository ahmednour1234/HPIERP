@extends('layouts.admin.app')

@section('title',\App\CPU\translate('update_stock'))

@push('css_or_js')
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/custom.css"/>
@endpush

@section('content')
<div class="content container-fluid">
        <!-- Page Header -->
    <div class="row align-items-center mb-3">
        <div class="col-sm mb-2 mb-sm-0">
            <h1 class="page-header-title text-capitalize"><i
                    class="tio-edit"></i> {{\App\CPU\translate('تعديل زيارة')}}
            </h1>
        </div>
    </div>
        <!-- End Page Header -->
        <div class="row gx-2 gx-lg-3">
            <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
                <div class="card">
                    <div class="card-body">
                        <form action="{{route('admin.stock.update',[$stock->id])}}" method="post" id="product_form"
                            enctype="multipart/form-data"  >
                            @csrf
                            <div class="row pl-2" >
                                <div class="col-12 col-sm-6">
                                    <div class="form-group">
                                        <label class="input-label">{{\App\CPU\translate('اسم المندوب')}} <span
                                            class="input-label-secondary text-danger">*</span></label>
                                        <select type="text" id="seller" name="seller_id" class="form-control" required>
                                            <option value="" hidden>-- Choose Seller --</option>
                                            @foreach($sellers as $key => $seller)
                                            <option @if($stock->seller_id == $seller->id) selected @endif value="{{ $seller->id }}">{{ $seller->email }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <div class="form-group">
                                        <label class="input-label">{{\App\CPU\translate('product')}} <span
                                            class="input-label-secondary text-danger">*</span></label>
                                        <select type="text" id="products" name="product_id" class="form-control" required>
                                            <option value="" hidden>-- Choose Product --</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <div class="form-group">
                                        <label class="input-label">{{\App\CPU\translate('stock')}} <span
                                            class="input-label-secondary text-danger">*</span></label>
                                        <input type="number" placeholder="{{\App\CPU\translate('Ex: 2')}}" name="stock" class="form-control" required value="{{ $stock->stock }}">
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">{{\App\CPU\translate('update')}}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection


@push('script_2')
    <script>
        var product = [];
        
        var stock = @json($stock);
        var products = @json($products);
        products.forEach((item) => {
            item.forEach((it) => {
                product.push(`<option id="value" ${stock.product_id == it.id ? 'selected' : ''} value="${it.id}">${it.name}</option>`)
            })
        })
        $('#products').append(product);

        $('#seller').on('change', function() {
            product = [];
            $('#products option#value').remove();
            $.get(
                `{{ route('admin.stock.create') }}?seller=${this.value}`,
                function(data, status) {
                    data.option.forEach((item) => {
                        item.forEach((it) => {
                            product.push(`<option id="value" value="${it.id}">${it.name}</option>`)
                        })
                    })

                    $('#products').append(product);
                }
            ).then(function(data, status) {
                
            },
            function(error, status) {
                console.log(error.responseText)
            });
        })
    </script>
@endpush
