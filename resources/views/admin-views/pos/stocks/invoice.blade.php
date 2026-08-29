<div class="width-inone">
    <div class="text-center mb-3">
        <h2 class="line-inone">{{ optional(\App\Models\BusinessSetting::where(['key' => 'shop_name'])->first())->value }}</h2>
        <h5 class="style-inone">
            {{ optional(\App\Models\BusinessSetting::where(['key' => 'shop_address'])->first())->value }}
        </h5>
        <h5 class="style-intwo">
            {{ \App\CPU\translate('Phone') }}
            : {{ optional(\App\Models\BusinessSetting::where(['key' => 'shop_phone'])->first())->value }}
        </h5>
        <h5 class="style-intwo">
            {{ \App\CPU\translate('Email') }}
            : {{ optional(\App\Models\BusinessSetting::where(['key' => 'shop_email'])->first())->value }}
        </h5>
        <h5 class="style-intwo">
            {{ \App\CPU\translate('Vat_registration_number') }}
            : {{ optional(\App\Models\BusinessSetting::where(['key' => 'vat_reg_no'])->first())->value }}
        </h5>
    </div>

    <hr class="line-dot">

    <center class="mt-3">
        <h5>{{ \App\CPU\translate('order_ID') }} : {{ $order->id }}</h5>
        
        <h5>{{ \App\CPU\translate('seller_name') }} : {{ optional($order->seller)->f_name . ' ' . optional($order->seller)->l_name }}</h5>

        <h5 class="font-inone fz-10">
            {{ date('d/M/Y h:i a', strtotime($order->created_at)) }}
        </h5>
    </center>
    <h5 class="text-uppercase"></h5>
    <hr class="line-dot">

    <h5>Sales Products</h5>
    <table class="table mt-3">
        <thead>
            <tr>
                <th>{{ \App\CPU\translate('SL') }}</th>
                <th>{{ \App\CPU\translate('DESC') }}</th>
                <th>{{ \App\CPU\translate('QTY') }}</th>

            </tr>
        </thead>

        <tbody>
            @php($sub_total = 0)

            @foreach ($order->statistcs->products as $key => $detail)
                <tr>
                    <td>
                        {{ $key + 1 }}
                    </td>
                    <td>
                        <span class="style-inthree">{{ $detail->name }}</span><br />
                        {{ \App\CPU\translate('price') }} :
                        {{ number_format($detail->price, 2) }} <br>
                    </td>
                    <td class="">
                        {{ $detail->quantity }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    
    <hr class="line-dot">
    <h5>Remain Products</h5>
    <table class="table mt-3">
        <thead>
            <tr>
                <th>{{ \App\CPU\translate('SL') }}</th>
                <th>{{ \App\CPU\translate('DESC') }}</th>
                <th>{{ \App\CPU\translate('QTY') }}</th>
            </tr>
        </thead>

        <tbody>
            @foreach ($order->statistcs->remain_products as $key => $detail)
                <tr>
                    <td>
                        {{ $key + 1 }}
                    </td>
                    <td>
                        <span class="style-inthree">{{ $detail->name }}</span><br />
                        {{ \App\CPU\translate('price') }} :
                        {{ number_format($detail->price, 2) }} <br>
                    </td>
                    <td class="">
                        {{ $detail->quantity }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    
    <hr class="line-dot">
    <dl class="row text-black-50">
        
        <dt class="col-7">{{ \App\CPU\translate('vehicle_code') }}:</dt>
        <dd class="col-5  text-right">{{ $order->statistcs->vehicle_code }}</dd>
        
        <dt class="col-7">{{ \App\CPU\translate('product_count') }}:</dt>
        <dd class="col-5  text-right">{{ $order->statistcs->product_count }}</dd>
        
        <dt class="col-7">{{ \App\CPU\translate('total_stock') }}:</dt>
        <dd class="col-5  text-right">{{ $order->statistcs->total_stock }}</dd>
        
        <dt class="col-7">{{ \App\CPU\translate('remain_stock') }}:</dt>
        <dd class="col-5  text-right">{{ $order->statistcs->remain_stock }}</dd>
        
        <dt class="col-7">{{ \App\CPU\translate('total_cash') }}:</dt>
        <dd class="col-5  text-right">{{ number_format($order->statistcs->total_cash, 2) }}</dd>
        
        <dt class="col-7">{{ \App\CPU\translate('total_credit') }}:</dt>
        <dd class="col-5  text-right">{{ number_format($order->statistcs->total_credit, 2) }}</dd>
        
        <dt class="col-7">{{ \App\CPU\translate('order_count') }}:</dt>
        <dd class="col-5  text-right">{{ $order->statistcs->order_count }}</dd>
        
        <dt class="col-7">{{ \App\CPU\translate('refund_total') }}:</dt>
        <dd class="col-5  text-right">{{ number_format($order->statistcs->refund_total, 2) }}</dd>
        
        <dt class="col-7">{{ \App\CPU\translate('installment_total') }}:</dt>
        <dd class="col-5  text-right">{{ number_format($order->statistcs->installment_total, 2) }}</dd>
        
        
    </dl>
    
    <hr class="line-dot">
    <h5 class="text-center">
        """{{ \App\CPU\translate('THANK YOU') }}"""
    </h5>
    <hr class="line-dot">
</div>
