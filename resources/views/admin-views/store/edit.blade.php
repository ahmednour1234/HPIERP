@extends('layouts.admin.app')

@section('title',\App\CPU\translate('update_store'))

@push('css_or_js')
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/custom.css"/>
@endpush

@section('content')
<div class="content container-fluid">
        <!-- Page Header -->
    <div class="row align-items-center mb-3">
        <div class="col-sm mb-2 mb-sm-0">
            <h1 class="page-header-title text-capitalize"><i
                    class="tio-edit"></i> {{\App\CPU\translate('update_store')}}
            </h1>
        </div>
    </div>
        <!-- End Page Header -->
        <div class="row gx-2 gx-lg-3">
            <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
                <div class="card">
                    <div class="card-body">
                        <form action="{{route('admin.stores.update',[$store->store_id])}}" method="Post" id="product_form"
                            enctype="multipart/form-data"  >
                            @csrf
                            <div class="row pl-2" >
                              <div class="col-12 col-sm-6">
    <div class="form-group">
        <label class="input-label">{{\App\CPU\translate('store_name')}} <span
                class="input-label-secondary text-danger">*</span></label>
        <input type="text" id="seller" name="store_name1" class="form-control" required value="{{ $store->store_name1 }}">
    </div>
</div>
<div class="col-12 col-sm-6">
    <div class="form-group">
        <label class="input-label">{{\App\CPU\translate('store_code')}} <span
                class="input-label-secondary text-danger">*</span></label>
        <input type="text" id="products" name="store_code" class="form-control" required value="{{ $store->store_code }}">
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

