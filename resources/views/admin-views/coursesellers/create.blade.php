@extends('layouts.admin.app')  
@section('title', \App\CPU\translate('كورسات الموظف'))  

@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/select2.min.css') }}">
@endpush

@section('content')
    <div class="container-fluid">
        <h3 class="mt-4">{{ \App\CPU\translate('كورسات الموظف') }}</h3>
        <div class="row">
            <div class="col-md-12">
                <form id="salary-form" method="POST" action="{{ route('admin.coursesellers.store') }}">
                    @csrf
                    <div class="form-group">
                        <label for="seller_id">{{ \App\CPU\translate('كورسات موظف') }}</label>
                        <select id="seller_id" name="seller_id" class="form-control" required>
                            <option value="">{{ \App\CPU\translate('كورسات موظف') }}</option>
                            @foreach($sellers as $seller)
                                <option value="{{ $seller->id }}">{{ $seller->email }}</option>
                            @endforeach
                        </select>
                    </div>

                     <div class="form-group">
                        <label for="name">{{ \App\CPU\translate('اسم الكورس') }}</label>
<input type="text" id="name" name="name" class="form-control" required placeholder="Enter course name" >
                    </div>
                     <div class="form-group">
                        <label for="link">{{ \App\CPU\translate('لينك') }}</label>
<input type="url" id="link" name="link" class="form-control" required placeholder="Enter course link" pattern="https?://.*">
                    </div>




                    <button type="submit" class="btn btn-primary">{{ \App\CPU\translate('حفظ') }}</button>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
    <script src="{{ asset('public/assets/admin/js/jquery.min.js') }}"></script>

@endpush
