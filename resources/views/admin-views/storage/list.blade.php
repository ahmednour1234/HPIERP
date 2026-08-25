@extends('layouts.admin.app')

@section('title', \App\CPU\translate('storage'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/custom.css') }}"/>
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="row align-items-center mb-3">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title d-flex align-items-center g-2px text-capitalize">
                    <i class="tio-filter-list"></i> {{ \App\CPU\translate('storage_seller_list') }}
                </h1>
            </div>
        </div>
        <div class="row gx-2 gx-lg-3">
            <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
                <div class="card">
                    <div class="card-header">
                        <div class="row justify-content-between align-items-center flex-grow-1">
                          
                        </div>
                    </div>
                    <div class="table-responsive datatable-custom">
                        <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                            <thead class="thead-light">
                                <tr>
                                    <th>{{ \App\CPU\translate('#') }}</th>
                                    <th>{{ \App\CPU\translate('Storage ID') }}</th>
                                    <th>{{ \App\CPU\translate('Storage Name') }}</th>
                                    <th>{{ \App\CPU\translate('Seller ID') }}</th>
                                    <th>{{ \App\CPU\translate('Seller Name') }}</th>
                                    <th>{{ \App\CPU\translate('action') }}</th>
                                </tr>
                            </thead>
                            <tbody id="set-rows">
                                @foreach($storageSellers as $key => $storageSeller)
                                    <tr>
                                        <td>{{ $key+1 }}</td>
                                        <td>{{ $storageSeller->storage->id }}</td>
                                        <td>{{ $storageSeller->storage->name }}</td>
                                        <td>{{ $storageSeller->seller->id }}</td>
                                        <td>{{ $storageSeller->seller->f_name }}</td>
                                        <td>
                                            <div class="d-flex flex-row">
                                              
                                                <form action="{{ route('admin.storageseller.delete', $storageSeller->id) }}" method="post" id="storageSeller-{{ $storageSeller->id }}">
                                                    @csrf
                                                    @method('delete')
                                                    <button type="submit" class="btn btn-white mr-1" onclick="return confirm('Want to delete this storage seller?')">
                                                        <span class="tio-delete"></span>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="page-area">
                            <table>
                                <tfoot class="border-top">
                                    {{ $storageSellers->links() }}
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="addStorageSellerModal" tabindex="-1" role="dialog" aria-labelledby="addStorageSellerModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addStorageSellerModalLabel">{{ \App\CPU\translate('add_new_storage_seller') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('admin.storageseller.store') }}" method="post" id="addStorageSellerForm">
                        @csrf
                        <div class="form-group">
                            <label for="storage_id">{{ \App\CPU\translate('Storage') }}</label>
                            <select name="storage_id" id="storage_id" class="form-control">
                                <option value="">{{ \App\CPU\translate('Select Storage') }}</option>
                                @foreach($storages as $storage)
                                    <option value="{{ $storage->id }}">{{ $storage->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="seller_id">{{ \App\CPU\translate('Sellers') }}</label>
                            <select name="seller_id[]" id="seller_id" class="form-control" multiple>
                                @foreach($sellers as $seller)
                                    <option value="{{ $seller->id }}">{{ $seller->f_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary">{{ \App\CPU\translate('submit') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
    <script>
        $(document).ready(function() {
            $('#addStorageSellerForm').on('submit', function(e) {
                e.preventDefault();
                let form = $(this);
                $.ajax({
                    type: 'POST',
                    url: form.attr('action'),
                    data: form.serialize(),
                    success: function(response) {
                        // Handle success response
                        $('#addStorageSellerModal').modal('hide');
                        location.reload();
                    },
                    error: function(response) {
                        // Handle error response
                        alert('Error: ' + response.responseJSON.message);
                    }
                });
            });
        });
    </script>
@endpush
