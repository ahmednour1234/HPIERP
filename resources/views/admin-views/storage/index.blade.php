@extends('layouts.admin.app')

@section('title', \App\CPU\translate('storage'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/custom.css') }}"/>
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="row align-items-center mb-3">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title d-flex align-items-center g-2px text-capitalize">
                    <i class="tio-filter-list"></i> {{ \App\CPU\translate('store_list') }}
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
                            <div class="col-12 col-sm-12">
                                <button class="btn btn-primary float-right" data-toggle="modal" data-target="#storageModal" onclick="openModal()">
                                    <i class="tio-add-circle"></i> {{ \App\CPU\translate('add_new_store') }}
                                </button>
                            </div>
                        </div>
                    </div>
                    <!-- End Header -->

                    <!-- Table -->
                    <div class="table-responsive datatable-custom">
                        <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                            <thead class="thead-light">
                            <tr>
                                <th>{{ \App\CPU\translate('#') }}</th>
                                <th>Storage</th>
                                <th>{{ \App\CPU\translate('action') }}</th>
                            </tr>
                            </thead>

                            <tbody id="set-rows">
                            @foreach($storages as $key=>$store)
                                <tr>
                                    <td>{{ $key+1 }}</td>
                                    <td>{{ $store->name }}</td>
                                    <td>
                                        <div class="d-flex flex-row">
                                            <button class="btn btn-white mr-1" data-toggle="modal" data-target="#storageModal" onclick="openModal({{ $store->id }}, '{{ $store->name }}')">
                                                <span class="tio-edit"></span>
                                            </button>
                                            <form action="{{ route('admin.storage.delete', $store->id) }}" method="post" id="store-{{ $store->id }}">
                                                @csrf
                                                @method('delete')
                                                <button type="submit" class="btn btn-white mr-1" onclick="return form_alert('store-{{ $store->id }}', 'Want to delete this store?')">
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
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <!-- End Table -->
                </div>
                <!-- End Card -->
            </div>
        </div>
    </div>

    <!-- Storage Modal -->
    <div class="modal fade" id="storageModal" tabindex="-1" aria-labelledby="storageModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="storageForm" method="post">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="storageModalLabel">{{ \App\CPU\translate('add_new_store') }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="storageName">{{ \App\CPU\translate('name') }}</label>
                            <input type="text" class="form-control" id="storageName" name="name" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ \App\CPU\translate('close') }}</button>
                        <button type="submit" class="btn btn-primary">{{ \App\CPU\translate('save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Storage Modal -->

@endsection

@push('script_2')
<script>
    function openModal(id = null, name = '') {
        let formAction = id ? '{{ url('admin/storages/update') }}/' + id : '{{ route('admin.storage.store') }}';
        let method = id ? 'PUT' : 'POST';

        $('#storageForm').attr('action', formAction);
        $('#storageForm').attr('method', 'post');
        $('#storageForm').find('input[name="_method"]').remove(); // Remove the previous _method input if it exists

        if (id) {
            $('#storageForm').append('<input type="hidden" name="_method" value="PUT">');
            $('#storageModalLabel').text('{{ \App\CPU\translate('edit_store') }}');
            $('#storageName').val(name);
        } else {
            $('#storageModalLabel').text('{{ \App\CPU\translate('add_new_store') }}');
            $('#storageName').val('');
        }
    }

    function form_alert(formId, message) {
        if (confirm(message)) {
            return true; // Allow form submission
        } else {
            return false; // Prevent form submission
        }
    }
</script>
    <script src="{{ asset('public/assets/admin/js/global.js') }}"></script>
@endpush
