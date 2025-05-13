<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TokenController;
use App\Http\Controllers\ProductController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware(['auth:sanctum', 'abilities:access-token']);

Route::apiResource('products', ProductController::class);

Route::get('/user/refresh', [TokenController::class, 'refresh'])->middleware(['auth:sanctum', 'abilities:refresh-token']);
Route::get('/user', [UserController::class, 'getCurrentUser'])->middleware(['auth:sanctum', 'abilities:access-token']);