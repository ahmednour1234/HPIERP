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
        <h5>{{ \App\CPU\translate('reservation_ID') }} : {{ $reserveProduct['id'] }}</h5>
        
        <h5>{{ \App\CPU\translate('seller_name') }} : {{ $reserveProduct->seller->f_name . ' ' . $reserveProduct->seller->l_name }}</h5>
        <h5>{{ \App\CPU\translate('customer_name') }} : {{ $reserveProduct->customer->name??'' }}</h5>

        <h5 class="font-inone fz-10">
            {{ date('d/M/Y h:i a', strtotime($reserveProduct['created_at'])) }}
        </h5>
    </center>
    <h5 class="text-uppercase"></h5>
    <hr class="line-dot">

    <table class="table mt-3">
        <thead>
            <tr>
                <th>{{ \App\CPU\translate('SL') }}</th>
                <th>{{ \App\CPU\translate('DESC') }}</th>
                <th>{{ \App\CPU\translate('QTY') }}</th>
                <th>{{ \App\CPU\translate('Balance') }}</th>
            </tr>
        </thead>

        <tbody>
            @php($sub_total = 0)
            @php($total_tax = 0)
            @php($total_dis_on_pro = 0)
            @foreach (json_decode($reserveProduct->data) as $key => $detail)
                @php($product = \App\Models\Product::find($detail->product_id))
                <tr>
                    <td>
                        {{ $key + 1 }}
                    </td>
                    <td>
                        <span class="style-inthree">{{ $product->name ??'' }}</span><br />
                        {{ \App\CPU\translate('price') }} :
                        {{ number_format($product->selling_price, 2) }} <br>
                    </td>
                    <td class="">
                        {{ $detail->stock }}
                    </td>
                      <td class="">
                        {{ $detail->balance }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <hr class="line-dot">
    
    <hr class="line-dot">
    <h5 class="text-center">
        """{{ \App\CPU\translate('THANK YOU') }}"""
    </h5>
    <hr class="line-dot">
</div>
