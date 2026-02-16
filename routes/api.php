<?php

use App\Http\Controllers\Admin\AdminAuthController;
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

Route::get('/health', function () {
    return response()->json([
        'status'  => 'ok',
        'service' => 'user-service',
    ]);
});

// ── Admin Authentication ────────────────────────────────────────────────
Route::prefix('admin')->group(function () {
    Route::post('/auth/login', [AdminAuthController::class, 'login']);

    Route::middleware('jwt.admin')->group(function () {
        Route::get('/me', [AdminAuthController::class, 'me']);
    });
});
