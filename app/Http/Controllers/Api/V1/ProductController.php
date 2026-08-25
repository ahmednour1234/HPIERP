<?php

namespace App\Http\Controllers\Api\V1;

use Box\Spout\Common\Exception\InvalidArgumentException;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Writer\Exception\WriterNotOpenedException;
use Excel;
use App\CPU\Helpers;
use App\Models\Product;
use Barryvdh\DomPDF\PDF;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\BusinessSetting;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Rap2hpoutre\FastExcel\FastExcel;
use App\Http\Resources\ProductsResource;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\CategoryProductsResource;
use App\Models\CustomerPrice;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    public function __construct(
        private product $product,
        private BusinessSetting $business_setting
    ){}

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function bulk_import_data(Request $request): JsonResponse
    {
        try {
            $collections = (new FastExcel)->import($request->file('products_file'));
        } catch (\Exception $exception) {
            return response()->json(['message' => 'You have uploaded a wrong format file, please upload the right file']);
        }

        foreach ($collections as $key => $collection) {
            if ($collection['name'] === "") {
                return response()->json(['message' => 'Please fill row:' . ($key + 2) . ' field: name']);
            } elseif ($collection['product_code'] === "") {
                return response()->json(['message' => 'Please fill row:' . ($key + 2) . ' field: product_code']);
            } elseif ($collection['unit_type'] === "") {
                return response()->json(['message' => 'Please fill row:' . ($key + 2) . ' field: unit_type']);
            } elseif ($collection['unit_value'] === "") {
                return response()->json(['message' => 'Please fill row:' . ($key + 2) . ' field: unit value']);
            } elseif (!is_numeric($collection['unit_value'])) {
                return response()->json(['message' => 'Unit Value of row ' . ($key + 2) . ' must be number']);
            } elseif ($collection['brand'] === "") {
                return response()->json(['message' => 'Please fill row:' . ($key + 2) . ' field: brand']);
            } elseif ($collection['category_id'] === "") {

                return response()->json(['message' => 'Please fill row:' . ($key + 2) . ' field: category_id']);
            } elseif ($collection['sub_category_id'] === "") {
                return response()->json(['message' => 'Please fill row:' . ($key + 2) . ' field: sub_category_id']);
            } elseif ($collection['purchase_price'] === "") {

                return response()->json(['message' => 'Please fill row:' . ($key + 2) . ' field: purchase price ']);
            } elseif (!is_numeric($collection['purchase_price'])) {
                return response()->json(['message' => 'Purchase Price of row ' . ($key + 2) . ' must be number']);
            } elseif ($collection['selling_price'] === "") {
                return response()->json(['message' => 'Please fill row:' . ($key + 2) . ' field: selling_price ']);
            } elseif (!is_numeric($collection['selling_price'])) {
                return response()->json(['message' => 'Please fill row:' . ($key + 2) . ' field: number ']);
            } elseif ($collection['discount_type'] === "") {
                return response()->json(['message' => 'Please fill row:' . ($key + 2) . ' field: discount type']);
            } elseif ($collection['discount'] === "") {

                return response()->json(['message' => 'Please fill row:' . ($key + 2) . ' field: discount ']);
            } elseif (!is_numeric($collection['discount'])) {
                return response()->json(['message' => 'Discount of row ' . ($key + 2) . ' must be number']);
            } elseif ($collection['tax'] === "") {
                return response()->json(['message' => 'Please fill row:' . ($key + 2) . ' field: tax ']);
            } elseif (!is_numeric($collection['tax'])) {
                return response()->json(['message' => 'Tax of row ' . ($key + 2) . ' must be number']);
            } elseif ($collection['quantity'] === "") {
                return response()->json(['message' => 'Please fill row:' . ($key + 2) . ' field: quantity ']);
            } elseif (!is_numeric($collection['quantity'])) {
                return response()->json(['message' => 'Quantity of row ' . ($key + 2) . ' must be number ']);
            } elseif ($collection['supplier_id'] === "") {
                return response()->json(['message' => 'Please fill row:' . ($key + 2) . ' field: supplier_id ']);
            } elseif (!is_numeric($collection['supplier_id'])) {
                return response()->json(['message' => 'supplier_id of row ' . ($key + 2) . ' must be number']);
            }

            $product = [
                'discount_type' => $collection['discount_type'],
                'discount' => $collection['discount'],
            ];
            if ($collection['selling_price'] <= Helpers::discount_calculate($product, $collection['selling_price'])) {
                return response()->json(['message' => 'Discount can not be more or equal to the price in row' . ($key + 2)]);
            }
            $product =  $this->product->where('product_code', $collection['product_code'])->first();
            if ($product) {
                return response()->json(['message' => 'Product code row ' . ($key + 2) . ' already exist']);
            }
        }
        $data = [];
        foreach ($collections as $collection) {
            $product =  $this->product->where('product_code', $collection['product_code'])->first();
            if ($product) {
                return response()->json(['message' => 'Product code already exist']);
            }

            $data[] = [
                'name' => $collection['name'],
                'product_code' => $collection['product_code'],
                'image' => json_encode(['def.png']),
                'unit_type' => $collection['unit_type'],
                'unit_value' => $collection['unit_value'],
                'brand' => $collection['brand'],
                'category_id' => json_encode([['id' => $collection['category_id'], 'position' => 0], ['id' => $collection['sub_category_id'], 'position' => 1]]),
                'purchase_price' => $collection['purchase_price'],
                'selling_price' => $collection['selling_price'],
                'discount_type' => $collection['discount_type'],
                'discount' => $collection['discount'],
                'tax' => $collection['tax'],
                'quantity' => $collection['quantity'],
                'supplier_id' => $collection['supplier_id'],

            ];
        }
        DB::table('products')->insert($data);
        return response()->json(['code' => 200, 'message' => 'Products imported successfully']);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
public function categoryWiseProduct(Request $request): JsonResponse
{
    // Query to get category-wise products, ordered by name
    $category_wise_product = $this->product->with('supplier')->active()
        ->when($request->has('category_id') && $request['category_id'] != 0, function ($query) use ($request) {
            $query->where('category_id', $request['category_id']);
        })
        ->orderBy('name', 'asc') // Order by name alphabetically
        ->get(); // Execute the query and retrieve the results as a collection of models

    // Loop through products to check seller prices and set price accordingly
    foreach ($category_wise_product as $product) {
        $seller_price = \App\Models\SellerPrice::where(['seller_id' => Auth::user()->id, 'product_id' => $product->id])->first();
        
        if ($seller_price) {
            // If seller price exists, use it
            $product->selling_price = $seller_price->price; // Assuming price is the correct field in SellerPrice
        } else {
            // If no seller price exists, use the product's selling_price
            $product->selling_price = $product->selling_price; // Use selling_price from the products table
        }
    }

    // Transform the collection using the resource
    $category_wise_product = CategoryProductsResource::collection($category_wise_product);
    
    // Return the result as a JSON response
    return response()->json($category_wise_product);
}


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function codeSearch(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_code' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $limit = $request['limit'] ?? 10;
        $offset = $request['offset'] ?? 1;

        $product_by_code = $this->product->whereRaw('product_code LIKE "%' . $request->product_code . '%" OR name LIKE "%' . $request->product_code . '%"')->latest()->paginate($limit, ['*'], 'page', $offset);

        $products = ProductsResource::collection($product_by_code);
        return response()->json($products, 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function productSort(Request $request)
    {
        $limit = $request['limit'] ?? 10;
        $offset = $request['offset'] ?? 1;
        $sort = $request['sort'] ? $request['sort'] : 'ASC';
        $sort_products = $this->product->orderBy('selling_price', $sort)->latest()->paginate($limit, ['*'], 'page', $offset);
        $products = ProductsResource::collection($sort_products);
        return response()->json($products, 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function propularProductSort(Request $request): JsonResponse
    {
        $sort = $request['sort'] ? $request['sort'] : 'ASC';
        $products = $this->product->orderBy('order_count', $sort)->get();
        $products = ProductsResource::collection($products);
        return response()->json($products, 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function supplierWiseProduct(Request $request): JsonResponse
    {
        $limit = $request['limit'] ?? 10;
        $offset = $request['offset'] ?? 1;
        $product = $this->product->where('supplier_id', $request->supplier_id)->latest()->paginate($limit, ['*'], 'page', $offset);
        $products = ProductsResource::collection($product);
        $data = [
            'total' => $products->total(),
            'limit' => $limit,
            'offset' => $offset,
            'products' => $products->items(),
        ];
        return response()->json($data, 200);
    }

    public function customerPrice(Request $request): JsonResponse
    {
        $products = $request['cart'];
        $custmer_id = $request['user_id'];

        $prices = [];

        foreach($products as $i => $item)
        {
            $c_price = CustomerPrice::where('customer_id', $custmer_id)->where('product_id', $item)->first();
            $prices[$i]['id'] = $item;
            if ($c_price)
            {
                $prices[$i]['price'] = $c_price->price;
            }
            else
            {
                $prices[$i]['price'] = 0;
            }
        }
        return response()->json($prices, 200);
    }

    public function changeCustomerPrice(Request $request)
    {
        $customer_id = $request['customer_id'];
        $product_id = $request['product_id'];
        $price = $request['price'];

        $c_price = CustomerPrice::where('customer_id', $customer_id)->where('product_id', $product_id)->first();
        if ($c_price)
        {
            $c_price->price = $price;
            $c_price->update();
        }
        else
        {
            $newPrice = new CustomerPrice();
            $newPrice->customer_id = $customer_id;
            $newPrice->product_id = $product_id;
            $newPrice->price = $price;
            $newPrice->save();
        }

        return response()->json(['message' => 'price changed successfully'], 200);
    }
}
