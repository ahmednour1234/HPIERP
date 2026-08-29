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
                                                        <div class="col-12">
                                <form action="{{ url()->current() }}" method="GET" class="mb-3">
                                    <div class="row align-items-end">
                                        <div class="col-md-3 mb-2">
                                            <label>{{ \App\CPU\translate('search') }}</label>
                                            <input type="search" name="search" class="form-control"
                                                   value="{{ request('search') }}"
                                                   placeholder="{{ \App\CPU\translate('المنتج أو المندوب') }}">
                                        </div>

                                        <div class="col-md-3 mb-2">
                                            <label>{{ \App\CPU\translate('seller') }}</label>
                                            <select name="seller_id" class="form-control">
                                                <option value="">{{ \App\CPU\translate('الكل') }}</option>
                                                @foreach (($sellers ?? []) as $s)
                                                    <option value="{{ $s->id }}"
                                                        {{ (string) request('seller_id') === (string) $s->id ? 'selected' : '' }}>
                                                        {{ trim($s->f_name . ' ' . $s->l_name) }}
                                                        @if ($s->mandob_code) ({{ $s->mandob_code }}) @endif
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-md-2 mb-2">
                                            <label>{{ \App\CPU\translate('المتبقي') }}</label>
                                            <select name="remaining" class="form-control">
                                                <option value="">{{ \App\CPU\translate('الكل') }}</option>
                                                <option value="yes" {{ request('remaining') === 'yes' ? 'selected' : '' }}>
                                                    {{ \App\CPU\translate('لديه رصيد') }}
                                                </option>
                                                <option value="no" {{ request('remaining') === 'no' ? 'selected' : '' }}>
                                                    {{ \App\CPU\translate('نفد') }}
                                                </option>
                                            </select>
                                        </div>

                                        <div class="col-md-2 mb-2">
                                            <label>{{ \App\CPU\translate('from') }}</label>
                                            <input type="date" name="from" class="form-control" value="{{ request('from') }}">
                                        </div>

                                        <div class="col-md-2 mb-2">
                                            <label>{{ \App\CPU\translate('to') }}</label>
                                            <input type="date" name="to" class="form-control" value="{{ request('to') }}">
                                        </div>

                                        <div class="col-md-12 mt-2">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="tio-filter-list"></i> {{ \App\CPU\translate('search') }}
                                            </button>

                                            <a href="{{ url()->current() }}" class="btn btn-secondary">
                                                {{ \App\CPU\translate('reset') }}
                                            </a>

                                            {{-- Carries the current filters, so the download matches the screen. --}}
                                            <a href="{{ route('admin.stock.export', request()->query()) }}"
                                               class="btn btn-success float-right">
                                                <i class="tio-file-outlined"></i> {{ \App\CPU\translate('تصدير CSV') }}
                                            </a>
                                        </div>
                                    </div>
                                </form>
                            </div>
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
                                        {{-- Return part of what the seller is carrying to the warehouse.
                                             Only offered while they still hold something. --}}
                                        @if ((float) $stock['stock'] > 0)
                                            <a class="btn btn-white mr-1" href="javascript:"
                                               title="{{ \App\CPU\translate('رد للمخزن') }}"
                                               onclick="askReturn({{ $stock['id'] }}, {{ (float) $stock['stock'] }})">
                                                <span class="tio-undo"></span>
                                            </a>
                                            <form action="{{ route('admin.stock.return', [$stock['id']]) }}"
                                                  method="post" id="return-{{ $stock['id'] }}">
                                                @csrf
                                                <input type="hidden" name="quantity" value="">
                                            </form>
                                        @endif
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
@push('script')
    <script>
        // Ask for the quantity, cap it at what the seller still holds, then
        // submit that row's hidden form.
        function askReturn(id, available) {
            const raw = prompt('{{ \App\CPU\translate("الكمية المراد ردها") }} (' + available + ')', available);
            if (raw === null) return;

            const qty = parseFloat(raw);

            if (isNaN(qty) || qty <= 0) {
                alert('{{ \App\CPU\translate("أدخل كمية صحيحة") }}');
                return;
            }
            if (qty > available) {
                alert('{{ \App\CPU\translate("الكمية أكبر من المتاح") }} (' + available + ')');
                return;
            }

            const form = document.getElementById('return-' + id);
            form.querySelector('input[name="quantity"]').value = qty;
            form.submit();
        }
    </script>
@endpush
