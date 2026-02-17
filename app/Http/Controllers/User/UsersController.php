<?php

namespace App\Http\Controllers\User;

use App\Domain\User\RecipientUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Responses\ApiResponse;
use App\Services\Contracts\UserServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UsersController extends Controller
{
    public function __construct(
        private readonly UserServiceInterface $userService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->input('per_page', 15), 100);

        $filters = $request->only(['email', 'is_active']);

        $paginator = $this->userService->list($perPage, $filters);

        return ApiResponse::list(
            collect($paginator->items())->map(fn (RecipientUser $u) => self::format($u))->toArray(),
            'Users retrieved.',
            [
                'page'      => $paginator->currentPage(),
                'per_page'  => $paginator->perPage(),
                'total'     => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        );
    }

    public function show(RecipientUser $user): JsonResponse
    {
        return ApiResponse::success(self::format($user), 'User retrieved.');
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->userService->create($request->validated());

        Log::info('user.created', [
            'actor_uuid' => $request->attributes->get('auth_admin_uuid'),
            'user_uuid'  => $user->uuid,
            'email'      => $user->email,
        ]);

        return ApiResponse::created(self::format($user), 'User created successfully.');
    }

    public function update(UpdateUserRequest $request, RecipientUser $user): JsonResponse
    {
        $user = $this->userService->update($user, $request->validated());

        Log::info('user.updated', [
            'actor_uuid' => $request->attributes->get('auth_admin_uuid'),
            'user_uuid'  => $user->uuid,
        ]);

        return ApiResponse::success(self::format($user), 'User updated successfully.');
    }

    public function destroy(Request $request, RecipientUser $user): JsonResponse
    {
        Log::info('user.deleted', [
            'actor_uuid' => $request->attributes->get('auth_admin_uuid'),
            'user_uuid'  => $user->uuid,
            'email'      => $user->email,
        ]);

        $this->userService->delete($user);

        return ApiResponse::success(null, 'User deleted successfully.');
    }

    public static function format(RecipientUser $user): array
    {
        return [
            'uuid'       => $user->uuid,
            'name'       => $user->name,
            'email'      => $user->email,
            'phone_e164' => $user->phone_e164,
            'locale'     => $user->locale,
            'timezone'   => $user->timezone,
            'is_active'  => $user->is_active,
            'created_at' => $user->created_at?->toIso8601String(),
            'updated_at' => $user->updated_at?->toIso8601String(),
        ];
    }
}
