@extends('layouts.admin.app')

@section('title',\App\CPU\translate('add_new_account'))

@push('css_or_js')

@endpush

@section('content')
    <div class="container">
        <h3>تعديل مصنع: {{ $factory->name }}</h3>
        <form action="{{ route('admin.factories.update', $factory->id) }}" method="POST">
            @csrf
            @method('PUT')
            @include('admin-views.factories.form', ['factory' => $factory])
            <button type="submit" class="btn btn-primary">تحديث</button>
        </form>
    </div>
@endsection
