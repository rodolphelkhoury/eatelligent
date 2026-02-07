<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\AuthenticateAdmin;
use App\Http\Middleware\AuthenticateUser;
use App\Http\Middleware\EmailVerified;
use Illuminate\Support\Facades\Route;

Route::post('/user/register', [UserController::class, 'registerUser']);
Route::post('/user/login', [UserController::class, 'loginUser']);

Route::post('/admin/register', [AdminController::class, 'registerAdmin']);
Route::post('/admin/login', [AdminController::class, 'loginAdmin']);

Route::middleware([AuthenticateUser::class])->group(function () {
    Route::post('/user/verify-email', [UserController::class, 'verifyEmail']);

    Route::middleware([EmailVerified::class])->group(function () {});
});

Route::middleware([AuthenticateAdmin::class])->group(function () {});
