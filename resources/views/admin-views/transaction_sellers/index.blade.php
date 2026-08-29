@extends('layouts.admin.app')

@section('title', \App\CPU\translate('seller_list'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/custom.css') }}">
@endpush

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="row align-items-center mb-3">
        <div class="col-sm mb-2 mb-sm-0">
            <h1 class="page-header-title d-flex align-items-center g-2px text-capitalize">
                <i class="tio-filter-list me-2"></i>
                {{ \App\CPU\translate('قائمة التحويلات من المناديب') }}
                <span class="badge badge-soft-dark ms-2">{{ $transactions->total() }}</span>
            </h1>
        </div>
    </div>
    <!-- End Page Header -->

    <div class="row gx-2 gx-lg-3">
        <div class="col-12 mb-3">
            <div class="card">
                <!-- Header / Filters -->
                <div class="card-header">
                    <form action="{{ url()->current() }}" method="GET" class="row g-2">
                        <div class="col-12 col-md-3">
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="tio-search"></i></span>
                                <input
                                    type="search"
                                    name="search"
                                    class="form-control"
                                    placeholder="{{ \App\CPU\translate('بحث بالاسم او الايميل') }}"
                                    value="{{ request('search') }}"
                                >
                            </div>
                        </div>

                        <div class="col-12 col-md-3">
                            <select name="seller_id" class="form-select">
                                <option value="">{{ \App\CPU\translate('اختار البائع') }}</option>
                                @foreach($sellers as $seller)
                                    <option value="{{ $seller->id }}" {{ (string)request('seller_id') === (string)$seller->id ? 'selected' : '' }}>
                                        {{ $seller->f_name }} {{ $seller->l_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-6 col-md-2">
                            <input
                                type="date"
                                name="start_date"
                                class="form-control"
                                value="{{ request('start_date') }}"
                                placeholder="{{ \App\CPU\translate('من تاريخ') }}"
                            >
                        </div>

                        <div class="col-6 col-md-2">
                            <input
                                type="date"
                                name="end_date"
                                class="form-control"
                                value="{{ request('end_date') }}"
                                placeholder="{{ \App\CPU\translate('إلى تاريخ') }}"
                            >
                        </div>

                        <div class="col-12 col-md-2 d-flex gap-2">
                            <button type="submit" class="btn btn-primary w-100">
                                {{ \App\CPU\translate('بحث') }}
                            </button>
                            <a href="{{ url()->current() }}" class="btn btn-outline-secondary" title="Reset">
                                {{ \App\CPU\translate('إعادة تعيين') }}
                            </a>
                        </div>
                    </form>
                </div>
                <!-- End Header / Filters -->

                <!-- Table -->
                <div class="table-responsive">
                    <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                        <thead class="thead-light">
                            <tr>
                                <th>#</th>
                                <th>{{ \App\CPU\translate('تاريخ التحويل') }}</th>
                                <th>{{ \App\CPU\translate('الاسم') }}</th>
                                <th>{{ \App\CPU\translate('الايميل') }}</th>
                                <th>{{ \App\CPU\translate('المديونية') }}</th>
                                <th>{{ \App\CPU\translate('الحساب') }}</th>
                                <th>{{ \App\CPU\translate('الملاحظة') }}</th>
                                <th>{{ \App\CPU\translate('المبلغ') }}</th>
                                <th>{{ \App\CPU\translate('الصورة') }}</th>
                                <th>{{ \App\CPU\translate('action') }}</th>
                            </tr>
                        </thead>

                        <tbody>
                        @forelse($transactions as $key => $transaction)
                            <tr>
                                <td>{{ $transactions->firstItem() + $key }}</td>

                                <td>{{ optional($transaction->created_at)->format('Y-m-d H:i') ?? '' }}</td>
                                <td>{{ $transaction->sellers->f_name ?? '' }}</td>
                                <td>{{ $transaction->sellers->email ?? '' }}</td>
                                <td>{{ $transaction->sellers->credit ?? '0' }}</td>
                                <td>{{ $transaction->accounts->account ?? 'N/A' }}</td>

                                <td>{{ $transaction->note }}</td>
                                <td>{{ $transaction->amount }}</td>

                                <td>
                                    @php
                                        $imgPath = $transaction->img ? asset('storage/' . $transaction->img) : null;
                                    @endphp
                                    @if($imgPath)
                                        <img
                                            src="{{ $imgPath }}"
                                            alt="Transaction Image"
                                            width="100" height="100"
                                            style="cursor: pointer"
                                            data-bs-toggle="modal"
                                            data-bs-target="#imageModal{{ $transaction->id }}"
                                        >
                                        <!-- Modal -->
                                        <div class="modal fade" id="imageModal{{ $transaction->id }}" tabindex="-1"
                                             aria-labelledby="imageModalLabel{{ $transaction->id }}" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-body text-center p-0">
                                                        <img src="{{ $imgPath }}" alt="Full Image"
                                                             class="img-fluid rounded"
                                                             style="max-height: 90vh;">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>

                                <td>
                                    @if((int)$transaction->active === 0)
                                        <form action="{{ route('admin.TransactionSeller.status', $transaction->id) }}"
                                              method="POST" class="d-inline">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="active" value="1">
                                            <button type="submit" class="btn btn-success btn-sm">
                                                {{ \App\CPU\translate('موافقة على التحويل') }}
                                            </button>
                                        </form>

                                        <form action="{{ route('admin.TransactionSeller.status', $transaction->id) }}"
                                              method="POST" class="d-inline ms-1">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="active" value="2">
                                            <button type="submit" class="btn btn-danger btn-sm">
                                                {{ \App\CPU\translate('رفض التحويل') }}
                                            </button>
                                        </form>
                                    @elseif((int)$transaction->active === 1)
                                        <span class="text-success">{{ \App\CPU\translate('تمت الموافقة على التحويل') }}</span>
                                    @elseif((int)$transaction->active === 2)
                                        <span class="text-danger">{{ \App\CPU\translate('تم رفض التحويل') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10">
                                    <div class="text-center p-4">
                                        <img class="mb-3 w-one-cl"
                                             src="{{ asset('public/assets/admin/svg/illustrations/sorry.svg') }}"
                                             alt="{{ \App\CPU\translate('Image Description') }}">
                                        <p class="mb-0">{{ \App\CPU\translate('لاتوجد تجويلات لعرضها') }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <!-- End Table -->

                <!-- Pagination -->
                @if($transactions->hasPages())
                    <div class="card-footer pb-0">
                        {!! $transactions->appends(request()->query())->onEachSide(1)->links() !!}
                    </div>
                @endif
                <!-- End Pagination -->
            </div>
        </div>
    </div>
</div>
@endsection

{{-- لو التخطيط (layout) لا يحتوي Bootstrap بالفعل، أبقي على هذه الروابط؛
    إن كان يحتوي، يمكنك حذف الأسطر التالية لتفادي التكرار. --}}
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

@push('script_2')
    <script src="{{ asset('public/assets/admin/js/global.js') }}"></script>
@endpush
