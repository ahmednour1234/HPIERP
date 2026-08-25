@extends('layouts.admin.app')

@section('title', \App\CPU\translate('الزيارات'))

@push('css_or_js')
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <link rel="stylesheet" href="{{ asset('public/assets/admin/css/custom.css') }}"/>
  {{-- Select2 --}}
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/css/select2.min.css" rel="stylesheet"/>
@endpush

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

  <!-- ===== بطاقة الفلترة ===== -->
<div class="card mb-4 shadow-sm rounded-3">
  <div class="card-header bg-primary text-white">
    <h5 class="mb-0">فلترة الزوار</h5>
  </div>

  <form method="GET" action="{{ route('admin.visitor.indexresult') }}">
    <div class="card-body">
      <div class="row g-2">
        <!-- العميل -->
        <div class="col-md-4">
          <label for="customer_id" class="form-label">العميل</label>
          <select name="customer_id" id="customer_id" class="form-control select2" >
            <option value="">كل العملاء</option>
            @foreach($customers as $c)
              <option value="{{ $c->id }}" {{ request('customer_id') == $c->id ? 'selected' : '' }}>
                {{ $c->name }}
              </option>
            @endforeach
          </select>
        </div>

        <!-- المندوب -->
        <div class="col-md-4">
          <label for="seller_id" class="form-label">المندوب</label>
          <select name="seller_id" id="seller_id" class="form-select select2">
            <option value="">كل المندوبين</option>
            @foreach($sellers as $s)
              <option value="{{ $s->id }}" {{ request('seller_id') == $s->id ? 'selected' : '' }}>
                {{ $s->f_name . ' ' . $s->l_name }}
              </option>
            @endforeach
          </select>
        </div>
</div>
      <div class="row g-4">

        <!-- من تاريخ -->
        <div class="col-md-4">
          <label for="date_from" class="form-label">من تاريخ</label>
          <input type="date" name="date_from" id="date_from" class="form-control" value="{{ request('date_from') }}">
        </div>

        <!-- إلى تاريخ -->
        <div class="col-md-4">
          <label for="date_to" class="form-label">إلى تاريخ</label>
          <input type="date" name="date_to" id="date_to" class="form-control" value="{{ request('date_to') }}">
        </div>
      </div>
    </div>

    <div class="card-footer bg-light d-flex justify-content-end gap-2">
      <button type="submit" class="btn btn-primary px-4">بحث</button>
      <a href="{{ route('admin.visitor.indexresult') }}" class="btn btn-outline-secondary">إعادة تعيين</a>
             <button type="button" class="btn btn-outline-secondary px-4 py-2 shadow-sm" onclick="printTable()">
                    <i class="tio-print me-1"></i> {{ \App\CPU\translate('طباعة') }}
                </button>
    </div>
    
    </div>
  </form>
</div>
<div id="product-table">
  <!-- ===== الإجماليات ===== -->
  <div class="row mb-3" >
    @isset($customerTotal)
      <div class="col-auto">
        <span class="badge bg-info fs-6">إجمالي زيارات العميل: {{ $customerTotal }}</span>
      </div>
    @endisset
    @isset($sellerTotal)
      <div class="col-auto">
        <span class="badge bg-success fs-6">إجمالي زيارات المندوب: {{ $sellerTotal }}</span>
      </div>
    @endisset
  </div>

  <!-- ===== جدول النتائج ===== -->
  <div class="card">
    <div class="table-responsive">
      <table class="table table-striped align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>العميل</th>
            <th>المندوب</th>
            <th>الملاحظة</th>
            <th class="none">الموقع (خط/دائرة)</th>
            <th>تاريخ الإنشاء</th>
          </tr>
        </thead>
        <tbody>
          @forelse($visitors as $index => $v)
            <tr>
              <td>{{ $index + $visitors->firstItem() }}</td>
           <td>{{ $v->customer->name ?? '-' }}</td>
<td>{{ trim(optional($v->seller)->f_name . ' ' . optional($v->seller)->l_name) ?: '-' }}</td>

              <td>{{ $v->note??'' }}</td>
             <td class="none">
  @if($v->lat && $v->lang)
    <a  href="https://www.google.com/maps?q={{ $v->lat }},{{ $v->lang }}"
        target="_blank"
        class="btn btn-sm btn-outline-primary">
      عرض الخريطة
    </a>
  @else
    <span class="text-muted">لا توجد إحداثيات</span>
  @endif
</td>

              <td>{{ $v->created_at->format('d M Y H:i') }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center py-4">لا توجد سجلات</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
</div>
    <div class="card-footer none">
      {{ $visitors->withQueryString()->links() }}
    </div>
  </div>

</div>



@endsection
<script>
    function printTable() {
        const tableContent = document.getElementById('product-table').innerHTML;


        const printWindow = window.open('', '_blank', 'width=800,height=600');
        printWindow.document.write(`
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>{{ \App\CPU\translate('تقرير الزيارات المنفذة') }}</title>
                <style>
                
                    body {
                        font-family: Arial, sans-serif;
                        margin: 20px;
                    }
                     body {
                            font-family: 'Cairo', Arial, sans-serif;
                            margin: 0;
                            background-color: #f9f9f9;
                            color: #333;
                            direction: rtl;
                        }

                        h1 {
                            text-align: center;
                            color: #003366;
                            font-weight: bold;
                            font-size: 28px;
                            margin-bottom: 20px;
                        }

                        .header-section {
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                            border-bottom: 2px solid #003366;
                            padding: 10px 0;
                            margin-bottom: 30px;
                            flex-wrap: wrap;
                        }

                        .header-section .left,
                        .header-section .right,
                        .header-section .logo {
                            width: 32%;
                            text-align: center;
                        }

                        .header-section p {
                            margin: 5px 0;
                            line-height: 1.6;
                            font-size: 16px;
                        }

                        .logo img {
                            max-width: 150px;
                            height: auto;
                        }
                    table {
                        width: 100%;
                        border-collapse: collapse;
                        margin-bottom: 20px;
                    }
                    table th, table td {
                        border: 1px solid #ddd;
                        padding: 8px;
                        text-align: left;
                    }
                    table th {
                        background-color: #f2f2f2;
                        font-weight: bold;
                    }
                    .row {
                        display: flex;
                        flex-wrap: wrap;
                        margin-bottom: 10px;
                    }
                    .col-md-3 {
                        flex: 0 0 25%;
                        max-width: 25%;
                        padding: 5px;
                        box-sizing: border-box;
                    }
                    .none{
                        display:none;
                    }
                    strong {
                        font-weight: bold;
                    }
                    input[type="search"][aria-controls="DataTables_Table_0"] {
    display: none;
}
label:has(input[type="search"][aria-controls="DataTables_Table_0"]) {
    display: none;
}
label:has(input[type="search"][aria-controls="DataTables_Table_1"]) {
    display: none;
}
label:has(input[type="search"][aria-controls="DataTables_Table_2"]) {
    display: none;
}
label:has(input[type="search"][aria-controls="DataTables_Table_3"]) {
    display: none;
}
label:has(input[type="search"][aria-controls="DataTables_Table_4"]) {
    display: none;
}
label:has(input[type="search"][aria-controls="DataTables_Table_5"]) {
    display: none;
}
#DataTables_Table_0_info{
        display: none;

}
#DataTables_Table_1_info{
            display: none;

}
#DataTables_Table_2_info{
            display: none;

}
#DataTables_Table_3_info{
            display: none;

}
#DataTables_Table_4_info{
            display: none;

}
#DataTables_Table_5_info{
            display: none;

}
#links{
    display: block;
}
                </style>
            </head>
            <body>
            <div class="header-section">
                        <div class="left">
                            <p><strong>رقم السجل التجاري:</strong> {{ \App\Models\BusinessSetting::where(["key" => "vat_reg_no"])->first()->value??'' }}</p>
                            <p><strong>الرقم الضريبي:</strong> {{ \App\Models\BusinessSetting::where(["key" => "number_tax"])->first()->value ??''}}</p>
                            <p><strong>البريد الإلكتروني:</strong> {{ \App\Models\BusinessSetting::where(["key" => "shop_email"])->first()->value }}</p>
                        </div>
                        <div class="logo">
                            <img src="{{ asset('storage/app/public/shop/' . \App\Models\BusinessSetting::where(['key' => 'shop_logo'])->first()->value) }}" alt="شعار المتجر">
                        </div>
                        <div class="right">
                            <p><strong>اسم المؤسسة:</strong> {{ \App\Models\BusinessSetting::where(["key" => "shop_name"])->first()->value }}</p>
                            <p><strong>العنوان:</strong> {{ \App\Models\BusinessSetting::where(["key" => "shop_address"])->first()->value }}</p>
                            <p><strong>رقم الجوال:</strong> {{ \App\Models\BusinessSetting::where(["key" => "shop_phone"])->first()->value }}</p>
                        </div>
                    </div>
                    
                <h2>{{ \App\CPU\translate('تقرير   الزيارات المنفذة') }}</h2>
                
                ${tableContent}
                <hr>
                <script>
                    window.onload = function() {
                        window.print();
                        window.close();
                    };
                <\/script>
            </body>
            </html>
        `);

        printWindow.document.close();
    }
</script>

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

