@extends('layouts.admin.app')  
@section('title', \App\CPU\translate('تقييم الموظف'))  

@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/select2.min.css') }}">
@endpush

@section('content')
    <div class="container-fluid">
        <h3 class="mt-4">{{ \App\CPU\translate('تقييم الموظف') }}</h3>
        <div class="row">
            <div class="col-md-12">
                <form id="salary-form" method="POST" action="{{ route('admin.salaries.storerating') }}">
                    @csrf
                    <div class="form-group">
                        <label for="seller_id">{{ \App\CPU\translate('اختار موظف') }}</label>
                        <select id="seller_id" name="seller_id" class="form-control" required>
                            <option value="">{{ \App\CPU\translate('اختار موظف') }}</option>
                            @foreach($sellers as $seller)
                                <option value="{{ $seller->id }}">{{ $seller->email }}</option>
                            @endforeach
                        </select>
                    </div>

                 

                    <div class="form-group">
                        <label for="commission">{{ \App\CPU\translate('اجمالي التحصيلات') }}</label>
                        <input type="text" id="commission" name="commission" class="form-control" readonly>
                    </div>

                    <div class="form-group">
                        <label for="number_of_visitors">{{ \App\CPU\translate('عدد الزيارات التي من المتفرض القيام بها') }}</label>
                        <input type="text" id="number_of_visitors" name="number_of_visitors" class="form-control" readonly>
                    </div>

                    <div class="form-group">
                        <label for="result_of_visitors">{{ \App\CPU\translate('عدد الزيارات التي قام بها بالفعل') }}</label>
                        <input type="text" id="result_of_visitors" name="result_of_visitors" class="form-control" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label for="score">{{ \App\CPU\translate('التقييم') }}</label>
                        <input type="text" id="score" name="score" class="form-control" required>
                    </div>
                     <div class="form-group">
                        <label for="note">{{ \App\CPU\translate('ملاحظات') }}</label>
                        <input type="text" id="note" name="note" class="form-control" required>
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
            function calculateTotal() {
                let salary = parseFloat($('#salary').val()) || 0;
                let commission = parseFloat($('#commission').val()) || 0;
                let transportAmount = parseFloat($('#transport_amount').val()) || 0;
                let salaryOfVisitors = parseFloat($('#salary_of_visitors').val()) || 0;
                let discount = parseFloat($('#Discount').val()) || 0;

                let total = salary + commission + transportAmount + salaryOfVisitors - discount;
                $('#total').val(total.toFixed(2));
            }

            $('#seller_id').change(function() {
                var sellerId = $(this).val();
                if (sellerId) {
                    $.ajax({
                        url: '{{ route("admin.salaries.showsalary", "") }}' + '/' + sellerId,
                        method: 'GET',
                        success: function(data) {
                            $('#salary').val(data.salary);
                            $('#commission').val(data.commission);
                            $('#number_of_visitors').val(data.visitors);
                            $('#result_of_visitors').val(data.result_visitors);
                            calculateTotal(); // Update total after fetching data
                        },
                        error: function() {
                            alert('Error fetching salary details.');
                        }
                    });
                } else {
                    $('#salary').val('');
                    $('#commission').val('');
                    $('#number_of_visitors').val('');
                    $('#result_of_visitors').val('');
                    calculateTotal();
                }
            });

            $('#salary_of_visitors, #transport_amount, #Discount').on('input', function() {
                calculateTotal();
            });
        });
    </script>
@endpush
