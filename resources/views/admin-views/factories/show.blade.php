@extends('layouts.admin.app')

@section('title', \App\CPU\translate('تفاصيل المصنع'))

@push('css_or_js')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<style>
    .card-header {
        background-color: #bee0ec;
        font-weight: bold;
        font-size: 1.2rem;
    }
    .list-group-item strong {
        display: inline-block;
        width: 120px;
    }
    #map {
        width: 100%;
        height: 400px;
        border-radius: 10px;
        border: 2px solid #bee0ec;
        margin-top: 20px;
    }
</style>
@endpush
@section('content')
<div class="container">
    <div class="card shadow">
        <div class="card-header text-center">
            🏭 تفاصيل المصنع
        </div>
        <div class="card-body">
            {{-- Tabs --}}
            <ul class="nav nav-tabs mb-4" id="factoryTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" type="button" role="tab">تفاصيل المصنع</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="ledger-tab" data-bs-toggle="tab" data-bs-target="#ledger" type="button" role="tab">كشف الحساب</button>
                </li>
                <li class="nav-item" role="presentation">
    <button class="nav-link" id="payment-tab" data-bs-toggle="tab" data-bs-target="#payment" type="button" role="tab">💸 دفع نقدية</button>
</li>
<li class="nav-item" role="presentation">
    <button class="nav-link" id="receipt-tab" data-bs-toggle="tab" data-bs-target="#receipt" type="button" role="tab">💰 استلام نقدية</button>
</li>

            </ul>

            <div class="tab-content" id="factoryTabsContent">
                {{-- تفاصيل المصنع --}}
                <div class="tab-pane fade show active" id="details" role="tabpanel">
                    <ul class="list-group mb-4">
                        <li class="list-group-item"><strong>الاسم:</strong> {{ $factory->name }}</li>
                        <li class="list-group-item"><strong>الهاتف:</strong> {{ $factory->phone }}</li>
                        <li class="list-group-item"><strong>البريد:</strong> {{ $factory->email }}</li>
                        <li class="list-group-item"><strong>العنوان:</strong> {{ $factory->address }}</li>
                        <li class="list-group-item"><strong>الموقع:</strong> {{ $factory->lang }} - {{ $factory->late }}</li>
                        <li class="list-group-item">
                            <strong>الحالة:</strong>
                            <span class="badge {{ $factory->active ? 'bg-success' : 'bg-danger' }}">
                                {{ $factory->active ? 'مفعل' : 'غير مفعل' }}
                            </span>
                        </li>
                        <li class="list-group-item"><strong>الرصيد المدين:</strong> {{ number_format($factory->maden, 2) }} جنيه</li>
                        <li class="list-group-item"><strong>الرصيد الدائن:</strong> {{ number_format($factory->daen, 2) }} جنيه</li>
                    </ul>

                    <h5 class="text-primary">موقع المصنع على الخريطة:</h5>
                    <div id="map"></div>
                </div>

                {{-- كشف الحساب --}}
                <div class="tab-pane fade" id="ledger" role="tabpanel">
                    <form method="GET" class="row mb-4">
                        <div class="col-md-4">
                            <label>من تاريخ</label>
                            <input type="date" name="from" class="form-control" value="{{ request('from') }}">
                        </div>
                        <div class="col-md-4">
                            <label>إلى تاريخ</label>
                            <input type="date" name="to" class="form-control" value="{{ request('to') }}">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button class="btn btn-primary w-100">تصفية</button>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-bordered text-center">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>رقم أمر الإنتاج</th>
                                    <th>نوع العملية</th>
                                    <th>الوصف</th>
                                    <th>التاريخ</th>
                                    <th>المنفذ</th>
                                    <th>مدين</th>
                                    <th>دائن</th>
                                    <th>الرصيد</th>
                                </tr>
                            </thead>
                            @php
    $runningBalance = 0;
    $totalCredit = 0;
    $totalDebit = 0;
@endphp

<tbody>
@foreach($transections as $index => $tran)
    @php
        $credit = $tran->credit ?? 0;
        $debit = $tran->debit ?? 0;
        $runningBalance += ($credit - $debit);
        $totalCredit += $credit;
        $totalDebit += $debit;
    @endphp
    <tr>
        <td>{{ $index + 1 }}</td>
<td>
    @if($tran->production_order_id)
        <a href="{{ route('admin.production_orders.show', $tran->production_order_id) }}">
            #{{ $tran->production_order_id }}
        </a>
    @else
        -
    @endif
</td>
        <td>
    @switch($tran->tran_type)
        @case(999)
            <span class="badge bg-info">إنهاء أمر تصنيع</span>
            @break

        @case(26)
            <span class="badge bg-danger">💸 دفع نقدية</span>
            @break

        @case(13)
            <span class="badge bg-success">💰 استلام نقدية</span>
            @break

        @default
            <span class="badge bg-secondary">نوع غير معروف</span>
    @endswitch
</td>

        <td>{{ $tran->description }}</td>
        <td>{{ $tran->created_at->format('Y-m-d') }}</td>
        <td>{{ $tran->seller->f_name ?? '-' }}</td>
        <td>{{ number_format($credit, 2) }}</td>
        <td>{{ number_format($debit, 2) }}</td>
        <td>{{ number_format($runningBalance, 2) }}</td>
    </tr>
@endforeach
</tbody>

<tfoot class="table-light">
    <tr>
        <th colspan="6">الإجمالي</th>
        <th>{{ number_format($totalCredit, 2) }}</th>
        <th>{{ number_format($totalDebit, 2) }}</th>
        <th>{{ number_format($totalCredit - $totalDebit, 2) }}</th>
    </tr>
</tfoot>

                        </table>

                        {{ $transections->links() }}
                    </div>
                </div>
                <div class="tab-pane fade" id="payment" role="tabpanel">
    <form action="{{ route('admin.factories.update_credit') }}" method="POST" enctype="multipart/form-data" class="row g-3">
        @csrf
        <input type="hidden" name="factory_id" value="{{ $factory->id }}">

        <div class="col-md-4">
            <label>الحساب</label>
            <select name="account_id" class="form-control" required>
                <option value="">اختر حساب</option>
                @foreach($accounts as $account)
                    <option value="{{ $account->id }}">{{ $account->account }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-4">
            <label>القيمة</label>
            <input type="number" name="amount" class="form-control" step="0.01" required>
        </div>

        <div class="col-md-4">
            <label>تاريخ العملية</label>
            <input type="date" name="date" class="form-control" required>
        </div>

        <div class="col-md-6">
            <label>الوصف</label>
            <textarea name="description" class="form-control" rows="2"></textarea>
        </div>

        <div class="col-md-6">
            <label>صورة الإيصال</label>
            <input type="file" name="img" class="form-control" required>
        </div>

        <div class="col-12">
            <button type="submit" class="btn btn-success w-100">💸 حفظ الدفع النقدي</button>
        </div>
    </form>
</div>
<div class="tab-pane fade" id="receipt" role="tabpanel">
    <form action="{{ route('admin.factories.update_debit') }}" method="POST" enctype="multipart/form-data" class="row g-3">
        @csrf
        <input type="hidden" name="factory_id" value="{{ $factory->id }}">

        <div class="col-md-4">
            <label>الحساب</label>
            <select name="account_id" class="form-control" required>
                <option value="">اختر حساب</option>
                @foreach($accounts as $account)
                    <option value="{{ $account->id }}">{{ $account->account }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-4">
            <label>القيمة</label>
            <input type="number" name="amount" class="form-control" step="0.01" required>
        </div>

        <div class="col-md-4">
            <label>تاريخ العملية</label>
            <input type="date" name="date" class="form-control" required>
        </div>

        <div class="col-md-6">
            <label>الوصف</label>
            <textarea name="description" class="form-control" rows="2"></textarea>
        </div>

        <div class="col-md-6">
            <label>صورة الإيصال</label>
            <input type="file" name="img" class="form-control" required>
        </div>

        <div class="col-12">
            <button type="submit" class="btn btn-primary w-100">💰 حفظ الاستلام النقدي</button>
        </div>
    </form>
</div>

            </div>

            <a href="{{ route('admin.factories.index') }}" class="btn btn-secondary mt-4">⬅️ العودة للقائمة</a>
        </div>
    </div>
</div>

{{-- Google Maps --}}
<script async defer
    src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAQgTQ30_TriFBdJPKKOK4zZQ8rfHCUk6c&callback=initMap">
</script>

<script>
    function initMap() {
        const lat = parseFloat({{ $factory->late ?? 24.7136 }});
        const lng = parseFloat({{ $factory->lang ?? 46.6753 }});
        const position = { lat: lat, lng: lng };

        const map = new google.maps.Map(document.getElementById("map"), {
            center: position,
            zoom: 14,
        });

        new google.maps.Marker({
            position: position,
            map: map,
            title: "موقع المصنع"
        });
    }
</script>
@endsection
