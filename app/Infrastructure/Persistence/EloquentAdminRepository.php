<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Admin\Admin;
use App\Domain\Admin\AdminRepositoryInterface;

class EloquentAdminRepository implements AdminRepositoryInterface
{
    public function findActiveByEmail(string $email): ?Admin
    {
        return Admin::query()
            ->where('email', $email)
            ->where('is_active', true)
            ->first();
    }

    public function findActiveByUuid(string $uuid): ?Admin
    {
        return Admin::query()
            ->where('uuid', $uuid)
            ->where('is_active', true)
            ->first();
    }

    public function updateLastLogin(Admin $admin): void
    {
        $admin->update(['last_login_at' => now()]);
    }
}
