@extends('layouts.admin.app')

@section('title', \App\CPU\translate('customer_list'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('public/assets/admin') }}/css/custom.css"/>
@endpush

@section('content')
<div class="container-fluid">

    {{-- تنبيه أمان --}}
    <div class="alert alert-warning d-flex align-items-center" role="alert">
        <i class="tio-warning-outlined flex-shrink-0 me-2"></i>
        <div>
            <strong>تنبيه!</strong> سيتم تضمين عمود <code>ID</code> في الملف. 
            أي تغيير في هذا العمود قد يؤدي إلى تحديث سجلّات خاطئة أو فقدان البيانات. 
            الرجاء عدم تعديل عمود <code>ID</code> تحت أي ظرف.
        </div>
    </div>

    {{-- رابط تحميل نموذج الجدول --}}
    <div class="card mb-4">
        <div class="card-body">
            <p>يمكنك تحميل نموذج الجدول بصيغة Excel لتعبئة البيانات بالترتيب الصحيح:</p>
            <a href="{{ route('admin.customer.updateexport') }}" class="btn btn-primary">
                <i class="tio-download-cloud-outlined"></i>
                تحميل نموذج Excel للتحديث
            </a>
        </div>
    </div>

    {{-- عرض مثال على هيكل الجدول --}}
    <div class="card mb-4">
        <div class="card-body">
            <p class="mb-2"><strong>مثال تنسيق الجدول المصدّر:</strong></p>
            <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Mobile</th>
                            <th>Address</th>
                            <th>Email</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td>محمد أحمد</td>
                            <td>01012345678</td>
                            <td>القاهرة، مصر</td>
                            <td>m.ahmed@example.com</td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td>سارة حسين</td>
                            <td>01234567890</td>
                            <td>الإسكندرية، مصر</td>
                            <td>s.hussein@example.com</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- نموذج رفع الملف --}}
    <div class="card">
        <div class="card-body">
            <h5 class="card-title mb-4">استيراد وتحديث بيانات العملاء</h5>
            <form action="{{ route('admin.customer.import') }}"
                  method="POST" 
                  enctype="multipart/form-data">
                @csrf
                <div class="input-group">
                    <input type="file"
                           name="excel_file"
                           accept=".xlsx,.xls"
                           class="form-control @error('excel_file') is-invalid @enderror"
                           required>
                    <button class="btn btn-success">
                        <i class="tio-file_upload"></i>
                        استيراد وتحديث
                    </button>
                </div>
                @error('excel_file')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </form>
        </div>
    </div>

</div>
@endsection
