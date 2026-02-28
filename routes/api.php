<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Prefix: /api
| Semua route di file ini otomatis menggunakan prefix /api
|
*/

// ========================================
// PUBLIC ROUTES (Tanpa Login)
// ========================================
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// ========================================
// PROTECTED ROUTES (Harus Login)
// ========================================
Route::middleware('auth:sanctum')->group(function () {

    // --- Auth ---
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });

    // --- Owner Only ---
    Route::middleware('role:Owner')->group(function () {
        Route::get('/members/pending', [AuthController::class, 'pendingMembers']);
        Route::post('/members/{user}/validate', [AuthController::class, 'validateMember']);
    });
});
