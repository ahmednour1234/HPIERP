<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    use ApiResponse;

    /** The signed-in seller, without the fields nobody should receive. */
    public function show(Request $request): JsonResponse
    {
        $admin = $request->user();

        return $this->ok([
            'id'           => $admin->id,
            'name'         => trim($admin->f_name . ' ' . $admin->l_name),
            'name_en'      => $admin->name_en,
            'email'        => $admin->email,
            'phone'        => $admin->phone,
            'image'        => $admin->image,
            'role'         => $admin->role,
            'mandob_code'  => $admin->mandob_code,
            'vehicle_code' => $admin->vehicle_code,
            'company_id'   => $admin->company_id,
        ], 'Profile retrieved');
    }

    public function changePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'confirmed', Password::min(8)],
        ]);

        $admin = $request->user();

        if (!Hash::check($data['current_password'], $admin->password)) {
            return $this->fail('The current password is incorrect.', 422);
        }

        $admin->password = Hash::make($data['password']);
        $admin->save();

        return $this->noContent('Password changed');
    }
}
