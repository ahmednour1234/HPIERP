<?php

namespace App\Http\Controllers\Api\V1;

use App\CPU\Helpers;
use App\Models\Currency;
use Illuminate\Http\Request;
use App\Models\BusinessSetting;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class SettingController extends Controller
{
    public function __construct(
        private Currency $currency,
        private BusinessSetting $business_setting
    ){}

    public function updateShop(Request $request)
    {
        DB::table('business_settings')->updateOrInsert(['key' => 'shop_name'], [
            'value' => $request['shop_name']
        ]);

        DB::table('business_settings')->updateOrInsert(['key' => 'shop_email'], [
            'value' => $request['shop_email']
        ]);

        DB::table('business_settings')->updateOrInsert(['key' => 'shop_phone'], [
            'value' => $request['shop_phone']
        ]);

        DB::table('business_settings')->updateOrInsert(['key' => 'stock_limit'], [
            'value' => $request['stock_limit']
        ]);

        DB::table('business_settings')->updateOrInsert(['key' => 'shop_address'], [
            'value' => $request['shop_address']
        ]);

        DB::table('business_settings')->updateOrInsert(['key' => 'pagination_limit'], [
            'value' => $request['pagination_limit']
        ]);

        DB::table('business_settings')->updateOrInsert(['key' => 'currency'], [
            'value' => $request['currency']
        ]);

        DB::table('business_settings')->updateOrInsert(['key' => 'currency_symbol'], [
            'value' => $request['currency_symbol']
        ]);

        DB::table('business_settings')->updateOrInsert(['key' => 'country'], [
            'value' => $request['country']
        ]);

        DB::table('business_settings')->updateOrInsert(['key' => 'footer_text'], [
            'value' => $request['footer_text']
        ]);

        // optional(): the row does not exist until a logo is first uploaded,
        // and reading ->value on null answered 500 for the whole request.
        $curr_logo = optional($this->business_setting->where(['key' => 'shop_logo'])->first())->value;
        DB::table('business_settings')->updateOrInsert(['key' => 'shop_logo'], [
            'value' => $request->hasFile('shop_logo')
                ? Helpers::update('shop/', $curr_logo, 'png', $request->file('shop_logo'))
                : $curr_logo
        ]);
        DB::table('business_settings')->updateOrInsert(['key' => 'time_zone'], [
            'value' => $request['time_zone'],
        ]);
        DB::table('business_settings')->updateOrInsert(['key' => 'vat_reg_no'], [
            'value' => $request['vat_reg_no'],
        ]);
        return response()->json([
            'success' => true,
            'message' => 'Shop updated succefully',
        ], 200);
    }
    public function configuration()
    {
        $key = [
            'shop_logo',
            'pagination_limit',
            'currency',
            'shop_name',
            'shop_address',
            'shop_phone',
            'shop_email',
            'footer_text',
            'app_minimum_version_ios',
            'country',
            'stock_limit',
            'time_zone',
            'vat_reg_no'
        ];
        $config_key_value_array =  array_column($this->business_setting->whereIn('key', $key)->get()->toArray(), 'value', 'key');
        return response()->json([
            'business_info' => $config_key_value_array,
            'currency_symbol' => optional($this->currency->where(['currency_code' => Helpers::currency_code()])->first())->currency_symbol,
            'base_urls' => [
                'category_image_url' => asset('storage/app/public/category'),
                'brand_image_url' => asset('storage/app/public/brand'),
                'product_image_url' => asset('storage/app/public/product'),
                'supplier_image_url' => asset('storage/app/public/supplier'),
                'shop_image_url' => asset('storage/app/public/shop'),
                'admin_image_url' => asset('storage/app/public/admin'),
                'customer_image_url' => asset('storage/app/public/customer'),
            ],
            'time_zone' => TIME_ZONE
        ], 200);
    }
}
