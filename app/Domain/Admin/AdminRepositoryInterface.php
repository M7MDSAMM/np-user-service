<?php

namespace App\Domain\Admin;

interface AdminRepositoryInterface
{
    public function findActiveByEmail(string $email): ?Admin;

    public function findActiveByUuid(string $uuid): ?Admin;

    public function create(array $data): Admin;

    public function update(Admin $admin, array $data): Admin;

    public function delete(Admin $admin): bool;

    public function updateLastLogin(Admin $admin): void;
}
