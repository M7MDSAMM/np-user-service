<?php

namespace App\Http\Controllers\User;

use App\Domain\User\RecipientUser;
use App\Domain\User\UserNotificationPreference;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\UpdatePreferencesRequest;
use App\Http\Responses\ApiResponse;
use App\Services\Contracts\UserPreferenceServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserPreferencesController extends Controller
{
    public function __construct(
        private readonly UserPreferenceServiceInterface $prefService,
    ) {}

    public function show(RecipientUser $user): JsonResponse
    {
        $prefs = $this->prefService->get($user);

        return ApiResponse::success(self::format($prefs), 'Preferences retrieved.');
    }

    public function update(UpdatePreferencesRequest $request, RecipientUser $user): JsonResponse
    {
        $prefs = $this->prefService->upsert($user, $request->validated());

        Log::info('user.preferences.updated', [
            'actor_uuid' => $request->attributes->get('auth_admin_uuid'),
            'user_uuid'  => $user->uuid,
        ]);

        return ApiResponse::success(self::format($prefs), 'Preferences updated successfully.');
    }

    private static function format(UserNotificationPreference $prefs): array
    {
        return [
            'channel_email'        => $prefs->channel_email,
            'channel_whatsapp'     => $prefs->channel_whatsapp,
            'channel_push'         => $prefs->channel_push,
            'rate_limit_per_minute' => $prefs->rate_limit_per_minute,
            'quiet_hours_start'    => $prefs->quiet_hours_start,
            'quiet_hours_end'      => $prefs->quiet_hours_end,
            'updated_at'           => $prefs->updated_at?->toIso8601String(),
        ];
    }
}
