<?php

namespace App\Http\Controllers\Admin;

use App\Application\Auth\AdminAuthService;
use App\Http\Requests\Admin\LoginRequest;
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
            return response()->json([
                'error' => [
                    'message' => 'Invalid credentials',
                    'status'  => 401,
                ],
                'correlation_id' => $request->header('X-Correlation-Id'),
            ], 401);
        }

        return response()->json($result);
    }

    public function me(Request $request): JsonResponse
    {
        $admin = $this->authService->resolveAdmin($request->attributes->get('auth_admin_uuid'));

        if (! $admin) {
            return response()->json([
                'error' => [
                    'message' => 'Admin not found',
                    'status'  => 404,
                ],
                'correlation_id' => $request->header('X-Correlation-Id'),
            ], 404);
        }

        return response()->json([
            'data' => [
                'uuid'      => $admin->uuid,
                'name'      => $admin->name,
                'email'     => $admin->email,
                'role'      => $admin->role,
                'is_active' => $admin->is_active,
            ],
        ]);
    }
}
