@extends('layouts.admin.app')

@section('title',\App\CPU\translate('update_product'))

@push('css_or_js')
    {{-- <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="{{asset('public/assets/admin/css/tags-input.min.css')}}" rel="stylesheet"> --}}
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="">
            <div class="row align-items-center mb-3">
                <div class="col-sm mb-2 mb-sm-0">
                    <h1 class="page-header-title d-flex align-items-center g-2px text-capitalize">
                        <i class="tio-edit"></i>
                        <span>{{\App\CPU\translate('update_product')}}</span>
                    </h1>
                </div>
            </div>
        </div>
        <!-- End Page Header -->
        <div class="row gx-2 gx-lg-3">
            <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
                <div class="card">
                    <div class="card-body">
                        <form action="{{route('admin.product.update',[$product['id']])}}" method="post"
                              id="product_form"
                              enctype="multipart/form-data">
                            @csrf

                            <div class="row pl-2">
                                <div class="col-12 col-sm-6">
                                    <div class="form-group">
                                        <label class="input-label"
                                               for="exampleFormControlInput1">{{\App\CPU\translate('name')}}
                                            <span class="input-label-secondary">*</span>
                                        </label>
                                        <input type="text" name="name" class="form-control"
                                               value="{{ $product['name'] }}"
                                               placeholder="{{\App\CPU\translate('product_name')}}" required>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <div class="form-group">
                                        <label class="input-label"
                                               for="exampleFormControlInput1">{{\App\CPU\translate('name_in_english')}}
                                            <span class="input-label-secondary">*</span>
                                        </label>
                                        <input type="text" name="name_en" class="form-control" value="{{ $product['name_en'] }}"
                                               placeholder="{{\App\CPU\translate('product_name_in_english')}}">
                                    </div>
                                </div>
                            </div>
                            <div class="row pl-2">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label class="input-label"
                                               for="exampleFormControlInput1">{{\App\CPU\translate('product_code_SKU')}}
                                            <span class="input-label-secondary">*</span>
                                            <a class="style-one-pu"
                                               onclick="document.getElementById('generate_number').value = getRndInteger()">{{\App\CPU\translate('generate_code')}}</a></label>
                                        <input type="text" id="generate_number" minlength="5" name="product_code"
                                               class="form-control" value="{{ $product['product_code'] }}"
                                               placeholder="{{\App\CPU\translate('product_code')}}" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row pl-2">
                                <div class="col-12 col-sm-6">
                                    <div class="form-group">
                                        <label class="input-label"
                                               for="exampleFormControlInput1">{{\App\CPU\translate('quantity')}}
                                            <span class="input-label-secondary">*</span>
                                        </label>
                                        <input type="number" min="1" name="quantity" class="form-control"
                                               value="{{ $product['quantity'] }}"
                                               placeholder="{{\App\CPU\translate('quantity')}}" required>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <div class="form-group">
                                        <label class="input-label"
                                               for="exampleFormControlSelect1">{{\App\CPU\translate('category')}}<span
                                                class="input-label-secondary">*</span></label>
                                        <select name="category_id" id="category-id"
                                                class="form-control js-select2-custom"
                                                onchange="getRequest('{{url('/')}}/admin/product/get-categories?parent_id='+this.value,'sub-categories')">
                                            <option value="">---{{\App\CPU\translate('select')}}---</option>
                                            @foreach($categories as $category)
                                                <option
                                                    value="{{$category['id']}}" {{ $category->id==$product['category_id'] ? 'selected' : ''}}>{{$category['name']}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                              <div class="col-12 col-sm-6">
    <div class="form-group">
        <label class="input-label" for="unit_type">
            {{ \App\CPU\translate('التصنيف') }}
            <span class="input-label-secondary">*</span>
        </label>
        <select name="type" id="type" class="form-control js-select2-custom">
            <option value="imported">{{ \App\CPU\translate('مستورد') }}</option>
            <option value="local">{{ \App\CPU\translate('محلي') }}</option>
            <option value="assembled">{{ \App\CPU\translate('مجمع') }}</option>
            <option value="company">{{ \App\CPU\translate('شركة') }}</option>
        </select>
    </div>
</div>
                            <div class="row pl-2">
                                <div class="col-12 col-sm-6">
                                    <div class="form-group">
                                        <label class="input-label"
                                               for="exampleFormControlInput1">{{\App\CPU\translate('unit_type')}}
                                            <span class="input-label-secondary">*</span>
                                        </label>
                                        <select name="unit_type" class="form-control js-select2-custom">
                                            <option value="">---{{\App\CPU\translate('select')}}---</option>
                                            @foreach ($units as $unit)
                                                <option
                                                    value="{{$unit['id']}}" {{ $product->unit_type==$unit['id']?'selected':'' }}>{{$unit['unit_type']}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <div class="form-group">
                                        <label class="input-label"
                                               for="exampleFormControlInput1">{{\App\CPU\translate('unit_value')}}
                                            <span class="input-label-secondary">*</span>
                                        </label>
<input type="number" name="unit_value" class="form-control"
       value="{{ round($product['unit_value']) }}"
       placeholder="{{\App\CPU\translate('unit_value')}}">


                                    </div>
                                </div>
                            </div>
                           <div class="row pl-2">
    <div class="row pl-2">
        <!-- Selling Prices -->
        <div class="col-12 col-sm-6">
            <div class="form-group">
                <label class="input-label" for="selling_price">{{\App\CPU\translate('سعر  الشراء')}}
                    <span class="input-label-secondary">*</span>
                </label>
                <input type="number" step="0.01" name="selling_price" class="form-control"
                    value="{{ $product['selling_price'] }}"
                    placeholder="{{\App\CPU\translate('selling_price')}}" required>
            </div>
        </div>

       
        <!-- Purchase Prices -->
        <div class="col-12 col-sm-6">
            <div class="form-group">
                <label class="input-label" for="purchase_price">{{\App\CPU\translate(' سعر البيع ')}}
                    <span class="input-label-secondary">*</span>
                </label>
                <input type="number" step="0.01" name="purchase_price" class="form-control"
                    value="{{ $product['purchase_price'] }}"
                    placeholder="{{\App\CPU\translate('purchase_price')}}">
            </div>
        </div>

      

    <!-- Discount and Tax -->
    <div class="row pl-2">
        <div class="col-12 col-sm-6">
            <div class="form-group">
                <label class="input-label" for="discount_type">{{\App\CPU\translate('نوع الخصم')}}</label>
                <select onchange="discount_option(this);" name="discount_type" class="form-control js-select2-custom">
                    <option value="percent" {{ old('discount_type') == 'percent' ? 'selected' : '' }}>
                        {{\App\CPU\translate('نسبة')}}
                    </option>
                    <option value="amount" {{ old('discount_type') == 'amount' ? 'selected' : '' }}>
                        {{\App\CPU\translate('رقم')}}
                    </option>
                </select>
            </div>
        </div>

        <div class="col-12 col-sm-6">
            <div class="form-group">
                <label id="percent" class="input-label">{{\App\CPU\translate('نسبة الخصم')}} (%)</label>
                <label id="amount" class="input-label d-none">{{\App\CPU\translate('discount_amount')}}</label>
                <input type="number" min="0" name="discount" class="form-control"
                    value="{{ old('discount') }}" placeholder="{{\App\CPU\translate('discount')}}">
            </div>
        </div>
    </div>

    <div class="row pl-2">
        <div class="col-12 col-sm-6">
            <div class="form-group">
                <label class="input-label" for="tax">{{\App\CPU\translate('الضريبة بالنسبة')}} (%)</label>
                <input type="number" min="0" name="tax" class="form-control" value="{{ old('tax') }}"
                    placeholder="{{\App\CPU\translate('tax')}}">
            </div>
        </div>

        <div class="col-12 col-sm-6">
            <div class="form-group">
                <label class="input-label" for="supplier_id">{{\App\CPU\translate('اختار مورد')}}</label>
                <select class="form-control js-select2-custom" name="supplier_id" id="supplier_id">
                    <option value="">---{{\App\CPU\translate('اختار')}}---</option>
                    @foreach ($suppliers as $supplier)
                    <option value="{{ $supplier['id'] }}" {{ old('supplier_id') == $supplier['id'] ? 'selected' : '' }}>
                        {{ $supplier['name'] }} ({{ $supplier['mobile'] }})
                    </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
   

    <!-- Image Upload -->
    <div class="row pl-2">
        <div class="col-12">
            <label>{{\App\CPU\translate('image')}}</label>
            <div class="custom-file">
                <input type="file" name="image" id="customFileEg1" class="custom-file-input">
                <label class="custom-file-label" for="customFileEg1">{{\App\CPU\translate('choose_file')}}</label>
            </div>
            <div class="form-group my-4">
                <center>
                    <img class="img-one-pu" id="viewer"
                        onerror="this.src='{{asset('public/assets/admin/img/400x400/img2.jpg')}}'"
                        src="{{asset('storage/product')}}/{{$product['image']}}"
                        alt="{{ \App\CPU\translate('image') }}" />
                </center>
            </div>
        </div>
     <!-- Expiry Date -->
    <div class="col-12 col-sm-6">
        <div class="form-group">
            <label class="input-label">{{ \App\CPU\translate('تاريخ انتهاء الصلاحية') }}</label>
            <input type="date" name="expiry_date" value="{{ $product['expiry_date'] }}" class="form-control" required>
        </div>
    </div>

    <!-- Submit Button -->
    <div class="col-12">
        <button type="submit" class="btn btn-primary">{{\App\CPU\translate('update')}}</button>
    </div>
</div>

            </div>
        </div>
    </div>
@endsection

@push('script_2')

    <script src={{asset("public/assets/admin/js/global.js")}}></script>
@endpush
