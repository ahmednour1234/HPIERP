@extends('layouts.admin.app')

@section('title',\App\CPU\translate('add_new_store'))

@push('css_or_js')
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/custom.css"/>
@endpush

@section('content')
<div class="content container-fluid">
        <!-- Page Header -->
        <div class="row align-items-center mb-3">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title d-flex align-items-center g-2px text-capitalize"><i
                        class="tio-add-circle-outlined"></i> {{\App\CPU\translate('add_new_store')}}
                </h1>
            </div>
        </div>
        <!-- End Page Header -->
        <div class="row gx-2 gx-lg-3">
            <div class="col-sm-12 col-lg-12  mb-lg-2">
                <div class="card">
                    <div class="card-body">
                        <form action="{{route('admin.stores.store')}}" method="post" id="product_form" enctype="multipart/form-data" >
                            @csrf
                            <div class="row pl-2" >
                               <div class="col-12 col-sm-4">
    <div class="form-group">
        <label class="input-label">{{\App\CPU\translate('store_name')}} <span
                class="input-label-secondary text-danger">*</span></label>
        <input type="text" name="store_name1" class="form-control" placeholder="Enter store name">
    </div>
</div>
<div class="col-12 col-sm-6">
    <div class="form-group">
        <label class="input-label">{{\App\CPU\translate('store_code')}}</label>
        <input type="text" name="store_code" class="form-control" placeholder="Enter store code">
    </div>
</div>

                            </div>
                            <div class="container">
                                <table class="table text-center">
                                    <thead>
                                        <tr>
                                            <th>Store Name</th>
                                            <th>Store Code</th>
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

