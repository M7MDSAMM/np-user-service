<?php

namespace App\Services\Implementations;

use App\Domain\User\RecipientUser;
use App\Domain\User\UserNotificationPreference;
use App\Services\Contracts\UserPreferenceServiceInterface;

class UserPreferenceService implements UserPreferenceServiceInterface
{
    public function get(RecipientUser $user): UserNotificationPreference
    {
        return $user->preferences ?? $this->createDefaults($user);
    }

    public function upsert(RecipientUser $user, array $data): UserNotificationPreference
    {
        $prefs = $user->preferences;

        if ($prefs) {
            $prefs->update($data);

            return $prefs->fresh();
        }

        return $this->createDefaults($user, $data);
    }

    private function createDefaults(RecipientUser $user, array $overrides = []): UserNotificationPreference
    {
        $defaults = [
            'channel_email'        => true,
            'channel_whatsapp'     => false,
            'channel_push'         => false,
            'rate_limit_per_minute' => 5,
        ];

        return $user->preferences()->create(array_merge($defaults, $overrides));
    }
}
