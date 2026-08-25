@extends('layouts.admin.app')
@section('title','Reservation List')
@push('css_or_js')
    {{-- <meta name="csrf-token" content="{{ csrf_token() }}"> --}}
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/custom.css"/>
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="">
            <div class="row align-items-center mb-3">
                <div class="col-sm">
                    <h1 class="page-header-title text-capitalize">{{\App\CPU\translate('pos')}} {{\App\CPU\translate('reservations')}}
                        <span
                            class="badge badge-soft-dark ml-2">{{$reservations->total()}}</span></h1>
                </div>
            </div>
        </div>
        <!-- End Page Header -->
        <!-- Card -->
        <div class="card">

            <!-- Table -->
            <div class="table-responsive ">
                <table
                    class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                >
                    <thead class="thead-light">
                    <tr>
                        <th class="">
                            {{\App\CPU\translate('#')}}
                        </th>
                        <th class="table-column-pl-0">{{\App\CPU\translate('seller_name')}}</th>
                        <th>{{\App\CPU\translate('customer_name')}}</th>
                        <th>{{\App\CPU\translate('date')}}</th>
                        <th>{{\App\CPU\translate('product')}}</th>
                    </tr>
                    </thead>

                    <tbody id="set-rows">
                        {{-- dd($reservations) --}}
                    @foreach($reservations as $key => $item)
                        <tr>
                            <td>
                                {{$key + 1}}
                            </td>
                            <td class="table-column-pl-0">
                                <a class="text-primary" href="#">{{ $item->seller->f_name . ' ' . $item->seller->l_name }}</a>
                            </td>
                            <td class="table-column-pl-0">
                                <a class="text-primary" href="#">{{ $item->customer->name }}</a>
                            </td>
                            <td>{{ $item->date }}</td>
                            <td class="table-column-pl-0">
                                <a class="text-primary" href="#">@foreach(json_decode($item->data) as $data) {{ $data->product_name }}, @endforeach</a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <!-- End Table -->

            <!-- Footer -->
            <div class="card-footer">
                <!-- Pagination -->
                <div class="row justify-content-center justify-content-sm-between align-items-sm-center">
                    <div class="col-sm-auto">
                        <div class="d-flex justify-content-center justify-content-sm-end">
                            <!-- Pagination -->
                            {!! $reservations->links() !!}
                        </div>
                    </div>
                </div>
                <!-- End Pagination -->
            </div>
            @if(count($reservations)==0)
                <div class="text-center p-4">
                    <img class="mb-3 img-one-ol" src="{{asset('public/assets/admin')}}/svg/illustrations/sorry.svg"
                         alt="Image Description">
                    <p class="mb-0">{{ \App\CPU\translate('No_data_to_show')}}</p>
                </div>
            @endif
        <!-- End Footer -->
        </div>
        <!-- End Card -->
    </div>
@endsection
