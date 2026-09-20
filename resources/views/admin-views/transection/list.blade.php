@extends('layouts.admin.app')

@section('title',\App\CPU\translate('transection_list'))

@push('css_or_js')
<style>
    .tx-actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .4rem;
        padding-top: .9rem;
        margin-top: .3rem;
        border-top: 1px solid #e3ecf4;
    }

    /* وسم النوع: الاتجاه هو ما يُلوَّن، لا كل نوع بلون. */
    .tx-tag {
        display: inline-block;
        font-size: .72rem;
        font-weight: 700;
        border-radius: 99px;
        padding: .15rem .6rem;
        white-space: nowrap;
    }

    .tx-tag.is-in   { background: #e6f7ef; color: #0f7a4d; }
    .tx-tag.is-out  { background: #fdecec; color: #b3261e; }
    .tx-tag.is-move { background: #eef2f6; color: #56687a; }

    /* الأنواع غير المؤكَّدة نقدًا بعد (آجل، تحصيل) أهدأ من المؤكَّدة. */
    .tx-tag.is-soft { background: #eaf4fb; color: #14395c; }

    /* المبالغ أرقام: تُقرأ يسارًا وتصطفّ على خانة واحدة. */
    .tx-amount,
    .tx-balance {
        direction: ltr;
        display: inline-block;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }

    .tx-balance { font-weight: 700; color: #1c2b3a; }
</style>
@endpush

@section('content')
<div class="content container-fluid">
        <!-- Page Header -->
        <div class="row align-items-center mb-3">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title d-flex align-items-center g-2px text-capitalize"><i
                        class="tio-files"></i> {{\App\CPU\translate('قائمة التحويلات')}}
                    <span class="badge badge-soft-dark ml-2">{{$transections->total()}}</span>
                </h1>
            </div>
        </div>

        <!-- End Page Header -->
        <div class="row ">
            <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
                <!-- Card -->
                <div class="card">
                    <!-- Header -->
                    <form action="{{url()->current()}}" method="GET">
                        <div class="row m-1">

                            <div class="form-group col-12 col-sm-6 col-md-3 col-lg-3">
                                <label class="input-label" for="exampleFormControlInput1">{{\App\CPU\translate('الحساب')}} </label>
                                <select id="account_id" name="account_id" class="form-control js-select2-custom">
                                    <option value="">---{{\App\CPU\translate('اختار')}}---</option>
                                    @foreach ($accounts as $account)
                                        <option value="{{$account['id']}}" {{ $acc_id==$account['id']?'selected':''}}>{{$account['account']}}</option>
                                    @endforeach
                                </select>
                            </div>
                  

                            <div class="form-group col-12 col-sm-6 col-md-3 col-lg-3">
                                <label class="input-label" for="exampleFormControlInput1">{{\App\CPU\translate('النوع')}} </label>
                                <select id="tran_type" name="tran_type" class="form-control js-select2-custom">
                                    <option value="">---{{\App\CPU\translate('اختار')}}---</option>
                                    <option value="Expense" {{ $tran_type=='Expense'?'selected':''}}>{{\App\CPU\translate('المصروفات')}}</option>
                                    <option value="Transfer" {{ $tran_type=='Transfer'?'selected':''}}>{{\App\CPU\translate('التحويلات')}}</option>
                                    <option value="Income" {{ $tran_type=='Income'?'selected':''}}>{{\App\CPU\translate('الدخل')}}</option>
    <!--                    <option value="مشتريات" {{ $tran_type == 4 ? 'selected' : '' }}>{{ \App\CPU\translate('مشتريات') }}</option>-->
    <!--<option value="مردود مشتريات" {{ $tran_type == 7 ? 'selected' : '' }}>{{ \App\CPU\translate('مردود مشتريات') }}</option>-->
    {{-- "مبيعات أجل" و"مبيعات" كلاهما نوع 4، ويفرّق بينهما cash، فيُمرَّران
         كقيمتين مركّبتين يفكّهما المتحكّم. --}}
    <option value="4_credit" {{ $tran_type === '4_credit' ? 'selected' : '' }}>{{ \App\CPU\translate('مبيعات أجل') }}</option>
    <option value="4_cash" {{ $tran_type === '4_cash' ? 'selected' : '' }}>{{ \App\CPU\translate('مبيعات') }}</option>
    <option value="26" {{ $tran_type == 26 ? 'selected' : '' }}>{{ \App\CPU\translate('تحصيل نقدي') }}</option>
    <option value="13" {{ $tran_type == 13 ? 'selected' : '' }}>{{ \App\CPU\translate('تحصيل من الآجل') }}</option>
    <option value="7" {{ $tran_type == 7 ? 'selected' : '' }}>{{ \App\CPU\translate('مرتجع') }}</option>
                                </select>
                            </div>

                            <div class="form-group col-12 col-sm-6 col-md-3 col-lg-3">
                                <label class="input-label" for="exampleFormControlInput1">{{\App\CPU\translate('من تاريخ')}} </label>
                                <input id="start_date" type="date" name="from" class="form-control" value="{{ $from }}">
                            </div>

                            <div class="form-group col-12 col-sm-6 col-md-3 col-lg-3">
                                <label class="input-label" for="exampleFormControlInput1">{{\App\CPU\translate('الي تاريخ')}} </label>
                                <input id="end_date" type="date" name="to" class="form-control" value="{{ $to }}">
                            </div>

                        @if ($acc_id!=null || $tran_type!=null || $from!=null || $to!=null)
                            <?php
                                $chk = 1;
                            ?>
                        @else
                        <?php
                                $chk = 0;
                            ?>
                        @endif

                            <div class="col-12">
                                {{-- كانت ثلاثة أزرار بثلاثة ألوان، واثنان منها
                                     يحملان كلمة «بحث» نفسها بينما الثاني يمسح
                                     الفلاتر. فعل واحد أساسي والبقية ثانوية. --}}
                                <div class="tx-actions">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="tio-search mr-1"></i> {{\App\CPU\translate('بحث')}}
                                    </button>

                                    @if($chk)
                                        <a href="{{ route('admin.account.list-transection') }}"
                                           class="btn btn-outline-secondary">
                                            {{\App\CPU\translate('إعادة تعيين')}}
                                        </a>
                                    @endif

                                    <span class="flex-grow-1"></span>

                                    <a href="{{ route('admin.account.transection-export',['account_id'=>$acc_id,'tran_type'=>$tran_type,'from'=>$from,'to'=>$to]) }}"
                                       class="btn btn-outline-primary"
                                       data-toggle="tooltip" data-placement="top"
                                       title="{{ $chk==0?\App\CPU\translate('export_last_month_data'):''}}">
                                        <i class="tio-file-outlined mr-1"></i> {{\App\CPU\translate('اصدار في اكسل شيت')}}
                                    </a>
                                </div>
                            </div>


                    </div>
                    </form>


                    <!-- End Header -->

                    <!-- Table -->
                    <div class="table-responsive datatable-custom">
                        <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                            <thead class="thead-light">
                            <tr>
                                <th>{{ \App\CPU\translate('التاريخ') }}</th>
                                <th>{{ \App\CPU\translate('الحساب') }}</th>
                                <th>{{ \App\CPU\translate('الكاتب') }}</th>
                                <th>{{\App\CPU\translate('النوع')}}</th>
                                <th>{{\App\CPU\translate('المبلغ')}}</th>
                                <th >{{\App\CPU\translate('الوصف')}}</th>
                                <th >{{\App\CPU\translate('الاجمالي')}}</th>
                            </tr>
                            </thead>

                            <tbody>
                                @foreach ($transections as $key=>$transection)
                                    <tr>

                                        <td>{{ $transection->date }}</td>
                                        <td>
                                            {{ $transection->account ? $transection->account->account : ' '}} <br>
                                        </td> <td>
                                            {{ $transection->seller->email ?? ' '}} <br>
                                        </td>
                                        
                                      <td>
    {{-- لون واحد لكل اتجاه: أخضر داخل، أحمر خارج، رمادي نقل بين حسابين
         لنا. كانت ثمانية ألوان بلا قاعدة، فيظهر «مبيعات» أحمر و«مرتجع
         مشتريات» أخضر، وهو عكس المعنى. --}}
    @if ($transection->tran_type == 'Expense')
        <span class="tx-tag is-out">{{ \App\CPU\translate('المصروفات') }}</span>
    @elseif($transection->tran_type == 'Transfer')
        <span class="tx-tag is-move">{{ \App\CPU\translate('التحويلات') }}</span>
    @elseif($transection->tran_type == 'Income')
        <span class="tx-tag is-in">{{ \App\CPU\translate('الدخل') }}</span>
    {{-- المسميات حسب المطلوب. البيع من نوع 4 ينقسم بحسب cash:
         cash = 2 آجل، cash = 1 نقدي — وهو ما تؤكده البيانات. --}}
    @elseif ($transection->tran_type == 4)
        @if($transection->cash == 2)
            <span class="tx-tag is-in is-soft">مبيعات أجل</span>
        @else
            <span class="tx-tag is-in">مبيعات</span>
        @endif
    @elseif($transection->tran_type == 7)
        <span class="tx-tag is-out">مرتجع</span>
    @elseif($transection->tran_type == 8)
        <span class="tx-tag is-out">مشتريات</span>
    @elseif($transection->tran_type == 14)
        <span class="tx-tag is-in">مرتجع مشتريات</span>
    @elseif($transection->tran_type == 13)
        <span class="tx-tag is-in is-soft">تحصيل من الآجل</span>
    @elseif($transection->tran_type == 26)
        <span class="tx-tag is-in is-soft">تحصيل نقدي</span>
    @endif
</td>

                                        <td>
                                            <span class="tx-amount">{{ $transection->amount ." ".\App\CPU\Helpers::currency_symbol()}}</span>
                                        </td>
                                        <td>
                                            {{ Str::limit($transection->description,30) }}
                                        </td>
                                       

                                        <td>
                                            <span class="tx-balance">{{ $transection->balance ." ".\App\CPU\Helpers::currency_symbol()}}</span>
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
                        @if(count($transections)==0)
                            <div class="text-center p-4">
                                <img class="mb-3 img-one-tranl" src="{{asset('public/assets/admin')}}/svg/illustrations/sorry.svg" alt="{{\App\CPU\translate('image_description')}}">
                                <p class="mb-0">{{ \App\CPU\translate('لاتوجد بيانات لعرضها')}}</p>
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
    <script src={{asset("public/assets/admin/js/transaction.js")}}></script>
@endpush
