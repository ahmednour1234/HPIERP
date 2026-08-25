@extends('layouts.admin.app')

@section('title', \App\CPU\translate('edit_price'))

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
                        <span>{{ \App\CPU\translate('edit_price') }}</span>
                    </h1>
                </div>
            </div>
        </div>
        <!-- End Page Header -->
        <div class="row gx-2 gx-lg-3">
            <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('admin.seller.prices.edit', [$seller_id, $price->id]) }}" method="post" enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                <div class="col-12 col-sm-6 col-md-6 col-lg-6">
                                    <div class="form-group">
                                        <label>{{ \App\CPU\translate('choose_product') }}</label>
                                        <select name="product_id" class="form-control js-select2-custom" required>
                                            <option value="" hidden>---{{\App\CPU\translate('select')}}---</option>
                                            @foreach(\App\Models\Product::all() as $p)
                                                <option @if($price->product_id == $p->id) selected @endif value="{{ $p->id }}">{{ $p->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-6 col-lg-6">
                                    <div class="form-group">
                                        <label>{{ \App\CPU\translate('price') }}</label>
                                        <input type="text" name="price" class="form-control"
                                            placeholder="{{ \App\CPU\translate('add_price') }}"
                                            value="{{ $price->price }}" required>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">{{ \App\CPU\translate('submit') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
    <script src={{ asset('public/assets/admin/js/global.js') }}></script>
@endpush
