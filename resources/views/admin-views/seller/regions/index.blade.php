@extends('layouts.admin.app')

@section('title',\App\CPU\translate('add_new_regions'))

@push('css_or_js')
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/custom.css"/>
@endpush

@section('content')
<div class="content container-fluid">
        <!-- Page Header -->
        <div class="row align-items-center mb-3">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title d-flex align-items-center g-2px text-capitalize"><i
                        class="tio-add-circle-outlined"></i> {{\App\CPU\translate('regions')}}
                </h1>
            </div>
        </div>
        <!-- End Page Header -->
        <div class="row gx-2 gx-lg-3">
            <div class="col-sm-12 col-lg-12  mb-lg-2">
                <div class="card">
                    <div class="card-body">
                        <form action="{{route('admin.regions.store')}}" method="post" id="product_form"
                            enctype="multipart/form-data" >
                            @csrf
                                <div class="row pl-2" >
                                    <div class="col-12 col-sm-6">
                                        <div class="form-group">
                                            <label class="input-label" >{{\App\CPU\translate('region_name')}} <span
                                                    class="input-label-secondary text-danger">*</span></label>
                                            <input type="text" name="region_name" class="form-control" value="{{ old('region_name') }}"  placeholder="{{\App\CPU\translate('region_name')}}" required>
                                        </div>
                                    </div>
                                </div>
                            <button type="submit" class="btn btn-primary">{{\App\CPU\translate('submit')}}</button>
                        </form>
                    </div>

                    <div class="table-responsive datatable-custom">
                        <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                            <thead class="thead-light">
                            <tr>
                                <th>{{\App\CPU\translate('#')}}</th>
                                <th>{{\App\CPU\translate('region_name')}}</th>
                                <th>{{\App\CPU\translate('action')}}</th>
                            </tr>
                            </thead>

                            <tbody id="set-rows">
                            @foreach($regions as $key=>$reg)
                                <tr>
                                    <td>{{ $key+1 }}</td>
                                    <td>
                                        {{ $reg->name }}
                                    </td>
                                    <td>
                                        <a class="btn btn-white mr-1" href="{{ route('admin.regions.edit',$reg['id']) }}">
                                            <span class="tio-edit"></span>
                                        </a>
                                        <a class="btn btn-white mr-1" href="javascript:"
                                            onclick="form_alert('region-{{ $reg['id'] }}','Want to delete this seller?')"><span class="tio-delete"></span>
                                        </a>
                                        <form action="{{ route('admin.regions.delete',[$reg['id']]) }}"
                                                method="post" id="region-{{ $reg['id'] }}">
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
                                {!! $regions->links() !!}
                                </tfoot>
                            </table>
                        </div>
                        @if(count($regions)==0)
                            <div class="text-center p-4">
                                <img class="mb-3 w-one-cl" src="{{asset('public/assets/admin')}}/svg/illustrations/sorry.svg" alt="{{\App\CPU\translate('Image Description')}}">
                                <p class="mb-0">{{ \App\CPU\translate('No_data_to_show')}}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('script_2')
    <script src={{asset("public/assets/admin/js/global.js")}}></script>
@endpush
