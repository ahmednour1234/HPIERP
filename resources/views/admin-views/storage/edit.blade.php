@extends('layouts.admin.app')

@section('title', \App\CPU\translate('تعديل المخزن'))

@section('content')
<div class="content container-fluid" dir="rtl">

    <div class="page-header">
        <h1 class="page-header-title">{{ \App\CPU\translate('تعديل المخزن') }}</h1>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.storage.update', [$storage->id]) }}" method="post">
                @csrf
                {{-- المسار مسجَّل PUT --}}
                @method('PUT')

                <div class="form-group">
                    <label for="name">{{ \App\CPU\translate('اسم المخزن') }}</label>
                    <input type="text" name="name" id="name" class="form-control"
                           value="{{ old('name', $storage->name) }}" required>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-primary px-4">
                        {{ \App\CPU\translate('حفظ') }}
                    </button>
                    <a href="{{ route('admin.storage.list') }}" class="btn btn-light border px-4">
                        {{ \App\CPU\translate('رجوع') }}
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
