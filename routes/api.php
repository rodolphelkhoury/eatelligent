<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\CafeteriaStaffController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WalletController;
use App\Http\Middleware\AuthenticateAdmin;
use App\Http\Middleware\AuthenticateCafeteriaStaff;
use App\Http\Middleware\AuthenticateUser;
use App\Http\Middleware\EmailVerified;
use Illuminate\Support\Facades\Route;

Route::post('/stripe/webhook', [WalletController::class, 'webhook']);

Route::post('/user/register', [UserController::class, 'registerUser']);
Route::post('/user/login', [UserController::class, 'loginUser']);

Route::post('/admin/register', [AdminController::class, 'registerAdmin']);
Route::post('/admin/login', [AdminController::class, 'loginAdmin']);

Route::post('/cafeteria-staff/login', [CafeteriaStaffController::class, 'loginCafeteriaStaff']);

Route::middleware([AuthenticateUser::class])->group(function () {
    Route::post('/user/verify-email', [UserController::class, 'verifyEmail']);

    Route::middleware([EmailVerified::class])->group(function () {
        Route::prefix('user')->group(function () {
            Route::get('/products', [ProductController::class, 'browseProducts']);
            Route::get('/products/{product}', [ProductController::class, 'show']);
            Route::post('/orders', [OrderController::class, 'store']);
        });

        Route::post('/wallet/checkout', [WalletController::class, 'checkout']);
        Route::get('/wallet', [WalletController::class, 'show']);
    });
});

Route::middleware([AuthenticateCafeteriaStaff::class])->group(function () {

    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index']);
        Route::get('/{category}', [CategoryController::class, 'show']);
        Route::post('/', [CategoryController::class, 'store']);
        Route::put('/{category}', [CategoryController::class, 'update']);
        Route::delete('/{category}', [CategoryController::class, 'destroy']);

        Route::post('/{category}/attach-products', [CategoryController::class, 'attachProducts']);
        Route::post('/{category}/detach-products', [CategoryController::class, 'detachProducts']);
    });

    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index']);
        Route::get('/{product}', [ProductController::class, 'show']);
        Route::post('/', [ProductController::class, 'store']);
        Route::put('/{product}', [ProductController::class, 'update']);
        Route::delete('/{product}', [ProductController::class, 'destroy']);

        Route::post('/{product}/attach-categories', [ProductController::class, 'attachCategories']);
        Route::post('/{product}/detach-categories', [ProductController::class, 'detachCategories']);
    });

});

Route::middleware([AuthenticateAdmin::class])->group(function () {});
