@extends('layouts.admin.app')

@section('title',\App\CPU\translate('category_update'))

@push('css_or_js')
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/custom.css"/>
@endpush

@section('content')
<div class="content container-fluid">
        <!-- Page Header -->
        <div class="">
            <div class="row align-items-center mb-3">
                <div class="col-sm mb-2 mb-sm-0">
                    <h1 class="page-header-title text-capitalize"><i class="tio-edit"></i> {{\App\CPU\translate('تحديث الضريبة')}}</h1>
                </div>
            </div>
        </div>
        <!-- End Page Header -->
        <div class="row gx-2 gx-lg-3">
            <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
                <div class="card">
                    <div class="card-body">
                        <form action="{{route('admin.taxe.update',[$taxe['id']])}}" method="post" enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                <div class="col-12 col-sm-6">
                                    <div class="form-group lang_form">
                                        <label class="input-label" for="exampleFormControlInput1">{{\App\CPU\translate('اسم')}} </label>
                                        <input type="text" name="name" value="{{$taxe['name']}}" class="form-control" placeholder="{{\App\CPU\translate('new_taxe')}}" required>
                                    </div>
                                        <div class="form-group lang_form">
                                        <label class="input-label" for="exampleFormControlInput1">{{\App\CPU\translate('القيمة')}} </label>
                                        <input type="text" name="amount" value="{{$taxe['amount']}}" class="form-control" placeholder="{{\App\CPU\translate('%')}}" required>
                                    </div>

                            </div>
                            <button type="submit" class="btn btn-primary">{{\App\CPU\translate('تحديث')}}</button>
                        </form>
                    </div>
                </div>
            </div>
            <!-- End Table -->
        </div>
    </div>
@endsection

@push('script_2')
    <script src={{asset("public/assets/admin/js/global.js")}}></script>
@endpush
