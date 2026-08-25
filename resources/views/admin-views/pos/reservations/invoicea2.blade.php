<style>
 /* General Container for the Invoice */
.invoice-container {
    font-family: 'Arial', sans-serif;
    margin: 0 auto;
    padding: 40px;
    width: 100%;
    max-width: 700px;
    border: 1px solid #ddd;
    border-radius: 15px;
    background: #ffffff;
    direction: rtl;
    text-align: right;
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

/* Header Section */
.header {
    text-align: center;
    margin-bottom: 30px;
    position: relative;
}

.header img {
    max-width: 180px;
    margin-bottom: 20px;
}

.header h2 {
    font-size: 32px;
    font-weight: 600;
    color: #0044cc;
}

.header p {
    font-size: 14px;
    color: #777;
}

/* Invoice Title */
.invoice-title {
    text-align: center;
    font-size: 30px;
    font-weight: bold;
    margin-top: 30px;
    color: #0044cc;
    border-bottom: 3px solid #0044cc;
    padding-bottom: 15px;
}

/* Separator between sections */
.separator {
    border-top: 2px solid #ddd;
    margin: 30px 0;
}

/* Invoice Table */
.invoice-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 30px;
    border: 1px solid #ddd;
    border-radius: 8px;
    background-color: #f9f9f9;
}

.invoice-table th, .invoice-table td {
    padding: 15px;
    text-align: right;
    border: 1px solid #ddd;
}

.invoice-table th {
    background-color: #f4f4f4;
    color: #333;
    font-weight: bold;
    text-transform: uppercase;
}

.invoice-table td {
    background-color: #ffffff;
    color: #555;
    font-size: 14px;
}

.invoice-table tr:nth-child(even) {
    background-color: #f1f1f1;
}

.invoice-table tr:hover {
    background-color: #e9e9e9;
}

/* Payment Details Section */
.payment-details {
    font-size: 16px;
    margin-top: 30px;
}

.payment-details p {
    font-size: 16px;
    font-weight: bold;
    margin: 8px 0;
}

/* Footer Section */
.footer {
    margin-top: 40px;
    text-align: center;
    font-size: 14px;
    color: #777;
}

.footer-logo {
    max-width: 130px;
    margin-top: 20px;
}

/* A4 page styling for print */
@media print {
    .invoice-container {
        width: 100%;
        max-width: 100%;
        page-break-before: always;
        page-break-after: always;
    }

    .invoice-container {
        font-size: 14px;
    }

    .invoice-title {
        font-size: 28px;
    }
}

/* Styling for logo in top-right */
.top-logo {
    position: absolute;
    top: 20px;
    right: 20px;
    background-color: #fff;
    padding: 12px;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}
.signature-section {
    display: flex;
    justify-content: center; /* Centers the content horizontally */
    gap: 20px; /* Adds space between signatures */
    margin-top: 50px; /* Adds space above the section */
    font-size: 16px;
    font-weight: bold;
}

.signature {
    text-align: center; /* Centers the text within each block */
    white-space: nowrap; /* Ensures the text doesn't wrap */
}

</style>

<div class="invoice-container">
    <div class="header">
        <!-- Logo in top-center -->
        <img src="{{ asset('storage/app/public/shop/' . \App\Models\BusinessSetting::where(['key' => 'shop_logo'])->first()->value) }}" alt="Shop Logo" style="max-width: 180px;">
        <h2>أمر صرف</h2>
        <p>{{ \App\Models\BusinessSetting::where(['key' => 'shop_name'])->first()->value }}</p>
    </div>

    <hr class="separator">

    <!-- Company and Customer Information -->
    <table class="invoice-table">
        <thead>
            <tr>
                <th colspan="4" style="text-align: center; background-color: #ddd;">معلومات أمر الصرف</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>اسم المتجر</td>
                <td>{{ \App\Models\BusinessSetting::where(['key' => 'shop_name'])->first()->value }}</td>
                <td>العنوان</td>
                <td>{{ \App\Models\BusinessSetting::where(['key' => 'shop_address'])->first()->value }}</td>
            </tr>
            <tr>
                <td>رقم الجوال</td>
                <td>{{ \App\Models\BusinessSetting::where(['key' => 'shop_phone'])->first()->value }}</td>
                <td>البريد الإلكتروني</td>
                <td>{{ \App\Models\BusinessSetting::where(['key' => 'shop_email'])->first()->value }}</td>
            </tr>
            <!--<tr>-->
            <!--    <td>رقم الضريبي</td>-->
            <!--    <td>{{ \App\Models\BusinessSetting::where(['key' => 'number_tax'])->first()->value??'' }}</td>-->
            <!--    <td>رقم السجل التجاري</td>-->
            <!--    <td>{{ \App\Models\BusinessSetting::where(['key' => 'vat_reg_no'])->first()->value??'' }}</td>-->
            <!--</tr>-->
            <tr>
                <td>رقم أمر الصرف</td>
                <td>{{ $reserveProduct['id'] }}</td>
                <td>اسم المندوب</td>
                <td>{{ $reserveProduct->seller ? $reserveProduct->seller->f_name . ' ' . $reserveProduct->seller->l_name : 'Seller Not Found' }}</td>
            </tr>
            <tr>
                <td>من</td>
                <td>المخزن الرئيسي </td>
                <td>إلى مندوب</td>
                <td>{{ $reserveProduct->seller ? $reserveProduct->seller->f_name . ' ' . $reserveProduct->seller->l_name : 'Seller Not Found' }} مخزن</td>
            </tr>
        </tbody>
    </table>

    <hr class="separator">

    <h5 class="text-center mt-3">{{ date('d/M/Y h:i a', strtotime($reserveProduct['created_at'])) }}</h5>

    <center class="mt-3">
        <h5>{{ \App\CPU\translate('رقم امر صرف') }} : {{ $reserveProduct['id'] }}</h5>
        <h5>{{ \App\CPU\translate('اسم المندوب') }} : {{ $reserveProduct->seller->f_name . ' ' . $reserveProduct->seller->l_name }}</h5>
    </center>

    <hr class="separator">

    <!-- Products Table -->
    <table class="invoice-table">
        <thead>
            <tr>
                <th>{{ \App\CPU\translate('رقم') }}</th>
                <th>{{ \App\CPU\translate('المنتج') }}</th>
                <th>{{ \App\CPU\translate('الكمية') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach (json_decode($reserveProduct->data) as $key => $detail)
                @php
                    $product = \App\Models\Product::find($detail->product_id); 
                    $isDecimal = (is_float($detail->stock) || strpos((string)$detail->stock, '.') !== false);
                    $result = $isDecimal ? ($detail->stock * ($product ? $product->unit_value : 0)) : $detail->stock;
                @endphp

                @if($product)
                    <tr>
                        <td>{{ $key + 1 }}</td>
                        <td>
                            {{ $product->name }}
                            <br>
                        </td>
                     
                        <td>{{ $result }}</td>
                    </tr>
                @endif
            @endforeach
        </tbody>
    </table>

    <hr class="separator">



    <hr class="separator">
<div class="signature-section">
    <div class="signature">
        توقيع المندوب: .....................................................
    </div>
    <div class="signature">
        توقيع المحاسب: .....................................................
    </div>
    <div class="signature">
        توقيع أمين المخازن: .....................................................
    </div>
</div>


</div>
