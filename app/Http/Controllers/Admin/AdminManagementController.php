<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Admin\Admin;
use App\Domain\Admin\AdminRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdminRequest;
use App\Http\Requests\Admin\UpdateAdminRequest;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminManagementController extends Controller
{
    public function __construct(
        private readonly AdminRepositoryInterface $adminRepository,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->input('per_page', 15), 100);
        $query = Admin::query()->orderBy('created_at', 'desc');

        $paginator = $query->paginate($perPage);

        return ApiResponse::list(
            collect($paginator->items())->map(fn (Admin $a) => $this->formatAdmin($a))->toArray(),
            'Admins retrieved.',
            [
                'page'      => $paginator->currentPage(),
                'per_page'  => $paginator->perPage(),
                'total'     => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        );
    }

    public function show(Admin $admin): JsonResponse
    {
        return ApiResponse::success($this->formatAdmin($admin), 'Admin retrieved.');
    }

    public function store(StoreAdminRequest $request): JsonResponse
    {
        $admin = $this->adminRepository->create($request->validated());

        Log::info('admin.crud.created', [
            'actor_uuid'   => $request->attributes->get('auth_admin_uuid'),
            'created_uuid' => $admin->uuid,
            'email'        => $admin->email,
        ]);

        return ApiResponse::created($this->formatAdmin($admin), 'Admin created successfully.');
    }

    public function update(UpdateAdminRequest $request, Admin $admin): JsonResponse
    {
        $data = $request->validated();

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $admin = $this->adminRepository->update($admin, $data);

        Log::info('admin.crud.updated', [
            'actor_uuid'   => $request->attributes->get('auth_admin_uuid'),
            'updated_uuid' => $admin->uuid,
        ]);

        return ApiResponse::success($this->formatAdmin($admin), 'Admin updated successfully.');
    }

    public function destroy(Request $request, Admin $admin): JsonResponse
    {
        Log::info('admin.crud.deleted', [
            'actor_uuid'   => $request->attributes->get('auth_admin_uuid'),
            'deleted_uuid' => $admin->uuid,
            'email'        => $admin->email,
        ]);

        $this->adminRepository->delete($admin);

        return ApiResponse::success(null, 'Admin deleted successfully.');
    }

    public function toggleActive(Request $request, Admin $admin): JsonResponse
    {
        $admin = $this->adminRepository->update($admin, [
            'is_active' => ! $admin->is_active,
        ]);

        $status = $admin->is_active ? 'activated' : 'deactivated';

        Log::info('admin.crud.toggle_active', [
            'actor_uuid'   => $request->attributes->get('auth_admin_uuid'),
            'toggled_uuid' => $admin->uuid,
            'is_active'    => $admin->is_active,
        ]);

        return ApiResponse::success($this->formatAdmin($admin), "Admin {$status} successfully.");
    }

    private function formatAdmin(Admin $admin): array
    {
        return [
            'uuid'          => $admin->uuid,
            'name'          => $admin->name,
            'email'         => $admin->email,
            'role'          => $admin->role,
            'is_active'     => $admin->is_active,
            'last_login_at' => $admin->last_login_at?->toIso8601String(),
            'created_at'    => $admin->created_at?->toIso8601String(),
            'updated_at'    => $admin->updated_at?->toIso8601String(),
        ];
    }
}
