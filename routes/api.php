<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/user/register', [UserController::class, 'registerUser']);
Route::post('/user/login', [UserController::class, 'loginUser']);

Route::post('/admin/register', [AdminController::class, 'registerAdmin']);
Route::post('/admin/login', [AdminController::class, 'loginAdmin']);

// Route::middleware('auth:sanctum')->get('/user', function () {
// });
