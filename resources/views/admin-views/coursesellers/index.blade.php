@extends('layouts.admin.app')

@section('title',\App\CPU\translate('seller_list'))

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
                        class="tio-filter-list"></i> {{\App\CPU\translate('قائمة  كورسات الموظفين')}}
                    <span class="badge badge-soft-dark ml-2">{{$courseSellers->total()}}</span>
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
                            <div class="col-12 col-sm-7 col-md-6 col-lg-4 col-xl-6 mb-3 mb-sm-0">
<form action="{{ url()->current() }}" method="GET">
    <!-- Search -->
    <div class="input-group input-group-merge input-group-flush">
        <div class="input-group-prepend">
            <div class="input-group-text">
                <i class="tio-search"></i>
            </div>
        </div>
        <input id="datatableSearch_" type="search" name="search" class="form-control"
               placeholder="{{ \App\CPU\translate('بحث بالاسم او الايميل') }}" aria-label="Search"
               value="{{ request('search') }}" required>
        <button type="submit" class="btn btn-primary">{{ \App\CPU\translate('بحث') }}</button>
    </div>
    <!-- End Search -->
</form>
                            </div>
                            <div class="col-12 col-sm-5">
                                <a href="{{route('admin.coursesellers.create')}}" class="btn btn-primary float-right"><i
                                        class="tio-add-circle"></i> {{\App\CPU\translate('اضافة كورس للتطوير')}}
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
                                <th>{{\App\CPU\translate('الاسم')}}</th>
                                <th>{{\App\CPU\translate('الايميل')}}</th>
                            <th>{{\App\CPU\translate('المدير')}}</th>
                            <th>{{\App\CPU\translate('اسم الكورس')}}</th>
                                <th>{{ \App\CPU\translate('لينك الكورس المطلوب الانتهاء منه') }}</th>
                                <th>{{ \App\CPU\translate('الشهادات') }}</th>
                                <th>{{\App\CPU\translate('action')}}</th>
                            </tr>
                            </thead>

                            <tbody id="set-rows">
                            @foreach($courseSellers as $key=>$seller)
                                <tr>
                                    <td>{{ $key+1 }}</td>
                                    <td>
                                        {{ $seller->sellers->f_name . ' ' . $seller->sellers->l_name }}
                                    </td>
                                    <td>
                                        {{ $seller->sellers->email }}
                                    </td>
                                       <td>
                                        {{ $seller->admins->email }}
                                    </td>
                            <td>
                                        {{ $seller->name }}
                                    </td>
                                     <td>
                                        {{ $seller->link }}
                                    </td>
            @if(is_null($seller->img) || json_decode($seller->img) === null)
    <td>
        لم يتم الانتهاء من الكورس <!-- Display message if no images are present -->
    </td>
@else
    @php
        $imgData = json_decode($seller->img, true); // Decode the JSON string into an array
    @endphp
    <td>
        @if(!empty($imgData)) <!-- Check if imgData is not empty -->
            <div>
                @foreach($imgData as $image) <!-- Loop through each image in the array -->
                    <img src="{{ asset('storage/' . $image) }}" alt="Course Image" class="img-thumbnail" style="max-width: 100px; margin: 5px;"> <!-- Display each image as an img tag -->
                @endforeach
            </div>
        @else
            No images available <!-- In case imgData is empty but not null -->
        @endif
    </td>
@endif

                                    
                    <td>

       <a class="btn btn-white mr-1" href="javascript:" onclick="form_alert('seller-{{ $seller['id'] }}','Want to delete this seller?')">
        <span class="tio-delete"></span>
    </a>

    <!-- Delete Form -->
    <form action="{{ route('admin.coursesellers.destroy', [$seller['id']]) }}" method="post" id="seller-{{ $seller['id'] }}">
        @csrf
        @method('delete')
    </form>
</td>

                                </tr>
                            @endforeach
                            </tbody>
                        </table>

                        <div class="page-area">
                            <table>
                                <tfoot class="border-top">
                                {!! $courseSellers->links() !!}
                                </tfoot>
                            </table>
                        </div>
                        @if(count($courseSellers)==0)
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
        <!-- jQuery -->

<!-- Bootstrap JS -->

 
@endpush
