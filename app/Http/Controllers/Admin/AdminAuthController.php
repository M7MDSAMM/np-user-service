<?php

namespace App\Http\Controllers\Admin;

use App\Application\Auth\AdminAuthService;
use App\Http\Requests\Admin\LoginRequest;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminAuthController
{
    public function __construct(
        private AdminAuthService $authService,
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->attemptLogin(
            $request->validated('email'),
            $request->validated('password'),
        );

        if (! $result) {
            return ApiResponse::unauthorized('Invalid credentials.', 'AUTH_INVALID');
        }

        return ApiResponse::success($result, 'Login successful.');
    }

    public function me(Request $request): JsonResponse
    {
        $admin = $this->authService->resolveAdmin(
            $request->attributes->get('auth_admin_uuid'),
        );

        if (! $admin) {
            return ApiResponse::notFound('Admin not found.');
        }

        return ApiResponse::success([
            'uuid'          => $admin->uuid,
            'name'          => $admin->name,
            'email'         => $admin->email,
            'role'          => $admin->role,
            'is_active'     => $admin->is_active,
            'last_login_at' => $admin->last_login_at?->toIso8601String(),
            'created_at'    => $admin->created_at?->toIso8601String(),
        ], 'Admin profile retrieved.');
    }
}
