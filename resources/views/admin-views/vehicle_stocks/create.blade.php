@extends('layouts.admin.app')

@section('title',\App\CPU\translate('add_new_stock'))

@push('css_or_js')
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/custom.css"/>
@endpush

@section('content')
<div class="content container-fluid">
        <!-- Page Header -->
        <div class="row align-items-center mb-3">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title d-flex align-items-center g-2px text-capitalize"><i
                        class="tio-add-circle-outlined"></i> {{\App\CPU\translate('add_new_stock')}}
                </h1>
            </div>
        </div>
        <!-- End Page Header -->
        <div class="row gx-2 gx-lg-3">
            <div class="col-sm-12 col-lg-12  mb-lg-2">
                <div class="card">
                    <div class="card-body">
                        <form action="{{route('admin.stock.store')}}" method="post" id="product_form" enctype="multipart/form-data" >
                            @csrf
                            <div class="row pl-2" >
                                <div class="col-12 col-sm-4">
                                    <div class="form-group">
                                        <label class="input-label">{{\App\CPU\translate('seller_email')}} <span
                                            class="input-label-secondary text-danger">*</span></label>
                                        <select type="text" id="seller" name="seller_id" class="form-control js-select2-custom" required onchange="getProduct()">
                                            <option value="" hidden>-- Choose Seller --</option>
                                            @foreach($sellers as $key => $seller)
                                            <option @if(old('seller_id') == $seller->id) selected @endif value="{{ $seller->id }}">{{ $seller->email }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <div class="form-group">
                                        <label class="input-label">{{\App\CPU\translate('product')}} <span
                                            class="input-label-secondary text-danger">*</span></label>
                                        <select id="products" class="form-control js-select2-custom" multiple>
                                            <option value="" disabled>---{{\App\CPU\translate('select')}}---</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="container">
                                <table class="table text-center">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Stock</th>
                                        </tr>
                                    </thead>
                                    <tbody id="data">
                                    </tbody>
                                </table>
                            </div>
                            <button type="submit" class="btn btn-primary">{{\App\CPU\translate('submit')}}</button>
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
        var options = [];
        var data = [];
        var simple = [];
        
        "use strict"
        function getProduct()
        {
            product = [];
            simple = [];
            $.get(
                `{{ route('admin.stock.create') }}?seller=${$('#seller').val()}`,
                function(data, status) {
                    options = data.option;
                    product.push(`<option value="" disabled>---{{\App\CPU\translate('select')}}---</option>`)
                    options.forEach((item) => {
                        item.forEach((it, i) => {
                            simple.push({id: it.id, name: it.name});
                            product.push(`<option id="${it.name}-${it.id}" value="${it.id}">${it.name}</option>`)
                        })
                    })

                    console.log(simple)
                    $('#products').empty().append(product);
                }
            ).then(function(data, status) {
                
            },
            function(error, status) {
                console.log(error.responseText)
            });
        }

        $('#products').on('change', function() {
            $('#data tr').remove()
            console.log($(this).val())
            $(this).val().forEach((e) => {
                simple.forEach((it) => {
                    if (e == it.id)
                    {
                        data.push(`
                        <tr>
                            <td>
                                <label for="">${it.name}</label>
                                <input type="hidden" name="product_id[]" value="${it.id}">
                            </td>
                            <td>
                                <input type="number" placeholder="Ex: 2" name="stock[]" class="form-control" required>
                            </td>
                        </tr>
                        `)
                    }
                })
                $('#data').append(data);
                data = [];
            })
        })
    </script>
@endpush
