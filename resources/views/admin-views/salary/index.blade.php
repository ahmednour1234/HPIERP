@extends('layouts.admin.app')  
@section('title', \App\CPU\translate('دفع مرتب جديد'))  

@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/select2.min.css') }}">
    <style>
        .pay-page { --py-navy:#11245a; --py-ink:#1f2d3d; --py-muted:#6b7a90;
                    --py-line:#e6edf5; --py-soft:#f6f9fc; }

        .pay-head { margin-bottom:1.1rem; }
        .pay-head h1 {
            display:flex; align-items:center; gap:.55rem; margin:0;
            font-size:1.3rem; font-weight:800; color:var(--py-navy);
        }
        .pay-head small { color:var(--py-muted); }

        .pay-card {
            border:1px solid var(--py-line); border-radius:14px; background:#fff;
            box-shadow:0 10px 26px rgba(15,23,42,.06); overflow:hidden;
        }

        /* أقسام: ثمانية عشر حقلًا في عمودٍ واحد تجعل إيجاد حقلٍ بعينه
           مسحًا للصفحة كلها. */
        .pay-section { padding:1.2rem 1.35rem; border-top:1px solid var(--py-line); }
        .pay-section:first-of-type { border-top:0; }

        .pay-section-title {
            display:flex; align-items:center; gap:.5rem; margin:0 0 1rem;
            font-size:.88rem; font-weight:800; color:var(--py-navy);
        }
        .pay-section-title span { flex:1; height:1px; background:var(--py-line); }

        .pay-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:1rem; }
        .pay-grid--2 { grid-template-columns:repeat(2,minmax(0,1fr)); }
        .pay-grid .wide { grid-column:1 / -1; }

        .pay-page .form-group { margin:0; }

        .pay-page label {
            display:block; font-size:.8rem; font-weight:700;
            color:var(--py-navy); margin-bottom:.35rem;
        }

        .pay-page .form-control,
        .pay-page select.form-control {
            border:1px solid var(--py-line); border-radius:9px;
            min-height:42px; font-size:.88rem; color:var(--py-ink);
        }
        .pay-page .form-control:focus {
            border-color:#8fb4e8; box-shadow:0 0 0 .18rem rgba(27,59,122,.1);
        }

        /* الحقول المقروءة فقط تبدو مقروءةً: كانت كالمدخلات تمامًا
           فيُحاوَل الكتابة فيها. */
        .pay-page .form-control[readonly] {
            background:var(--py-soft); color:var(--py-muted); font-weight:600;
        }

        /* الإجمالي هو ما يُقرأ أولًا. */
        #total {
            background:#eef3fb !important; color:var(--py-navy) !important;
            font-size:1.05rem; font-weight:800;
        }

        .pay-foot {
            display:flex; justify-content:flex-end; gap:.6rem;
            padding:1.05rem 1.35rem; background:var(--py-soft);
            border-top:1px solid var(--py-line);
        }
        .pay-foot .btn { border-radius:9px; font-weight:700; padding:.5rem 1.8rem; }

        @media (max-width: 991.98px) { .pay-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
        @media (max-width: 575.98px) {
            .pay-grid, .pay-grid--2 { grid-template-columns:1fr; }
            .pay-section { padding:1rem; }
            .pay-foot { flex-direction:column-reverse; }
            .pay-foot .btn { width:100%; }
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid pay-page">
        <div class="pay-head">
            <h1><i class="tio-money"></i> {{ \App\CPU\translate('دفع مرتب جديد') }}</h1>
            <small>تُجلب أرقام الموظف تلقائيًّا عند اختياره وتحديد الشهر.</small>
        </div>

        <div class="row">
            <div class="col-md-12">
                <form id="salary-form" method="POST" action="{{ route('admin.salaries.store') }}">
                    @csrf

                    <div class="pay-card">

                    <div class="pay-section">
                    <h6 class="pay-section-title"><i class="tio-user"></i> الموظف والشهر <span></span></h6>
                    <div class="pay-grid pay-grid--2">
                    <div class="form-group">
                        <label for="seller_id">{{ \App\CPU\translate('اختار موظف') }}</label>
                        <select id="seller_id" name="seller_id" class="form-control select2" required>
                            <option value="">{{ \App\CPU\translate('اختار موظف') }}</option>
                            @foreach($sellers as $seller)
                                <option value="{{ $seller->id }}">{{ $seller->email }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="month">{{ \App\CPU\translate('عن شهر') }}</label>
                        <input type="month" id="month" name="month" class="form-control" required>
                    </div>

                    </div>
                    </div>

                    <div class="pay-section">
                    <h6 class="pay-section-title"><i class="tio-chart-bar-4"></i> الأداء والتحصيل <span></span></h6>
                    <div class="pay-grid">
                    <div class="form-group">
                        <label for="salary">{{ \App\CPU\translate('الراتب') }}</label>
                        <input type="text" id="salary" name="salary" class="form-control" readonly>
                    </div>

                    <div class="form-group">
                        <label for="commission">{{ \App\CPU\translate('اجمالي التحصيلات') }}</label>
                        <input type="text" id="commission" name="commission" class="form-control" readonly>
                    </div>

                    <div class="form-group">
                        <label for="transport_amount">{{ \App\CPU\translate('حافز البيع') }}</label>
                        <input type="text" id="transport_amount" name="transport_amount" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="collection_incentive">{{ \App\CPU\translate('حافز التحصيل') }}</label>
                        <input type="text" id="collection_incentive" name="collection_incentive"
                               class="form-control" value="0">
                    </div>

                    <div class="form-group">
                        <label for="number_of_days">{{ \App\CPU\translate('عدد أيام العمل') }}</label>
                        <input type="text" id="number_of_days" name="number_of_days" class="form-control" readonly>
                    </div>

                    <div class="form-group">
                        <label for="holidays">{{ \App\CPU\translate('رصيد الأجازات') }}</label>
                        <input type="text" id="holidays" name="holidays" class="form-control" readonly>
                    </div>

                    <div class="form-group">
                        <label for="number_of_visitors">{{ \App\CPU\translate('عدد الزيارات المتوقعة') }}</label>
                        <input type="text" id="number_of_visitors" name="number_of_visitors" class="form-control" readonly>
                    </div>

                    <div class="form-group">
                        <label for="result_of_visitors">{{ \App\CPU\translate('عدد الزيارات الفعلية') }}</label>
                        <input type="text" id="result_of_visitors" name="result_of_visitors" class="form-control" readonly>
                    </div>
                    <div class="col-md-4">
  <label class="form-label">نسبة تنفيذ الزيارات</label>
  <input type="text" class="form-control" id="visits_ratio" readonly>
</div>


                    </div>
                    </div>

                    <div class="pay-section">
                    <h6 class="pay-section-title"><i class="tio-gift"></i> الحوافز والبدلات <span></span></h6>
                    <div class="pay-grid">
                    <div class="form-group">
                        <label for="salary_of_visitors">{{ \App\CPU\translate('مكافأة الالتزام') }}</label>
                        <input type="text" id="salary_of_visitors" name="salary_of_visitors" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="score">{{ \App\CPU\translate('تقييم المدير') }}</label>
                        <input type="text" id="score" name="score" class="form-control" readonly>
                    </div>

                    <div class="form-group wide">
                        <label for="notemanager">{{ \App\CPU\translate('ملاحظات المدير') }}</label>
                        <input type="text" id="notemanager" name="notemanager" class="form-control" readonly>
                    </div>

                    </div>
                    </div>

                    <div class="pay-section">
                    <h6 class="pay-section-title"><i class="tio-notes"></i> الخصومات والملاحظات <span></span></h6>
                    <div class="pay-grid">
                    <div class="form-group">
                        <label for="other">{{ \App\CPU\translate('بدلات أخرى') }}</label>
                        <input type="number" id="other" name="other" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="discount">{{ \App\CPU\translate('خصم') }}</label>
                        <input type="text" id="discount" name="discount" class="form-control" required>
                    </div>

                    <div class="form-group wide">
                        <label for="note">{{ \App\CPU\translate('ملاحظاتك') }}</label>
                        <input type="text" id="note" name="note" class="form-control" required>
                    </div>

                    <div class="form-group wide">
                        <label for="total">{{ \App\CPU\translate('الإجمالي') }}</label>
                        <input type="text" id="total" name="total" class="form-control" readonly>
                    </div>

                    </div>
                    </div>

                    <div class="pay-foot">
                        <a href="{{ route('admin.salaries.index') }}" class="btn btn-secondary">إلغاء</a>
                        <button type="submit" class="btn btn-primary">{{ \App\CPU\translate('حفظ') }}</button>
                    </div>

                    </div>{{-- pay-card --}}
                </form>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
    <script src="{{ asset('public/assets/admin/js/jquery.min.js') }}"></script>
    <script src="{{ asset('public/assets/admin/js/select2.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            $('.select2').select2();

            function calculateTotal() {
                let salary = parseFloat($('#salary').val()) || 0;
                let commission = parseFloat($('#commission').val()) || 0;
                let transportAmount = parseFloat($('#transport_amount').val()) || 0;
                let collectionIncentive = parseFloat($('#collection_incentive').val()) || 0;
                let salaryOfVisitors = parseFloat($('#salary_of_visitors').val()) || 0;
                let discount = parseFloat($('#discount').val()) || 0;
                let other = parseFloat($('#other').val()) || 0;

                let total = salary + transportAmount + collectionIncentive + salaryOfVisitors + other - discount;
                $('#total').val(total.toFixed(2));
            }

            $('#seller_id').change(function() {
                var sellerId = $(this).val();
                if (sellerId) {
                    $.ajax({
                        // الشهر يُمرَّر ليُعدّ عليه: الكشف يُحرَّر غالبًا
                        // بعد انتهاء شهره، فالعدّ على «الآن» يُظهر صفرًا.
                        url: '{{ route("admin.salaries.showsalary", "") }}/' + sellerId
                             + '?month=' + ($('#month').val() || ''),
                        method: 'GET',
                        success: function(data) {
                            $('#salary').val(data.salary);
                            $('#commission').val(data.commission);
                            $('#score').val(data.score);
                            $('#number_of_visitors').val(data.visitors);
                            $('#result_of_visitors').val(data.result_visitors);
                            $('#notemanager').val(data.note || 'لا توجد ملاحظات');
                            $('#holidays').val(data.holidays);
                            $('#number_of_days').val(data.number_of_days);
                                let resultVisitors = parseFloat(data.result_visitors) || 0;
    let totalVisitors = parseFloat(data.visitors) || 0;
    let ratio = totalVisitors > 0 ? (resultVisitors / totalVisitors * 100).toFixed(2) + '%' : '0%';
    $('#visits_ratio').val(ratio);
                            calculateTotal();
                        },
                        error: function() {
                            alert('Error fetching salary details.');
                        }
                    });
                } else {
                    $('#salary, #commission, #score, #number_of_visitors, #result_of_visitors, #notemanager, #holidays, #number_of_days').val('');
                    calculateTotal();
                }
            });

            // تغيير الشهر يعيد جلب زيارات ذلك الشهر.
            $('#month').on('change', function () {
                $('#seller_id').trigger('change');
            });

            $('#salary_of_visitors, #transport_amount, #collection_incentive, #discount, #other').on('input', calculateTotal);
        });
    </script>
@endpush
