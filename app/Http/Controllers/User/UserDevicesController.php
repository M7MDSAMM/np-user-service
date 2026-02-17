<?php

namespace App\Http\Controllers\User;

use App\Domain\User\RecipientUser;
use App\Domain\User\UserDevice;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreDeviceRequest;
use App\Http\Responses\ApiResponse;
use App\Services\Contracts\UserDeviceServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserDevicesController extends Controller
{
    public function __construct(
        private readonly UserDeviceServiceInterface $deviceService,
    ) {}

    public function index(RecipientUser $user): JsonResponse
    {
        $devices = $this->deviceService->list($user);

        return ApiResponse::success(
            $devices->map(fn (UserDevice $d) => self::format($d))->toArray(),
            'Devices retrieved.',
        );
    }

    public function store(StoreDeviceRequest $request, RecipientUser $user): JsonResponse
    {
        $device = $this->deviceService->add($user, $request->validated());

        Log::info('user.device.added', [
            'actor_uuid' => $request->attributes->get('auth_admin_uuid'),
            'user_uuid'  => $user->uuid,
            'device_uuid' => $device->uuid,
        ]);

        return ApiResponse::created(self::format($device), 'Device added successfully.');
    }

    public function destroy(Request $request, RecipientUser $user, UserDevice $device): JsonResponse
    {
        if ($device->user_id !== $user->id) {
            return ApiResponse::notFound('Device not found for this user.');
        }

        Log::info('user.device.deleted', [
            'actor_uuid'  => $request->attributes->get('auth_admin_uuid'),
            'user_uuid'   => $user->uuid,
            'device_uuid' => $device->uuid,
        ]);

        $this->deviceService->delete($device);

        return ApiResponse::success(null, 'Device deleted successfully.');
    }

    private static function format(UserDevice $device): array
    {
        return [
            'uuid'         => $device->uuid,
            'provider'     => $device->provider,
            'token'        => $device->token,
            'platform'     => $device->platform,
            'is_active'    => $device->is_active,
            'last_seen_at' => $device->last_seen_at?->toIso8601String(),
            'created_at'   => $device->created_at?->toIso8601String(),
        ];
    }
}
