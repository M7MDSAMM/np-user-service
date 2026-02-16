<?php

namespace App\Domain\Admin;

interface AdminRepositoryInterface
{
    public function findActiveByEmail(string $email): ?Admin;

    public function findActiveByUuid(string $uuid): ?Admin;

    public function updateLastLogin(Admin $admin): void;
}
