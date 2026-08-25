@extends('layouts.admin.app')

@section('title',\App\CPU\translate('stock_list'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/custom.css"/>
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="row align-items-center mb-3">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title d-flex align-items-center g-2px text-capitalize"><i
                        class="tio-filter-list"></i> {{\App\CPU\translate('stock_list')}}
                    <span class="badge badge-soft-dark ml-2">{{$stocks->total()}}</span>
                </h1>
            </div>
        </div>
        <!-- End Page Header -->
        <div class="row gx-2 gx-lg-3">
            <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
                <!-- Card -->
                <div class="card">
                    <!-- Header -->
                    <div class="card-header">
                        <div class="row justify-content-between align-items-center flex-grow-1">
                            {{-- <div class="col-12 col-sm-7 col-md-6 col-lg-4 col-xl-6 mb-3 mb-sm-0">
                                <form action="{{url()->current()}}" method="GET">
                                    <!-- Search -->
                                    <div class="input-group input-group-merge input-group-flush">
                                        <div class="input-group-prepend">
                                            <div class="input-group-text">
                                                <i class="tio-search"></i>
                                            </div>
                                        </div>
                                        <input id="datatableSearch_" type="search" name="search" class="form-control"
                                               placeholder="{{\App\CPU\translate('search_by_name')}}" aria-label="Search" value="{{ '$search' }}"  required>
                                        <button type="submit" class="btn btn-primary">{{\App\CPU\translate('search')}} </button>

                                    </div>
                                    <!-- End Search -->
                                </form>
                            </div> --}}
                            <div class="col-12 col-sm-12">
                                <a href="{{route('admin.stock.create')}}" class="btn btn-primary float-right"><i
                                        class="tio-add-circle"></i> {{\App\CPU\translate('add_new_stock')}}
                                </a>
                            </div>
                        </div>
                    </div>
                    <!-- End Header -->

                    <!-- Table -->
                    <div class="table-responsive datatable-custom">
                        <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                            <thead class="thead-light">
                            <tr>
                                <th>{{\App\CPU\translate('#')}}</th>
                                <th>{{\App\CPU\translate('vehicle_code')}}</th>
                                <th>{{\App\CPU\translate('seller_code')}}</th>
                                <th>{{ \App\CPU\translate('seller_name') }}</th>
                                <th>{{ \App\CPU\translate('product_name') }}</th>
                                <th>{{\App\CPU\translate('action')}}</th>
                            </tr>
                            </thead>

                            <tbody id="set-rows">
                            @foreach($stocks as $key=>$stock)
                                <tr>
                                    <td>{{ $key+1 }}</td>
                                    <td>
                                        {{ $stock->seller->vehicle_code }}
                                    </td>
                                    <td>
                                        {{ $stock->seller->mandob_code }}
                                    </td>
                                    <td>
                                        {{ $stock->seller->f_name . ' ' . $stock->seller->l_name }}
                                    </td>
                                    <td>
                                        {{ $stock->product->name }}
                                    </td>
                                    <td>
                                        {{-- <a class="btn btn-white mr-1" href="{{route('admin.stock.view',[$stock['id']])}}"><span class="tio-visible"></span></a> --}}
                                        <a class="btn btn-white mr-1"
                                            href="{{route('admin.stock.edit',[$stock['id']])}}">
                                            <span class="tio-edit"></span>
                                        </a>
                                        <a class="btn btn-white mr-1" href="javascript:"
                                            onclick="form_alert('stock-{{$stock['id']}}','Want to delete this stock?')"><span class="tio-delete"></span>
                                        </a>
                                        <form action="{{route('admin.stock.delete',[$stock['id']])}}"
                                                method="post" id="stock-{{$stock['id']}}">
                                            @csrf @method('delete')
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>

                        <div class="page-area">
                            <table>
                                <tfoot class="border-top">
                                {!! $stocks->links() !!}
                                </tfoot>
                            </table>
                        </div>
                        @if(count($stocks)==0)
                            <div class="text-center p-4">
                                <img class="mb-3 w-one-cl" src="{{asset('public/assets/admin')}}/svg/illustrations/sorry.svg" alt="{{\App\CPU\translate('Image Description')}}">
                                <p class="mb-0">{{ \App\CPU\translate('No_data_to_show')}}</p>
                            </div>
                        @endif
                    </div>
                    <!-- End Table -->
                </div>
                <!-- End Card -->
            </div>
        </div>
    </div>
@endsection

@push('script_2')
    <script src={{asset("public/assets/admin/js/global.js")}}></script>
@endpush
