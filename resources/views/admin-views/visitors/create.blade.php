@extends('layouts.admin.app')

@section('title', \App\CPU\translate('add_new_visitors'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/custom.css') }}"/>
@endpush

@section('content')
<div class="content container-fluid">
    <!-- Header -->
    <div class="row align-items-center mb-3">
        <div class="col-sm mb-2 mb-sm-0">
            <h1 class="page-header-title d-flex align-items-center g-2px text-capitalize">
                <i class="tio-add-circle-outlined"></i> {{ \App\CPU\translate('انشاء زيارات الشهر') }}
            </h1>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form action="{{ route('admin.visitor.store') }}"
                  method="post"
                  id="product_form"
                  enctype="multipart/form-data">
                @csrf

                <div class="row">
                    {{-- Seller --}}
                    <div class="col-sm-4 mb-3">
                        <label class="input-label">
                            {{ \App\CPU\translate('ايميل المندوب') }}
                            <span class="text-danger">*</span>
                        </label>
                        <select id="seller"
                                name="seller_id"
                                class="form-control js-select2-custom"
                                required
                                onchange="fetchRegions()">
                            <option value="" hidden>-- {{ \App\CPU\translate('select') }} --</option>
                            @foreach($sellers as $s)
                                <option value="{{ $s->id }}">{{ $s->email }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Region --}}
                    <div class="col-sm-4 mb-3">
                        <label class="input-label">
                            {{ \App\CPU\translate('المنطقة') }}
                            <span class="text-danger">*</span>
                        </label>
                        <select id="region"
                                name="region_id"
                                class="form-control js-select2-custom"
                                disabled>
                            <option value="" hidden>-- {{ \App\CPU\translate('select') }} --</option>
                        </select>
                    </div>

                    {{-- Customer --}}
                    <div class="col-sm-4 mb-3">
                        <label class="input-label">
                            {{ \App\CPU\translate('العملاء') }}
                            <span class="text-danger">*</span>
                        </label>
                        <select id="customer"
                                class="form-control js-select2-custom"
                                disabled>
                            <option value="" hidden>-- {{ \App\CPU\translate('select') }} --</option>
                        </select>
                    </div>
                </div>

                <div class="table-responsive mb-3">
                    <table class="table text-center">
                        <thead>
                            <tr>
                                <th>العميل</th>
                                <th>العنوان</th>
                                <th>رقم الهاتف</th>
                                <th>التاريخ</th>
                                <th>ملاحظة</th>
                                <th>حذف</th>
                            </tr>
                        </thead>
                        <tbody id="data"></tbody>
                    </table>
                </div>

                <button type="submit" class="btn btn-primary">
                    {{ \App\CPU\translate('حفظ') }}
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('script_2')
<script>
"use strict";

let simple = [];

// 1) Seller → Regions
function fetchRegions() {
    const sellerId = $('#seller').val();
    $('#region, #customer')
        .prop('disabled', true)
        .empty()
        .append(`<option value="" hidden>--{{\App\CPU\translate('select')}}--</option>`);
    simple = [];

    $.get(`{{ route('admin.visitor.create') }}?seller_id=${sellerId}`)
      .done(data => {
        let opts = [`<option value="" hidden>--{{\App\CPU\translate('select')}}--</option>`];
        (data.option || []).forEach(r => {
            opts.push(`<option value="${r.id}">${r.name}</option>`);
        });
        $('#region').empty().append(opts).prop('disabled', false);
      })
      .fail(err => console.error('Regions error', err));
}

// 2) Region → Customers
$('#region').on('change', function() {
    const regionId = $(this).val();
    $('#customer')
        .prop('disabled', true)
        .empty()
        .append(`<option value="" hidden>--{{\App\CPU\translate('select')}}--</option>`);
    simple = [];

    $.get(`{{ route('admin.visitor.create') }}?region_id=${regionId}`)
      .done(data => {
        let opts = [`<option value="" hidden>--{{\App\CPU\translate('select')}}--</option>`];
        (data.option || []).forEach(c => {
            simple.push({
                id:      c.id,
                name:    c.name,
                mobile:  c.mobile  || 'N/A',
                address: c.address || 'N/A'
            });
            opts.push(`<option value="${c.id}">${c.name}</option>`);
        });
        $('#customer').empty().append(opts).prop('disabled', false);
      })
      .fail(err => console.error('Customers error', err));
});

// 3) Customer → Table Row
$('#customer').on('change', function() {
    const id = $(this).val();
    const picked = simple.find(x => x.id == id);
    if (!picked) return;

    const row = `
        <tr>
            <td>
                <label>${picked.name}</label>
                <input type="hidden" name="customer_id[]" value="${picked.id}">
            </td>
            <td><label>${picked.address}</label></td>
            <td><label>${picked.mobile}</label></td>
            <td><input type="date" name="date[]" class="form-control date-input" required></td>
            <td><input type="text" name="note[]" class="form-control" required></td>
            <td>
                <button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)">حذف</button>
            </td>
        </tr>`;
    $('#data').append(row);
    applyMaxDate();
});

// 4) Remove Row
function removeRow(btn) {
    $(btn).closest('tr').remove();
}

// 5) Date constraints (same logic as before)
function applyMaxDate() {
    const today = new Date(),
          d     = today.getDate(),
          m     = today.getMonth(),
          y     = today.getFullYear();
    let tgtY = y, tgtM = m + 1;

    if (tgtM > 11) { tgtM = 0; tgtY++; }

    if (d >= 25) {
        const last = new Date(tgtY, tgtM + 1, 0).getDate(),
              mm   = String(tgtM + 1).padStart(2,'0');
        document.querySelectorAll('.date-input').forEach(i => {
            i.min = `${tgtY}-${mm}-01`;
            i.max = `${tgtY}-${mm}-${last}`;
        });
    } else {
        const last = new Date(y, m + 1, 0).getDate(),
              mm   = String(m + 1).padStart(2,'0');
        document.querySelectorAll('.date-input').forEach(i => {
            i.min = `${y}-${mm}-01`;
            i.max = `${y}-${mm}-${last}`;
        });
    }
}

$(document).ready(applyMaxDate);
</script>
@endpush
