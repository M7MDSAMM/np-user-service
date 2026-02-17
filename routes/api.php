<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminManagementController;
use App\Http\Controllers\User\UserDevicesController;
use App\Http\Controllers\User\UserPreferencesController;
use App\Http\Controllers\User\UsersController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| User Service — API Routes (v1)
|--------------------------------------------------------------------------
|
| Prefix: /api/v1
| All routes here are stateless and expect JSON.
|
*/

Route::get('/health', fn () => response()->json([
    'success' => true,
    'data'    => ['service' => 'user-service', 'status' => 'ok'],
]));

// ── Admin Authentication ────────────────────────────────────────────────
Route::prefix('admin')->group(function () {
    Route::post('/auth/login', [AdminAuthController::class, 'login']);

    Route::middleware('jwt.admin')->group(function () {
        Route::get('/me', [AdminAuthController::class, 'me']);
    });
});

// ── Admin Management (super_admin only) ─────────────────────────────────
Route::prefix('admins')->middleware(['jwt.admin', 'role.super'])->group(function () {
    Route::get('/', [AdminManagementController::class, 'index']);
    Route::post('/', [AdminManagementController::class, 'store']);
    Route::get('/{admin}', [AdminManagementController::class, 'show']);
    Route::put('/{admin}', [AdminManagementController::class, 'update']);
    Route::delete('/{admin}', [AdminManagementController::class, 'destroy']);
    Route::patch('/{admin}/toggle-active', [AdminManagementController::class, 'toggleActive']);
});

// ── Recipient User Management (any admin) ────────────────────────────────
Route::prefix('users')->middleware('jwt.admin')->group(function () {
    Route::get('/', [UsersController::class, 'index']);
    Route::post('/', [UsersController::class, 'store']);
    Route::get('/{user}', [UsersController::class, 'show']);
    Route::put('/{user}', [UsersController::class, 'update']);
    Route::delete('/{user}', [UsersController::class, 'destroy']);

    // Preferences
    Route::get('/{user}/preferences', [UserPreferencesController::class, 'show']);
    Route::put('/{user}/preferences', [UserPreferencesController::class, 'update']);

    // Devices
    Route::get('/{user}/devices', [UserDevicesController::class, 'index']);
    Route::post('/{user}/devices', [UserDevicesController::class, 'store']);
    Route::delete('/{user}/devices/{device}', [UserDevicesController::class, 'destroy']);
});
