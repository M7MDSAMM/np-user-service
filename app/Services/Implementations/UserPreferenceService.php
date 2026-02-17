<?php

namespace App\Services\Implementations;

use App\Domain\User\RecipientUser;
use App\Domain\User\UserNotificationPreference;
use App\Services\Contracts\UserPreferenceServiceInterface;

class UserPreferenceService implements UserPreferenceServiceInterface
{
    public function get(RecipientUser $user): UserNotificationPreference
    {
        return $user->preferences ?? $user->preferences()->create([]);
    }

    public function upsert(RecipientUser $user, array $data): UserNotificationPreference
    {
        $prefs = $user->preferences;

        if ($prefs) {
            $prefs->update($data);

            return $prefs->fresh();
        }

        return $user->preferences()->create($data);
    }
}
