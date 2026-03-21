<?php

namespace App\Http\Controllers;

use App\Actions\PayOrder;
use App\Http\Requests\Image\AttachImageRequest;
use App\Http\Requests\User\LoginUserRequest;
use App\Http\Requests\User\RegisterUserRequest;
use App\Http\Requests\User\VerifyUserEmail;
use App\Mail\SendOtpMail;
use App\Models\Image;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class UserController extends Controller
{
    public function registerUser(RegisterUserRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        $otp = $user->generateOtp();
        Mail::send(new SendOtpMail($user, $otp));

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    public function loginUser(LoginUserRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 200);
    }

    public function verifyEmail(VerifyUserEmail $request)
    {
        $user = $request->user();

        if ($user->email_verified_at) {
            return response()->json([
                'message' => 'Email already verified.',
            ], 400);
        }

        if (! $user->verifyOtp($request->otp)) {
            return response()->json([
                'message' => 'Invalid or expired OTP.',
            ], 422);
        }

        $user->update([
            'email_verified_at' => now(),
        ]);

        return response()->json([
            'message' => 'Email verified successfully.',
        ]);
    }

    public function resendOtp(Request $request)
    {
        $user = $request->user();

        if ($user->email_verified_at) {
            return response()->json([
                'message' => 'Email already verified.',
            ], 400);
        }

        $otp = $user->generateOtp();
        Mail::send(new SendOtpMail($user, $otp));

        return response()->json([
            'message' => 'OTP resent successfully.',
        ]);
    }

    public function payOrder(Request $request, Order $order, PayOrder $payOrder)
    {
        $user = $request->user();

        try {
            $transaction = $payOrder->execute($user, $order);

            $order->load('orderItems.product');

            return response()->json([
                'message' => 'Order paid successfully.',
                'order' => $order,
                'transaction' => $transaction,
            ]);
        } catch (\Exception $e) {
            $msg = $e->getMessage();

            if (str_contains($msg, 'Unauthorized')) {
                return response()->json(['message' => $msg], 403);
            }

            if (str_contains($msg, 'Insufficient')) {
                return response()->json(['message' => $msg], 400);
            }

            return response()->json(['message' => $msg], 400);
        }
    }

    public function attachImage(AttachImageRequest $request)
    {
        $user = $request->user();

        $image = Image::findOrFail($request->validated()['image_id']);

        if ($image->creator_id !== $user->id || $image->creator_type !== $user->getMorphClass()) {
            return response()->json([
                'message' => 'You can only attach images you have created.',
            ], 403);
        }

        if ($image->owner_id !== null && $image->owner_type !== null) {
            return response()->json([
                'message' => 'This image is already attached to another owner.',
            ], 422);
        }

        if ($user->image()->exists()) {
            return response()->json([
                'message' => 'This user already has a profile image.',
            ], 422);
        }

        $image->owner()->associate($user);
        $image->save();

        return response()->json([
            'message' => 'Image attached successfully',
            'user' => $user->refresh(),
        ], 200);
    }

    public function detachImage(AttachImageRequest $request)
    {
        $user = $request->user();

        $image = $user->images()
            ->where('id', $request->validated()['image_id'])
            ->first();

        if (! $image) {
            return response()->json([
                'message' => 'Image not found for this user',
            ], 404);
        }

        $image->owner()->dissociate();
        $image->save();

        return response()->json([
            'message' => 'Image detached successfully',
            'user' => $user->refresh(),
        ], 200);
    }
}
