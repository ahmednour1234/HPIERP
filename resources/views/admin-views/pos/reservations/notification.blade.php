@extends('layouts.admin.app')

@section('title', 'Reservations List')

@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/custom.css') }}">
@endpush

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="row align-items-center mb-3">
        <div class="col-sm">
            <h1 class="page-header-title text-capitalize">
                {{ \App\CPU\translate('pos') }} {{ \App\CPU\translate('reservations') }}
                <span class="badge badge-soft-dark ml-2">{{ $reservations->total() }}</span>
            </h1>
        </div>
    </div>
    <!-- End Page Header -->

    <!-- Card -->
    <div class="card">
        <!-- Header -->
        <div class="card-header">
            <div class="row justify-content-between align-items-center flex-grow-1">
                <div class="col-sm-8 col-md-6 col-lg-6 mb-3 mb-lg-0">
                    <form action="{{ url()->current() }}" method="GET">
                        <!-- Search by Order ID -->
                        <div class="input-group input-group-merge input-group-flush">
                            <div class="input-group-prepend">
                                <div class="input-group-text">
                                    <i class="tio-search"></i>
                                </div>
                            </div>
                            <input type="search" name="search" class="form-control" placeholder="{{ \App\CPU\translate('search_by_customer_name_or_seller_name') }}" aria-label="Search" value="{{ $search }}">
                            <button type="submit" class="btn btn-primary">{{ \App\CPU\translate('search') }}</button>
                        </div>
                        <!-- End Search by Order ID -->

                        <!-- Search by Date Range -->
                        <div class="input-group mt-3">
                            <input type="date" name="from_date" class="form-control" placeholder="From Date" value="{{ $fromDate }}" aria-label="From Date">
                            <div class="input-group-append">
                                <span class="input-group-text">-</span>
                            </div>
                            <input type="date" name="to_date" class="form-control" placeholder="To Date" value="{{ $toDate }}" aria-label="To Date">
                        </div>
                        <!-- End Search by Date Range -->
                    </form>
                </div>

                <div class="col-lg-6"></div>
            </div>
            <!-- End Row -->
        </div>
        <!-- End Header -->

        <!-- Table -->
        <div class="table-responsive">
<table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
    <thead class="thead-light">
        <tr>
            <th>{{ \App\CPU\translate('#') }}</th>
            <th>{{ \App\CPU\translate('Seller Name') }}</th>
            <th>{{ \App\CPU\translate('Customer Name') }}</th>
            <th>{{ \App\CPU\translate('Products') }}</th>
            <th>{{ \App\CPU\translate('Date') }}</th>
            <th>{{ \App\CPU\translate('Actions') }}</th>
        </tr>
    </thead>
    <tbody id="set-rows">
        @foreach($reservations as $key => $item)
            @php
                $bgColor = ($item->type == 4) ? 'bg-danger' : ''; // Check if type is 4
            @endphp
            <tr class="{{ $bgColor }}">
                <td>{{ $key + 1 }}</td>
                <td>{{ $item->seller->f_name }} {{ $item->seller->l_name }}</td>
                <td>{{ $item->customer->name }}</td>
                <td>
                    <ul>
                        @foreach(json_decode($item->data) as $value)
                            @php $product = \App\Models\Product::find($value->product_id) @endphp
                            <li>{{ $product->name . ' ' .  $value->stock }}</li>
                        @endforeach
                    </ul>
                </td>
                <td>{{ date('d M Y', strtotime($item['created_at'])) }}</td>
                <td>
                    <button class="btn btn-sm btn-white" target="_blank" type="button" onclick="print_invoice('{{ $item->id }}')">
                        <i class="tio-download"></i> {{ \App\CPU\translate('Invoice') }}
                    </button>
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

            @if(count($reservations) == 0)
                <div class="text-center p-4">
                    <img class="mb-3 img-one-ol" src="{{ asset('public/assets/admin/svg/illustrations/sorry.svg') }}" alt="Image Description">
                    <p class="mb-0">{{ \App\CPU\translate('No_data_to_show') }}</p>
                </div>
            @endif
        </div>
        <!-- End Footer -->
    </div>
    <!-- End Card -->
</div>

<div class="modal fade" id="print-invoice" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content modal-content1">
            <div class="modal-header">
                <h5 class="modal-title">{{ \App\CPU\translate('Print') }} {{ \App\CPU\translate('Invoice') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span class="text-dark" aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body row">
                <div class="col-md-12">
                    <center>
                        <input type="button" class="mt-2 btn btn-primary non-printable" onclick="printDiv('printableArea')" value="{{ \App\CPU\translate('Proceed, If thermal printer is ready') }}."/>
                        <a href="{{ url()->previous() }}" class="mt-2 btn btn-danger non-printable">{{ \App\CPU\translate('Back') }}</a>
                    </center>
                    <hr class="non-printable">
                </div>
                <div class="row m-auto" id="printableArea">
                    <!-- Content loaded dynamically -->
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script_2')
<script>
    "use strict";
    function print_invoice(id) {
        $.get({
            url: '{{ url('/admin/pos/reservationsnotification/invoice/') }}' + '/' + id,
            dataType: 'json',
            beforeSend: function () {
                $('#loading').show();
            },
            success: function (data) {
                $('#print-invoice').modal('show');
                $('#printableArea').empty().html(data.view);
            },
            complete: function () {
                $('#loading').hide();
            },
            error: function (error) {
                console.log(error);
            }
        });
    }
</script>
<script src="{{ asset('public/assets/admin/js/global.js') }}"></script>
@endpush
