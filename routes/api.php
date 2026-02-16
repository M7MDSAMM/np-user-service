<?php

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
