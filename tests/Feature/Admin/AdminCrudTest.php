<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\AssertsApiEnvelope;
use Tests\Support\JwtHelper;
use Tests\TestCase;

class AdminCrudTest extends TestCase
{
    use RefreshDatabase, JwtHelper, AssertsApiEnvelope;

    private string $baseUrl = '/api/v1/admins';

    // ── Authorization ───────────────────────────────────────────────────

    public function test_super_admin_can_list_admins(): void
    {
        $admin = $this->createAdmin();

        $response = $this->getJson($this->baseUrl, $this->authHeaders($admin));

        $this->assertApiSuccess($response);
        $response->assertJsonStructure([
            'meta' => ['pagination'],
        ]);
    }

    public function test_regular_admin_cannot_list_admins(): void
    {
        $admin = $this->createAdmin([
            'email' => 'regular@local.test',
            'role'  => 'admin',
        ]);

        $response = $this->getJson($this->baseUrl, $this->authHeaders($admin));

        $this->assertApiError($response, 403, 'FORBIDDEN');
    }

    public function test_unauthenticated_cannot_list_admins(): void
    {
        $response = $this->getJson($this->baseUrl);

        $this->assertApiError($response, 401, 'AUTH_INVALID');
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

        $this->assertApiSuccess($response, 201);
        $response->assertJsonStructure([
            'data' => ['uuid', 'name', 'email', 'role', 'is_active'],
        ])
            ->assertJson([
                'data' => [
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

        $this->assertApiError($response, 403, 'FORBIDDEN');
    }

    public function test_create_validation_returns_standardized_422(): void
    {
        $actor = $this->createAdmin();

        $response = $this->postJson($this->baseUrl, [
            'name' => '',
        ], $this->authHeaders($actor));

        $this->assertApiError($response, 422, 'VALIDATION_ERROR');
        $response->assertJsonValidationErrors(['name', 'email', 'password', 'role']);
    }

    public function test_create_duplicate_email_returns_422(): void
    {
        $actor = $this->createAdmin();

        $response = $this->postJson($this->baseUrl, [
            'name'     => 'Duplicate',
            'email'    => 'super@local.test',
            'password' => 'Password123!',
            'role'     => 'admin',
        ], $this->authHeaders($actor));

        $this->assertApiError($response, 422, 'VALIDATION_ERROR');
        $response->assertJsonValidationErrors(['email']);
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

        $this->assertApiSuccess($response);
        $response->assertJson([
            'data' => ['uuid' => $target->uuid, 'name' => 'Target Admin'],
        ]);
    }

    public function test_show_nonexistent_returns_404(): void
    {
        $actor = $this->createAdmin();

        $response = $this->getJson("{$this->baseUrl}/nonexistent-uuid", $this->authHeaders($actor));

        $this->assertApiError($response, 404, 'NOT_FOUND');
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

        $this->assertApiSuccess($response);
        $response->assertJsonPath('data.name', 'Updated Name');
    }

    // ── Delete ──────────────────────────────────────────────────────────

    public function test_super_admin_can_soft_delete_admin(): void
    {
        $actor = $this->createAdmin();
        $target = $this->createAdmin([
            'email' => 'target@local.test',
        ]);

        $response = $this->deleteJson("{$this->baseUrl}/{$target->uuid}", [], $this->authHeaders($actor));

        $this->assertApiSuccess($response);
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

        $this->assertApiSuccess($response);
        $response->assertJsonPath('data.is_active', false);
    }

    public function test_toggle_active_is_idempotent(): void
    {
        $actor = $this->createAdmin();
        $target = $this->createAdmin([
            'email'     => 'target@local.test',
            'is_active' => false,
        ]);

        $response = $this->patchJson(
            "{$this->baseUrl}/{$target->uuid}/toggle-active",
            [],
            $this->authHeaders($actor),
        );

        $this->assertApiSuccess($response);
        $response->assertJsonPath('data.is_active', true);
    }

    // ── Response format ─────────────────────────────────────────────────

    public function test_response_does_not_expose_numeric_id(): void
    {
        $actor = $this->createAdmin();

        $response = $this->getJson($this->baseUrl, $this->authHeaders($actor));

        $response->assertOk();

        foreach ($response->json('data') as $admin) {
            $this->assertArrayNotHasKey('id', $admin);
        }
    }

    // ── Pagination ──────────────────────────────────────────────────────

    public function test_list_returns_pagination_meta(): void
    {
        $actor = $this->createAdmin();
        $this->createAdmin(['email' => 'admin2@local.test']);
        $this->createAdmin(['email' => 'admin3@local.test']);

        $response = $this->getJson("{$this->baseUrl}?per_page=1", $this->authHeaders($actor));

        $this->assertApiSuccess($response);
        $response->assertJsonStructure([
            'meta' => ['pagination' => ['page', 'per_page', 'total', 'last_page']],
        ]);
    }
}
