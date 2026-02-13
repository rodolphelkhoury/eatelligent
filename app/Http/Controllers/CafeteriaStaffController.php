<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginCafeteriaStaffRequest;
use App\Models\CafeteriaStaff;
use Illuminate\Support\Facades\Hash;

class CafeteriaStaffController extends Controller
{
    public function loginCafeteriaStaff(LoginCafeteriaStaffRequest $request)
    {
        $cafeteriaStaff = CafeteriaStaff::where('email', $request->email)->first();

        if (! $cafeteriaStaff || ! Hash::check($request->password, $cafeteriaStaff->password)) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }

        $token = $cafeteriaStaff->createToken('auth_token')->plainTextToken;

        return response()->json([
            'cafeteria_staff' => $cafeteriaStaff,
            'token' => $token,
        ], 200);
    }
}
