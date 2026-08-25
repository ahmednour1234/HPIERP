@extends('layouts.admin.app')

@section('title',\App\CPU\translate('edit_regions'))


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
                        <form action="{{route('admin.regions.update', $region->id)}}" method="post">
                            @csrf
                                <div class="row pl-2" >
                                    <div class="col-12 col-sm-6">
                                        <div class="form-group">
                                            <label class="input-label" >{{\App\CPU\translate('region_name')}} <span
                                                    class="input-label-secondary text-danger">*</span></label>
                                            <input type="text" name="region_name" class="form-control" value="{{ $region->name }}"  placeholder="{{\App\CPU\translate('region_name')}}" required>
                                        </div>
                                    </div>
                                </div>
                            <button type="submit" class="btn btn-primary">{{\App\CPU\translate('submit')}}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
