<?php

namespace App\Http\Controllers\Api\V1;

use App\CPU\Helpers;
use App\Models\Admin;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Seller;
use App\Models\Account;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\StorageSeller; 
class AuthController extends Controller
{
    public function __construct(
        private Admin $admin,
        private Seller $seller
    ){}

    /**
     * @param Request $request
     * @return Application|ResponseFactory|JsonResponse|Response
     */
     public function dataadmin(Request $request): Response|JsonResponse|Application|ResponseFactory
{
        $sellerId = Auth::id();


    // Find the admin by the provided code
    $admin =Seller::where('id',$sellerId)->first();



            // Fetch the updated kilometer value from the business_settings table
            $kilometerSetting = DB::table('business_settings')->where('key', 'kilometer')->first();

            // Return the response with the token and kilometer setting
            return response()->json(
                [
                    'admin' => $admin,
                    'kilometer' => (int) ($kilometerSetting->value ?? 0), // Cast to integer
                ],
                200
            );
        
    
}
public function getAllStorages(Request $request): JsonResponse
{
    if (Auth::check()) {
        $sellerId = Auth::id();
        $storages = StorageSeller::where('seller_id', $sellerId)->with('storage')->get();
        $storagess = Account::get();

        if ($storages->isEmpty()) { // Check if $storages collection is empty
            return response()->json([
                'message' => 'This seller does not have any storage',
                'storages' => [] // Return an empty array or handle as needed
            ], 409);
        }

        return response()->json([
            'message' => 'Storages retrieved successfully',
            'storages' => $storagess
        ], 200);
    } else {
        return response()->json([
            'message' => 'Unauthorized'
        ], 401);
    }
}

public function adminLogin(Request $request): Response|JsonResponse|Application|ResponseFactory
{
    // Validate the incoming request
    $validator = Validator::make($request->all(), [
        'code' => 'required',
        'password' => 'required',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => Helpers::error_processor($validator)], 403);
    }

    // Find the admin by the provided code
    $admin = $this->admin->where('mandob_code', $request->code)->where('role', 'seller')->with('storage')->first();

    if ($admin) {
        // Verify the password
        if (Hash::check($request->password, $admin->password)) {
            // Create an access token for the admin
            $token = $admin->createToken('adminToken')->accessToken;


            // Fetch the updated kilometer value from the business_settings table
            $kilometerSetting = DB::table('business_settings')->where('key', 'kilometer')->first();

            // Return the response with the token and kilometer setting
            return response()->json(
                [
                    'message' => 'You are logged in',
                    'token' => $token,
                    'admin' => $admin,
                    'kilometer' => (int) ($kilometerSetting->value ?? 0), // Cast to integer
                ],
                200
            );
        } else {
            return response()->json(["message" => "Password mismatch"], 422);
        }
    } else {
        return response()->json(["message" => 'Wrong credentials! Please input correct code and password'], 422);
    }
}


    public function confirmLogin(Request $request): Response|JsonResponse|Application|ResponseFactory
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'password' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $admin = $this->admin->where('email', $request->email)->where('role', 'admin')->first();

        if ($admin) {
            if (Hash::check($request->password, $admin->password)) {
                
                return response()->json(
                    ['message' => 'You are logged in'],
                    200
                );
            } else {
                $response = ["message" => "Password mismatch"];
                return response($response, 422);
            }
        }
        
        else {
            $response = ["message" => 'Wrong credentials! please input correct code and password'];
            return response($response, 422);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse|void
     */
    public function passwordChange(Request $request)
    {
        $adminId = Auth::guard('admin-api')->user()->id;
        $validator = Validator::make($request->all(), [
            'password' => 'required|same:confirm_password|min:8',
            'confirm_password' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        if (isset($adminId)) {
            DB::table('admins')->where(['id' => $adminId])->update([
                'password' => bcrypt($request['confirm_password'])
            ]);
            return response()->json(['message' => 'Password changed successfully.'], 200);
        }
    }

    /**
     * @return JsonResponse
     */
    public function profile(): JsonResponse
    {
        
        $profile = Auth::user();
        return response()->json($profile, 200);
    }

    public function logOut(Request $request): JsonResponse
    {
        try {
            $request->admin()->token()->revoke();
            return response()->json([
                'message' => 'Successfully logged out',
                "success" => true
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Something wrong',
                "success" => false
            ], 403);
        }
    }

public function update(Request $request): JsonResponse
{
    // Fetch authenticated admin's ID
    $admin = Auth::guard('admin-api')->user();

    if (!$admin) {
        return response()->json(['error' => 'Unauthenticated.'], 401);
    }

    $validator = Validator::make($request->all(), [
        'latitude' => 'required',
        'longitude' => 'required',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $admin->latitude = $request->latitude;
    $admin->longitude = $request->longitude;
    $admin->update();

    return response()->json(['message' => 'Admin updated successfully'], 200);
}


}
