<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\CreateCafeteriaStaffRequest;
use App\Http\Requests\Admin\UpdateCafeteriaStaffPasswordRequest;
use App\Http\Requests\Auth\LoginAdminRequest;
use App\Http\Requests\Auth\RegisterAdminRequest;
use App\Models\Admin;
use App\Models\CafeteriaStaff;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    /**
     * Register a new admin
     */
    public function registerAdmin(RegisterAdminRequest $request)
    {
        $admin = Admin::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        $token = $admin->createToken('auth_token')->plainTextToken;

        return response()->json([
            'admin' => $admin,
            'token' => $token,
        ], 201);
    }

    /**
     * Login admin
     */
    public function loginAdmin(LoginAdminRequest $request)
    {
        $admin = Admin::where('email', $request->email)->first();

        if (! $admin || ! Hash::check($request->password, $admin->password)) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }

        $token = $admin->createToken('auth_token')->plainTextToken;

        return response()->json([
            'admin' => $admin,
            'token' => $token,
        ], 200);
    }

    /**
     * Create cafeteria staff
     */
    public function createCafeteriaStaff(CreateCafeteriaStaffRequest $request)
    {
        $staff = CafeteriaStaff::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        return response()->json([
            'message' => 'Cafeteria staff created successfully',
            'staff' => $staff,
        ], 201);
    }

    /**
     * Update cafeteria staff password by email
     */
    public function updateCafeteriaStaffPassword(UpdateCafeteriaStaffPasswordRequest $request)
    {
        $staff = CafeteriaStaff::where('email', $request->email)->firstOrFail();

        $staff->update([
            'password' => bcrypt($request->password),
        ]);

        return response()->json([
            'message' => 'Password updated successfully',
        ], 200);
    }
}
