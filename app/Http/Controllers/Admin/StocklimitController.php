<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\Product;
use App\CPU\Helpers;
use Brian2694\Toastr\Facades\Toastr;

class StocklimitController extends Controller
{
    public function __construct(
        private Product $product
    ){}

    /**
     * @param Request $request
     * @return Application|Factory|View
     */
    public function stock_limit(Request $request): Factory|View|Application
    {
        // The setting is absent on a fresh install and the null it returns made
        // the where() below throw "Illegal operator and value combination".
        $stock_limit = (int) (Helpers::get_business_settings('stock_limit') ?? 0);
        $query_param = [];
        $search = $request['search'];
        $sort_oqrderQty= $request['sort_oqrderQty'];
        if ($request->has('search')) {
            $key = explode(' ', $request['search']);
            $query = $this->product->whereColumn('quantity','<','limit_stock')->
                where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('name', 'like', "%{$value}%")
                            ->orWhere('product_code', 'like', "%{$value}%");
                    }
            });
            $query_param = ['search' => $request['search']];
        } else {
            $query = $this->product->when($request->sort_oqrderQty=='quantity_asc', function($q) use ($request){
                                    return $q->orderBy('quantity', 'asc');
                                })
                                ->when($request->sort_oqrderQty=='quantity_desc', function($q) use ($request){
                                    return $q->orderBy('quantity', 'desc');
                                })
                                ->when($request->sort_oqrderQty=='order_asc', function($q) use ($request){
                                    return $q->orderBy('order_count', 'asc');
                                })
                                ->when($request->sort_oqrderQty=='order_desc', function($q) use ($request){
                                    return $q->orderBy('order_count', 'desc');
                                })
                                ->when($request->sort_oqrderQty=='default', function($q) use ($request){
                                    return $q->orderBy('id');
                                })->where('quantity','<',$stock_limit);
        }
        $products = $query->latest()->paginate(Helpers::pagination_limit())->appends($query_param);

        return view('admin-views.stock.list',compact('products','search','sort_oqrderQty'));
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function update_quantity(Request $request): RedirectResponse
    {
        $product = $this->product->find($request->id);
        $total_quantity = $product->quantity + $request->quantity;
        if($total_quantity >= 0)
        {
            $product->quantity = $product->quantity + $request->quantity;
            $product->save();
            Toastr::success(\App\CPU\translate('product_quantity_updated_successfully!'));
        }else{
            Toastr::warning(\App\CPU\translate('product_quantity_can_not_be_less_than_0_!'));
        }
        return back();
    }
}
