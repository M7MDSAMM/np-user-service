<?php

namespace Tests\Support;

use App\Domain\Admin\Admin;
use App\Domain\Auth\JwtTokenServiceInterface;

trait JwtHelper
{
    private ?Admin $defaultAdmin = null;

    protected function setUp(): void
    {
        parent::setUp();

        $privatePath = config('jwt.keys.private');
        if (! file_exists($privatePath)) {
            $this->artisan('jwt:generate-keys');
        }
    }

    private function createAdmin(array $overrides = []): Admin
    {
        return Admin::create(array_merge([
            'name'      => 'Super Admin',
            'email'     => 'super@local.test',
            'password'  => 'Admin12345!',
            'role'      => 'super_admin',
            'is_active' => true,
        ], $overrides));
    }

    private function tokenFor(Admin $admin): string
    {
        return $this->app->make(JwtTokenServiceInterface::class)->issueToken($admin);
    }

    private function authHeaders(?Admin $admin = null): array
    {
        if ($admin === null) {
            $this->defaultAdmin ??= $this->createAdmin();
            $admin = $this->defaultAdmin;
        }

        return ['Authorization' => 'Bearer ' . $this->tokenFor($admin)];
    }
}
