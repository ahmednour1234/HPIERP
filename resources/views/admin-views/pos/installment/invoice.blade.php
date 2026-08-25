<style>
    body {
        direction: rtl;
        font-family: 'Tajawal', sans-serif;
        background: #f4f6f8;
    }
    .invoice-container {
        max-width: 800px;
        margin: 40px auto;
        background: #fff;
        border-radius: 12px;
        padding: 30px;
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.1);
        border: 1px solid #e0e0e0;
    }
    .header-section {
        background: linear-gradient(120deg, #001B63, #3a5fa8);
        padding: 20px;
        border-radius: 8px;
        color: #fff;
        text-align: center;
        margin-bottom: 30px;
    }
    .header-section h2 {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 5px;
    }
    .header-section h5 {
        font-size: 1rem;
        margin: 4px 0;
        opacity: 0.9;
    }
    .divider {
        width: 100%;
        height: 2px;
        background: #e0e0e0;
        margin: 30px 0;
    }
    .details {
        text-align: center;
    }
    .details h4,
    .details h6 {
        margin: 12px 0;
        color: #333;
    }
    .details h4 span,
    .details h6 span {
        display: inline-block;
        min-width: 200px;
        font-weight: 600;
        color: #001B63;
    }
    .footer {
        text-align: center;
        margin-top: 40px;
    }
    .footer h5 {
        font-size: 1.1rem;
        font-weight: 600;
        color: #3a5fa8;
    }
    /* Animation for divider line */
    @keyframes stretchLine {
        from { transform: scaleX(0); }
        to { transform: scaleX(1); }
    }
    .divider {
        transform-origin: center;
        animation: stretchLine 0.8s ease-out forwards;
    }
</style>

<div class="invoice-container">
    <div class="header-section">
        <h2>{{ \App\Models\BusinessSetting::where(['key' => 'shop_name'])->value('value') }}</h2>
        <h5>{{ \App\Models\BusinessSetting::where(['key' => 'shop_address'])->value('value') }}</h5>
        <h5>هاتف: {{ \App\Models\BusinessSetting::where(['key' => 'shop_phone'])->value('value') }}</h5>
        <h5>البريد الإلكتروني: {{ \App\Models\BusinessSetting::where(['key' => 'shop_email'])->value('value') }}</h5>
        <h5>رقم التسجيل الضريبي: {{ \App\Models\BusinessSetting::where(['key' => 'vat_reg_no'])->value('value') }}</h5>
    </div>

    <div class="divider"></div>

    <div class="details">
        <h4>رقم القسط: <span>{{ $installment->id }}</span></h4>
        <h4>اسم البائع: <span>{{ $installment->seller->f_name . ' ' . $installment->seller->l_name }}</span></h4>
        <h4>اسم العميل: <span>{{ $installment->customer->name }}</span></h4>
        <h6>تاريخ الإنشاء: <span>{{ date('d/m/Y h:i A', strtotime($installment->created_at)) }}</span></h6>
        <h4>البيان: <span>{{ $installment->note }}</span></h4>
        <h4>إجمالي القسط: <span>{{ number_format($installment->total_price, 2) }} جنيه مصري</span></h4>
    </div>

    <div class="divider"></div>

    <div class="footer">
        <h5>شكرًا لتعاملكم معنا</h5>
    </div>
</div>