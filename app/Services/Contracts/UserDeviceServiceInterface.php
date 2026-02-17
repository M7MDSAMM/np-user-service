<?php

namespace App\Services\Contracts;

use App\Domain\User\RecipientUser;
use App\Domain\User\UserDevice;
use Illuminate\Database\Eloquent\Collection;

interface UserDeviceServiceInterface
{
    public function list(RecipientUser $user): Collection;

    public function add(RecipientUser $user, array $data): UserDevice;

    public function delete(UserDevice $device): void;
}
