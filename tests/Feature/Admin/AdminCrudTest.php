<?php

namespace Tests\Feature\Admin;

use App\Domain\Admin\Admin;
use App\Domain\Auth\JwtTokenServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCrudTest extends TestCase
{
    use RefreshDatabase;

    private string $baseUrl = '/api/v1/admins';

    protected function setUp(): void
    {
        parent::setUp();

        $privatePath = config('jwt.keys.private');
        if (! file_exists($privatePath)) {
            $this->artisan('jwt:generate-keys');
        }
    }

    private function createAdmin(array $overrides = []): Admin
    {
        return Admin::create(array_merge([
            'name'      => 'Super Admin',
            'email'     => 'super@local.test',
            'password'  => 'Admin12345!',
            'role'      => 'super_admin',
            'is_active' => true,
        ], $overrides));
    }

    private function tokenFor(Admin $admin): string
    {
        return $this->app->make(JwtTokenServiceInterface::class)->issueToken($admin);
    }

    private function authHeaders(Admin $admin): array
    {
        return ['Authorization' => 'Bearer '.$this->tokenFor($admin)];
    }

    // ── Authorization ───────────────────────────────────────────────────

    public function test_super_admin_can_list_admins(): void
    {
        $admin = $this->createAdmin();

        $response = $this->getJson($this->baseUrl, $this->authHeaders($admin));

        $response->assertOk()
            ->assertJsonStructure([
                'success', 'message', 'data', 'meta' => ['pagination'],
                'correlation_id',
            ])
            ->assertJson(['success' => true]);
    }

    public function test_regular_admin_cannot_list_admins(): void
    {
        $admin = $this->createAdmin([
            'email' => 'regular@local.test',
            'role'  => 'admin',
        ]);

        $response = $this->getJson($this->baseUrl, $this->authHeaders($admin));

        $response->assertForbidden()
            ->assertJson(['success' => false, 'error_code' => 'FORBIDDEN']);
    }

    public function test_unauthenticated_cannot_list_admins(): void
    {
        $response = $this->getJson($this->baseUrl);

        $response->assertUnauthorized()
            ->assertJson(['success' => false, 'error_code' => 'AUTH_INVALID']);
    }

    // ── Create ──────────────────────────────────────────────────────────

    public function test_super_admin_can_create_admin(): void
    {
        $actor = $this->createAdmin();

        $response = $this->postJson($this->baseUrl, [
            'name'     => 'New Admin',
            'email'    => 'new@local.test',
            'password' => 'Password123!',
            'role'     => 'admin',
        ], $this->authHeaders($actor));

        $response->assertCreated()
            ->assertJsonStructure([
                'success', 'message',
                'data' => ['uuid', 'name', 'email', 'role', 'is_active'],
                'correlation_id',
            ])
            ->assertJson([
                'success' => true,
                'data'    => [
                    'name'  => 'New Admin',
                    'email' => 'new@local.test',
                    'role'  => 'admin',
                ],
            ]);

        $this->assertDatabaseHas('admins', ['email' => 'new@local.test']);
    }

    public function test_regular_admin_cannot_create_admin(): void
    {
        $admin = $this->createAdmin([
            'email' => 'regular@local.test',
            'role'  => 'admin',
        ]);

        $response = $this->postJson($this->baseUrl, [
            'name'     => 'New Admin',
            'email'    => 'new@local.test',
            'password' => 'Password123!',
            'role'     => 'admin',
        ], $this->authHeaders($admin));

        $response->assertForbidden()
            ->assertJson(['success' => false, 'error_code' => 'FORBIDDEN']);
    }

    public function test_create_validation_returns_standardized_422(): void
    {
        $actor = $this->createAdmin();

        $response = $this->postJson($this->baseUrl, [
            'name' => '',
        ], $this->authHeaders($actor));

        $response->assertUnprocessable()
            ->assertJson([
                'success'    => false,
                'error_code' => 'VALIDATION_ERROR',
            ])
            ->assertJsonValidationErrors(['name', 'email', 'password', 'role']);
    }

    // ── Show ────────────────────────────────────────────────────────────

    public function test_super_admin_can_show_admin(): void
    {
        $actor = $this->createAdmin();
        $target = $this->createAdmin([
            'email' => 'target@local.test',
            'name'  => 'Target Admin',
        ]);

        $response = $this->getJson("{$this->baseUrl}/{$target->uuid}", $this->authHeaders($actor));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data'    => ['uuid' => $target->uuid, 'name' => 'Target Admin'],
            ]);
    }

    public function test_show_nonexistent_returns_404(): void
    {
        $actor = $this->createAdmin();

        $response = $this->getJson("{$this->baseUrl}/nonexistent-uuid", $this->authHeaders($actor));

        $response->assertNotFound()
            ->assertJson(['success' => false, 'error_code' => 'NOT_FOUND']);
    }

    // ── Update ──────────────────────────────────────────────────────────

    public function test_super_admin_can_update_admin(): void
    {
        $actor = $this->createAdmin();
        $target = $this->createAdmin([
            'email' => 'target@local.test',
            'name'  => 'Old Name',
        ]);

        $response = $this->putJson("{$this->baseUrl}/{$target->uuid}", [
            'name'  => 'Updated Name',
            'email' => 'target@local.test',
            'role'  => 'admin',
        ], $this->authHeaders($actor));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data'    => ['name' => 'Updated Name'],
            ]);
    }

    // ── Delete ──────────────────────────────────────────────────────────

    public function test_super_admin_can_soft_delete_admin(): void
    {
        $actor = $this->createAdmin();
        $target = $this->createAdmin([
            'email' => 'target@local.test',
        ]);

        $response = $this->deleteJson("{$this->baseUrl}/{$target->uuid}", [], $this->authHeaders($actor));

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted('admins', ['uuid' => $target->uuid]);
    }

    // ── Toggle Active ───────────────────────────────────────────────────

    public function test_super_admin_can_toggle_active(): void
    {
        $actor = $this->createAdmin();
        $target = $this->createAdmin([
            'email'     => 'target@local.test',
            'is_active' => true,
        ]);

        $response = $this->patchJson(
            "{$this->baseUrl}/{$target->uuid}/toggle-active",
            [],
            $this->authHeaders($actor),
        );

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data'    => ['is_active' => false],
            ]);
    }

    // ── Response does not expose internal ID ────────────────────────────

    public function test_response_does_not_expose_numeric_id(): void
    {
        $actor = $this->createAdmin();

        $response = $this->getJson($this->baseUrl, $this->authHeaders($actor));

        $response->assertOk();

        foreach ($response->json('data') as $admin) {
            $this->assertArrayNotHasKey('id', $admin);
        }
    }
}
