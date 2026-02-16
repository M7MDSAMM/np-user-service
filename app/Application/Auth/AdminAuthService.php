<?php

namespace App\Application\Auth;

use App\Domain\Admin\Admin;
use App\Domain\Admin\AdminRepositoryInterface;
use App\Domain\Auth\JwtTokenServiceInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AdminAuthService
{
    public function __construct(
        private AdminRepositoryInterface $adminRepository,
        private JwtTokenServiceInterface $tokenService,
    ) {}

    /**
     * Attempt admin login. Returns token data array or null on failure.
     *
     * @return array{access_token: string, token_type: string, expires_in: int}|null
     */
    public function attemptLogin(string $email, string $password): ?array
    {
        $admin = $this->adminRepository->findActiveByEmail($email);

        if (! $admin || ! Hash::check($password, $admin->password)) {
            Log::warning('admin.login.failed', ['email' => $email]);

            return null;
        }

        $token = $this->tokenService->issueToken($admin);

        $this->adminRepository->updateLastLogin($admin);

        Log::info('admin.login.success', [
            'admin_uuid' => $admin->uuid,
            'email'      => $admin->email,
        ]);

        return [
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'expires_in'   => config('jwt.ttl'),
        ];
    }

    /**
     * Resolve admin by UUID (for /me endpoint).
     */
    public function resolveAdmin(string $uuid): ?Admin
    {
        return $this->adminRepository->findActiveByUuid($uuid);
    }
}
