@extends('layouts.admin.app')  
@section('title', \App\CPU\translate('دفع مرتب جديد'))  

@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/select2.min.css') }}">
@endpush

@section('content')
    <div class="container-fluid">
        <h3 class="mt-4">{{ \App\CPU\translate('دفع مرتب جديد') }}</h3>
        <div class="row">
            <div class="col-md-12">
                <form id="salary-form" method="POST" action="{{ route('admin.salaries.store') }}">
                    @csrf
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


                    <div class="form-group">
                        <label for="salary_of_visitors">{{ \App\CPU\translate('مكافأة الالتزام') }}</label>
                        <input type="text" id="salary_of_visitors" name="salary_of_visitors" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="score">{{ \App\CPU\translate('تقييم المدير') }}</label>
                        <input type="text" id="score" name="score" class="form-control" readonly>
                    </div>

                    <div class="form-group">
                        <label for="notemanager">{{ \App\CPU\translate('ملاحظات المدير') }}</label>
                        <input type="text" id="notemanager" name="notemanager" class="form-control" readonly>
                    </div>

                    <div class="form-group">
                        <label for="other">{{ \App\CPU\translate('بدلات أخرى') }}</label>
                        <input type="number" id="other" name="other" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="discount">{{ \App\CPU\translate('خصم') }}</label>
                        <input type="text" id="discount" name="discount" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="note">{{ \App\CPU\translate('ملاحظاتك') }}</label>
                        <input type="text" id="note" name="note" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="total">{{ \App\CPU\translate('الإجمالي') }}</label>
                        <input type="text" id="total" name="total" class="form-control" readonly>
                    </div>

                    <div class="form-group">
                        <label for="month">{{ \App\CPU\translate('عن شهر') }}</label>
                        <input type="month" id="month" name="month" class="form-control" required>
                    </div>

                    <button type="submit" class="btn btn-primary">{{ \App\CPU\translate('حفظ') }}</button>
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
                let salaryOfVisitors = parseFloat($('#salary_of_visitors').val()) || 0;
                let discount = parseFloat($('#discount').val()) || 0;
                let other = parseFloat($('#other').val()) || 0;

                let total = salary + transportAmount + salaryOfVisitors + other - discount;
                $('#total').val(total.toFixed(2));
            }

            $('#seller_id').change(function() {
                var sellerId = $(this).val();
                if (sellerId) {
                    $.ajax({
                        url: '{{ route("admin.salaries.showsalary", "") }}/' + sellerId,
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

            $('#salary_of_visitors, #transport_amount, #discount, #other').on('input', calculateTotal);
        });
    </script>
@endpush
