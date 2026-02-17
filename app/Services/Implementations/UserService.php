<?php

namespace App\Services\Implementations;

use App\Domain\User\RecipientUser;
use App\Services\Contracts\UserServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserService implements UserServiceInterface
{
    public function list(int $perPage, array $filters = []): LengthAwarePaginator
    {
        $query = RecipientUser::query()->orderBy('created_at', 'desc');

        if (! empty($filters['email'])) {
            $query->where('email', 'like', '%' . $filters['email'] . '%');
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        return $query->paginate($perPage);
    }

    public function find(string $uuid): RecipientUser
    {
        return RecipientUser::where('uuid', $uuid)->firstOrFail();
    }

    public function create(array $data): RecipientUser
    {
        return RecipientUser::create($data);
    }

    public function update(RecipientUser $user, array $data): RecipientUser
    {
        $user->update($data);

        return $user->fresh();
    }

    public function delete(RecipientUser $user): void
    {
        $user->delete();
    }
}
