@extends('layouts.admin.app')

@section('title', \App\CPU\translate('add_new_stock'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/custom.css') }}"/>
    <style>
        .stock-create-page {
            direction: rtl;
            background: linear-gradient(135deg, rgba(17, 36, 90, .05), rgba(20, 184, 166, .08)), #f4f8fb;
            min-height: calc(100vh - 4rem);
            padding-top: 2rem;
            padding-bottom: 2.5rem;
            color: #102a43;
        }

        .stock-create-shell {
            display: flex;
            flex-direction: column;
            gap: 1.15rem;
        }

        .stock-create-hero,
        .stock-create-card {
            border: 1px solid #d9e6f2;
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 14px 32px rgba(15, 23, 42, .08);
        }

        .stock-create-hero {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1.35rem 1.5rem;
            background: linear-gradient(135deg, #111857, #0f766e);
            color: #fff;
        }

        .stock-create-title {
            display: flex;
            align-items: center;
            gap: .75rem;
            margin: 0;
            color: #fff;
            font-size: 1.65rem;
            font-weight: 900;
        }

        .stock-create-title span {
            width: 3rem;
            height: 3rem;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, .14);
        }

        .stock-create-hero p {
            margin: .4rem 3.75rem 0 0;
            color: rgba(255, 255, 255, .76);
            font-weight: 700;
        }

        .stock-create-badge {
            border-radius: 999px;
            padding: .55rem .85rem;
            background: rgba(255, 255, 255, .14);
            color: #fff;
            font-weight: 900;
            white-space: nowrap;
        }

        .stock-create-card {
            padding: 1.25rem;
        }

        .stock-form-grid {
            display: grid;
            grid-template-columns: minmax(18rem, 1fr) minmax(22rem, 1.45fr);
            gap: 1rem;
            align-items: start;
        }

        .stock-field-card {
            border: 1px solid #e0e9f3;
            border-radius: 8px;
            background: #f8fbff;
            padding: 1rem;
            min-height: 8.75rem;
        }

        .stock-field-card label {
            display: flex;
            align-items: center;
            gap: .45rem;
            color: #52677f;
            font-weight: 900;
            margin-bottom: .6rem;
        }

        .stock-field-card .form-control,
        .stock-field-card .select2-container .select2-selection {
            border-color: #d4e2ef;
            border-radius: 8px !important;
            min-height: 3.15rem;
            font-weight: 800;
        }

        .stock-products-table-wrap {
            margin-top: 1.2rem;
            border: 1px solid #e0e9f3;
            border-radius: 8px;
            overflow: hidden;
            background: #fff;
        }

        .stock-products-table {
            margin: 0;
        }

        .stock-products-table thead th {
            border: 0;
            background: #11245a;
            color: #fff;
            padding: 1rem;
            font-weight: 900;
            text-align: center;
        }

        .stock-products-table tbody td {
            border-top: 1px solid #e7eef6;
            padding: .85rem;
            vertical-align: middle;
            color: #52677f;
            font-weight: 800;
            text-align: center;
        }

        .stock-products-table tbody:empty::after {
            content: "اختار مندوب ثم اختار المنتجات لإضافة الكميات";
            display: block;
            padding: 2.25rem 1rem;
            color: #8294a8;
            text-align: center;
            font-weight: 800;
        }

        .stock-products-table input.form-control {
            max-width: 12rem;
            margin-inline: auto;
            border-radius: 8px;
            text-align: center;
            font-weight: 900;
        }

        .stock-create-actions {
            display: flex;
            justify-content: flex-start;
            padding-top: 1.1rem;
        }

        .stock-submit-btn {
            min-height: 3.15rem;
            border: 0;
            border-radius: 8px;
            padding: .75rem 1.4rem;
            background: #11245a;
            color: #fff;
            font-weight: 900;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .45rem;
            box-shadow: 0 12px 24px rgba(17, 36, 90, .18);
        }

        .stock-submit-btn:hover {
            color: #fff;
            background: #173174;
        }

        @media (max-width: 991.98px) {
            .stock-form-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 575.98px) {
            .stock-create-hero {
                flex-direction: column;
                align-items: stretch;
            }

            .stock-create-hero p {
                margin-right: 0;
            }
        }
    </style>
@endpush

@section('content')
<div class="content container-fluid stock-create-page">
    <div class="stock-create-shell">
        <section class="stock-create-hero">
            <div>
                <h1 class="stock-create-title">
                    <span><i class="tio-add-circle-outlined"></i></span>
                    إضافة مخزون جديد
                </h1>
                <p>اختار المندوب والمنتجات، ثم سجل كمية المخزون لكل منتج في جدول واحد واضح.</p>
            </div>
            <div class="stock-create-badge">
                <i class="tio-archive"></i>
                مخزون المركبات
            </div>
        </section>

        <section class="stock-create-card">
            <form action="{{ route('admin.stock.store') }}" method="post" id="product_form" enctype="multipart/form-data">
                @csrf
                <div class="stock-form-grid">
                    <div class="stock-field-card">
                        <label for="seller">
                            <i class="tio-user"></i>
                            إيميل المندوب
                            <span class="text-danger">*</span>
                        </label>
                        <select id="seller" name="seller_id" class="form-control js-select2-custom" required onchange="getProduct()">
                            <option value="" hidden>-- Choose Seller --</option>
                            @foreach($sellers as $seller)
                                <option @if(old('seller_id') == $seller->id) selected @endif value="{{ $seller->id }}">{{ $seller->email }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="stock-field-card">
                        <label for="products">
                            <i class="tio-shopping-basket"></i>
                            المنتجات
                            <span class="text-danger">*</span>
                        </label>
                        <select id="products" class="form-control js-select2-custom" multiple>
                            <option value="" disabled>---{{ \App\CPU\translate('select') }}---</option>
                        </select>
                    </div>
                </div>

                <div class="stock-products-table-wrap">
                    <table class="table stock-products-table">
                        <thead>
                            <tr>
                                <th>المنتج</th>
                                <th>الكمية</th>
                            </tr>
                        </thead>
                        <tbody id="data"></tbody>
                    </table>
                </div>

                <div class="stock-create-actions">
                    <button type="submit" class="stock-submit-btn">
                        <i class="tio-save"></i>
                        حفظ المخزون
                    </button>
                </div>
            </form>
        </section>
    </div>
</div>
@endsection

@push('script_2')
    <script>
        var product = [];
        var options = [];
        var data = [];
        var simple = [];

        "use strict"
        function getProduct()
        {
            product = [];
            simple = [];
            $.get(
                `{{ route('admin.stock.create') }}?seller=${$('#seller').val()}`,
                function(data, status) {
                    options = data.option;
                    product.push(`<option value="" disabled>---{{ \App\CPU\translate('select') }}---</option>`)
                    options.forEach((item) => {
                        item.forEach((it, i) => {
                            simple.push({id: it.id, name: it.name});
                            product.push(`<option id="${it.name}-${it.id}" value="${it.id}">${it.name}</option>`)
                        })
                    })

                    $('#products').empty().append(product);
                }
            ).then(function(data, status) {

            },
            function(error, status) {
                console.log(error.responseText)
            });
        }

        $('#products').on('change', function() {
            $('#data tr').remove()
            const selectedProducts = $(this).val() || [];
            selectedProducts.forEach((e) => {
                simple.forEach((it) => {
                    if (e == it.id)
                    {
                        data.push(`
                        <tr>
                            <td>
                                <label class="mb-0">${it.name}</label>
                                <input type="hidden" name="product_id[]" value="${it.id}">
                            </td>
                            <td>
                                <input type="number" placeholder="Ex: 2" name="stock[]" class="form-control" required>
                            </td>
                        </tr>
                        `)
                    }
                })
                $('#data').append(data);
                data = [];
            })
        })
    </script>
@endpush
