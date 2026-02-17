<?php

namespace App\Services\Contracts;

use App\Domain\User\RecipientUser;
use App\Domain\User\UserNotificationPreference;

interface UserPreferenceServiceInterface
{
    public function get(RecipientUser $user): UserNotificationPreference;

    public function upsert(RecipientUser $user, array $data): UserNotificationPreference;
}
