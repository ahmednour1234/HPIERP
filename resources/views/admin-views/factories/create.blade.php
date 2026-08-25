@extends('layouts.admin.app')

@section('title',\App\CPU\translate('add_new_account'))

@push('css_or_js')

@endpush

@section('content')
    <div class="container">
        <h3>إضافة مصنع جديد</h3>
        <form action="{{ route('admin.factories.store') }}" method="POST">
            @csrf
            @include('admin-views.factories.form')
            <button type="submit" class="btn btn-success">حفظ</button>
        </form>
    </div>
@endsection
