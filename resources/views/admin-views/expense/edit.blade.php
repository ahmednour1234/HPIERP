@extends('layouts.admin.app')

@section('title', 'تعديل مصروف')

@section('content')
<div class="content container-fluid" dir="rtl">

    <div class="page-header">
        <h1 class="page-header-title">{{ \App\CPU\translate('تعديل مصروف') }}</h1>
    </div>

    <div class="card">
        <div class="card-body">

            {{-- التعديل يعكس أثر القيد القديم على الحساب ثم يطبّق الجديد،
                 فيبقى رصيد الحساب صحيحًا. --}}
            <form action="{{ route('admin.account.update-expense', [$expense->id]) }}"
                  method="post" enctype="multipart/form-data">
                @csrf

                <div class="row">
                    <div class="col-md-6 form-group">
                        <label for="account_id">{{ \App\CPU\translate('الحساب') }}</label>
                        <select name="account_id" id="account_id" class="form-control" required>
                            @foreach ($accounts as $account)
                                <option value="{{ $account->id }}"
                                    {{ (string) $expense->account_id === (string) $account->id ? 'selected' : '' }}>
                                    {{ $account->account }}
                                    ({{ $account->balance . ' ' . \App\CPU\Helpers::currency_symbol() }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6 form-group">
                        <label for="amount">{{ \App\CPU\translate('المبلغ') }}</label>
                        <input type="number" step="0.01" min="0.01" name="amount" id="amount"
                               class="form-control" value="{{ old('amount', $expense->amount) }}" required>
                    </div>

                    <div class="col-md-6 form-group">
                        <label for="date">{{ \App\CPU\translate('التاريخ') }}</label>
                        <input type="date" name="date" id="date" class="form-control"
                               value="{{ old('date', $expense->date) }}">
                    </div>

                    <div class="col-md-6 form-group">
                        <label for="img">{{ \App\CPU\translate('صورة') }}</label>
                        <input type="file" name="img" id="img" class="form-control">
                        @if($expense->img)
                            <small class="text-muted">
                                {{ \App\CPU\translate('اترك الحقل فارغًا للإبقاء على الصورة الحالية') }}
                            </small>
                        @endif
                    </div>

                    <div class="col-12 form-group">
                        <label for="description">{{ \App\CPU\translate('الوصف') }}</label>
                        <input type="text" name="description" id="description" class="form-control"
                               value="{{ old('description', $expense->description) }}" required>
                    </div>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-primary px-4">
                        {{ \App\CPU\translate('حفظ التعديل') }}
                    </button>
                    <a href="{{ route('admin.account.add-expense') }}" class="btn btn-light border px-4">
                        {{ \App\CPU\translate('رجوع') }}
                    </a>
                </div>
            </form>

        </div>
    </div>
</div>
@endsection
