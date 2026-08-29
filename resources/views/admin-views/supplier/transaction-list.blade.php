@extends('layouts.admin.app')

@section('title', \App\CPU\translate('supplier_details'))

@push('css_or_js')
@endpush

@section('content')

    <div class="content container-fluid">
        <div class="page-header">
            <div>
                <h1 class="page-header-title">{{ $supplier->name }}</h1>
            </div>
            <div class="js-nav-scroller hs-nav-scroller-horizontal">
                <ul class="nav nav-tabs page-header-tabs">
                    <li class="nav-item">
                        <a class="nav-link"
                            href="{{ route('admin.supplier.view', [$supplier['id']]) }}">{{ \App\CPU\translate('تفاصيل المورد') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link"
                            href="{{ route('admin.supplier.products', [$supplier['id']]) }}">{{ \App\CPU\translate('قائمة المنتجلت') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active"
                            href="{{ route('admin.supplier.transaction-list', [$supplier['id']]) }}">{{ \App\CPU\translate('كشاف حساب المورد') }}</a>
                    </li>
                </ul>

            </div>
        </div>

        <div class="row">
            <div class="col-12 col-md-7 mt-2">
                <div class="card">

              <div class="card-body">
    <div class="row">
        <!-- Title for Total Supplier Account -->
        <span class="font-one-stl badge badge-warning">{{ \App\CPU\translate('اجمالي حساب المورد') }}</span>
        
        <!-- Supplier Due Amount (مدين) -->
        <div class="col-12 style-one-stl mt-2">
            <div class="d-flex justify-content-between align-items-center">
                <span class="font-weight-bold">{{ \App\CPU\translate('دائن') }}:</span>
                <span>
                    {{ $supplier->due_amount ? $supplier->due_amount . ' ' . \App\CPU\Helpers::currency_symbol() : '0 ' . \App\CPU\Helpers::currency_symbol() }}
                </span>
            </div>
        </div>

        <!-- Supplier Credit Amount (دائن) -->
        <div class="col-12 style-one-stl mt-2">
            <div class="d-flex justify-content-between align-items-center">
                <span class="font-weight-bold">{{ \App\CPU\translate('مدين') }}:</span>
                <span>
                    {{ $supplier->credit ? $supplier->credit . ' ' . \App\CPU\Helpers::currency_symbol() : '0 ' . \App\CPU\Helpers::currency_symbol() }}
                </span>
            </div>
        </div>

        <!-- Final Balance (Total = Due - Credit) -->
        <div class="col-12 style-one-stl mt-2">
            <div class="d-flex justify-content-between align-items-center">
                <span class="font-weight-bold">{{ \App\CPU\translate('الرصيد النهائي') }}:</span>
                @php
                    $final_balance = $supplier->due_amount - $supplier->credit;
                @endphp
                <span>
                    {{ abs($final_balance) . ' ' . \App\CPU\Helpers::currency_symbol() }} 
                    ({{ $final_balance >= 0 ? 'دائن' : 'مدين' }})
                </span>
            </div>
        </div>
    </div>
</div>

                </div>
            </div>
            <!--<div class="col-12 col-md-5 mt-2">-->
            <!--    <div class="card">-->
            <!--        <div class="card-body">-->
            <!--            <div class="row">-->
            <!--                <div class="col-12 mb-1">-->
            <!--                    <a class="col-12 btn btn-info" onclick="add_new_purchase({{ $supplier->id }});"-->
            <!--                        data-toggle="modal"-->
            <!--                        data-target="#add-new-purchase">{{ \App\CPU\translate('اضافة حساب جديد') }}</a>-->
            <!--                </div>-->
            <!--                <div class="col-12">-->
            <!--                    <a class="col-12 btn btn-success" onclick="payment_due({{ $supplier->id }});"-->
            <!--                        data-toggle="modal" data-target="#payment-due">{{ \App\CPU\translate('دفع دفعة للمورد') }}</a>-->
            <!--                </div>-->

            <!--            </div>-->
            <!--        </div>-->
            <!--    </div>-->
            <!--</div>-->
        </div>

    </div>
    <div class="content container-fluid">
        <div class="row gx-2 gx-lg-3">
            <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
                <!-- Card -->
                <div class="card">
                    <!-- Header -->
                    <div class="card-header">
                        <div class="row justify-content-between align-items-center flex-grow-1">
                            <div class="col-12 col-lg-5 mt-2 mb-lg-0">
                                <h3>{{ \App\CPU\translate('كشف الحساب') }}
                                    <span class="badge badge-soft-dark ml-2">{{ $transections->total() }}</span>
                                </h3>
                            </div>
                            <div class="col-12  mt-2">
                                <form action="{{ url()->current() }}" method="GET">
                                    <div class="row">
                                        <div class="col-12 col-md-5">
                                            <div class="form-group">
                                                <label class="input-label"
                                                    for="exampleFormControlInput1">{{ \App\CPU\translate('من تاريخ') }}
                                                </label>
                                                <input id="start_date" type="date" name="from" class="form-control"
                                                    value="{{ $from }}" required>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-5">
                                            <div class="form-group">
                                                <label class="input-label"
                                                    for="exampleFormControlInput1">{{ \App\CPU\translate('الي تاريخ') }} </label>
                                                <input id="end_date" type="date" name="to" class="form-control"
                                                    value="{{ $to }}" required>
                                            </div>
                                        </div>
                                        <div class="col-md-2 mt-md-5">
                                            <button href="" class="btn btn-success">
                                                {{ \App\CPU\translate('بحث') }}</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <!-- End Header -->

                    <!-- Table -->
                    <div class="table-responsive datatable-custom">
                        <table
                            class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                            <thead class="thead-light">
                                <tr>
                                    <th>{{ \App\CPU\translate('التاريخ') }}</th>
                                    <th>{{ \App\CPU\translate('الحساب') }}</th>
                                    <th>{{ \App\CPU\translate('نوع العملية') }}</th>
                                  <th>{{ \App\CPU\translate('الكاتب') }}</th>
                                    <th>{{ \App\CPU\translate('المبلغ') }}</th>
                                    <th>{{ \App\CPU\translate('الوصف') }}</th>
                                 
                                    <th>{{\App\CPU\translate('صورة')}}</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($transections as $key => $transaction)
                                    <tr>
                                        <td>{{ $transaction->date }}</td>
                                        <td>
                                            {{ $transaction->account ? $transaction->account->account : '' }}
                                            <br>
                                        </td>
                                                           <td>
    @if ($transaction->tran_type == 4)
        <span class="badge badge-danger">مبيعات</span>
    @elseif($transaction->tran_type == 7)
        <span class="badge badge-info">مرتجع مبيعات</span>
    @elseif($transaction->tran_type == 8)
        <span class="badge badge-warning">مشتريات</span>
    @elseif($transaction->tran_type == 14)
        <span class="badge badge-success">مرتجع مشتريات</span>
    @elseif($transaction->tran_type == 13)
        <span class="badge badge-soft-warning">دفع نقدية</span>
    @elseif($transaction->tran_type == 26)
        <span class="badge badge-soft-success">استلام نقدية</span>
    @endif
</td>
                                   <td>{{ $transaction->seller->email??''}}</td>
                                        <td>
                                            {{ $transaction->amount . ' ' . \App\CPU\Helpers::currency_symbol() }}
                                        </td>
                                        <td>
                                            {{ Str::limit($transaction->description, 30) }}
                                        </td>
                                      
                                       
                                          <td>
                                        <img class="navbar-brand-logo"
                         src="{{ asset('storage/shop/' . $transaction->img) }}" alt="Logo">
                                    </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <div class="page-area">
                            <table>
                                <tfoot class="border-top">
                                    {!! $transections->links() !!}
                                </tfoot>
                            </table>
                        </div>
                        @if (count($transections) == 0)
                            <div class="text-center p-4">
                                <img class="mb-3 img-one-stl"
                                    src="{{ asset('public/assets/admin') }}/svg/illustrations/sorry.svg"
                                    alt="{{ \App\CPU\translate('image_description') }}">
                                <p class="mb-0">{{ \App\CPU\translate('No_data_to_show') }}</p>
                            </div>
                        @endif
                    </div>
                    <!-- End Table -->
                </div>
                <!-- End Card -->
            </div>
        </div>
    </div>
    <div class="modal fade" id="add-new-purchase" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ \App\CPU\translate('اضافة حساب جديد') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('admin.supplier.add-new-purchase') }}" method="post" class="row">
                        @csrf
                        <input type="hidden" id="supplier_id" name="supplier_id">
                        <div class="form-group col-sm-6">
                            <label for="">{{ \App\CPU\translate('المبلغ الحساب') }}</label>
                            <input id="purchased_amount" type="number" step=".01" min="0"
                                class="form-control" name="purchased_amount" required>
                        </div>
                        <div class="form-group col-sm-6">
                            <label for="">{{ \App\CPU\translate('المبلغ الذي سيتم دفعه') }}</label>
                            <input id="paid_amount" onkeyup="due_calculate();" type="number" step=".01"
                                min="0" class="form-control" name="paid_amount" required>
                        </div>
                        <div class="form-group col-sm-6">
                            <label for="">{{ \App\CPU\translate('المتبقي') }}</label>
                            <input id="due_amount" type="number" step=".01" min="0" class="form-control"
                                name="due_amount" required readonly>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="input-label"
                                    for="exampleFormControlInput1">{{ \App\CPU\translate('الحساب الذي سيتم الدفع من خلاله') }} </label>
                                <select id="payment_account_id" name="payment_account_id" class="form-control" required>
                                    <option value="">---{{ \App\CPU\translate('select') }}---</option>
                                    @foreach ($accounts as $account)
                                            <option value="{{ $account['id'] }}" class="account">
                                                {{ $account['account'] }} </option>
                                    @endforeach

                                </select>
                            </div>
                        </div>
                        <div class="form-group col-sm-12">
                            <button class="btn btn-sm btn-primary"
                                type="submit">{{ \App\CPU\translate('submit') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="payment-due" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ \App\CPU\translate('دفع دفعة للمورد') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('admin.supplier.pay-due') }}" method="post" class="row">
                        @csrf
                        <input type="hidden" id="due_pay_supplier_id" name="supplier_id">
                        <div class="form-group col-sm-6">
                            <label for="">{{ \App\CPU\translate('اجمالي حساب المورد') }}</label>
                            <input id="total_due_amount" type="number" step=".01" min="0"
                                class="form-control" name="total_due_amount" value="{{ $supplier->due_amount }}"
                                required readonly>
                        </div>
                        <div class="form-group col-sm-6">
                            <label for="">{{ \App\CPU\translate('المبلغ المدفوع') }}</label>
                            <input id="pay_amount" onkeyup="due_remain();" type="number" step=".01" min="0.1"
                                max="{{ $supplier->due_amount }}" class="form-control" name="pay_amount" required>
                        </div>
                        <div class="form-group col-sm-6">
                            <label for="">{{ \App\CPU\translate('المبلغ المتبقي') }}</label>
                            <input id="remaining_due_amount" type="number" step=".01" min="0"
                                class="form-control" name="remaining_due_amount" required readonly>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="input-label"
                                    for="exampleFormControlInput1">{{ \App\CPU\translate('الحساب الذي سيتم الدفع من خلاله') }} </label>
                                <select id="payment_account_id" name="payment_account_id" class="form-control" required>
                                    <option value="">---{{ \App\CPU\translate('اختار') }}---</option>
                                    @foreach ($accounts as $account)
                                            <option value="{{ $account['id'] }}" class="account">
                                                {{ $account['account'] }} </option>
                                    @endforeach

                                </select>
                            </div>
                        </div>
                        <div class="form-group col-sm-12">
                            <button class="btn btn-sm btn-primary"
                                type="submit">{{ \App\CPU\translate('حفظ') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
    <script src={{ asset('public/assets/admin/js/global.js') }}></script>
@endpush
