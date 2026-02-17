<?php

namespace App\Services\Contracts;

use App\Domain\User\RecipientUser;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface UserServiceInterface
{
    public function list(int $perPage, array $filters = []): LengthAwarePaginator;

    public function find(string $uuid): RecipientUser;

    public function create(array $data): RecipientUser;

    public function update(RecipientUser $user, array $data): RecipientUser;

    public function delete(RecipientUser $user): void;
}
