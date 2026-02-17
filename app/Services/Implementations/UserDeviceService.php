<?php

namespace App\Services\Implementations;

use App\Domain\User\RecipientUser;
use App\Domain\User\UserDevice;
use App\Services\Contracts\UserDeviceServiceInterface;
use Illuminate\Database\Eloquent\Collection;

class UserDeviceService implements UserDeviceServiceInterface
{
    public function list(RecipientUser $user): Collection
    {
        return $user->devices()->where('is_active', true)->get();
    }

    public function add(RecipientUser $user, array $data): UserDevice
    {
        return $user->devices()->create($data);
    }

    public function delete(UserDevice $device): void
    {
        $device->delete();
    }
}
