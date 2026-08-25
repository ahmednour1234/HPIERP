@extends('layouts.admin.app')  
@section('title', \App\CPU\translate('تقييم الموظف'))  

@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/select2.min.css') }}">
@endpush

@section('content')
    <div class="container-fluid">
        <h3 class="mt-4">{{ \App\CPU\translate('تطوير الموظف') }}</h3>
        <div class="row">
            <div class="col-md-12">
                <form id="salary-form" method="POST" action="{{ route('admin.developsellers.update', $developSeller->id) }}">
                    @csrf
                    @method('PUT') <!-- Add PUT method for updating -->

                    <div class="form-group">
                        <label for="seller_id">{{ \App\CPU\translate('اختار موظف') }}</label>
                        <select id="seller_id" name="seller_id" class="form-control" required readonly>
                                <option value="{{ $developSeller->sellers->id }}" {{ $developSeller->sellers->id == $developSeller->seller_id ? 'selected' : '' }}>
                                    {{ $developSeller->sellers->email }}
                                </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="note">{{ \App\CPU\translate('ملاحظات') }}</label>
                        <input type="text" id="note" name="note" class="form-control" value="{{ $developSeller->note }}" required>
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
