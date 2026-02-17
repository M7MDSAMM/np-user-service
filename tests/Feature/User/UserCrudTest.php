<?php

namespace Tests\Feature\User;

use App\Domain\Admin\Admin;
use App\Domain\Auth\JwtTokenServiceInterface;
use App\Domain\User\RecipientUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserCrudTest extends TestCase
{
    use RefreshDatabase;

    private string $baseUrl = '/api/v1/users';

    protected function setUp(): void
    {
        parent::setUp();

        if (! file_exists(config('jwt.keys.private'))) {
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

    private function authHeaders(?Admin $admin = null): array
    {
        $admin ??= $this->createAdmin();
        $token = $this->app->make(JwtTokenServiceInterface::class)->issueToken($admin);

        return ['Authorization' => 'Bearer ' . $token];
    }

    private function createUser(array $overrides = []): RecipientUser
    {
        return RecipientUser::create(array_merge([
            'name'  => 'John Doe',
            'email' => 'john@example.test',
        ], $overrides));
    }

    // ── Authorization ───────────────────────────────────────────────────

    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson($this->baseUrl);

        $response->assertUnauthorized()
            ->assertJson(['success' => false, 'error_code' => 'AUTH_INVALID']);
    }

    public function test_admin_can_access_user_endpoints(): void
    {
        $admin = $this->createAdmin(['role' => 'admin', 'email' => 'regular@local.test']);

        $response = $this->getJson($this->baseUrl, $this->authHeaders($admin));

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    // ── Create ──────────────────────────────────────────────────────────

    public function test_admin_can_create_user(): void
    {
        $response = $this->postJson($this->baseUrl, [
            'name'       => 'Jane Doe',
            'email'      => 'jane@example.test',
            'phone_e164' => '+1234567890',
            'locale'     => 'ar',
            'timezone'   => 'Asia/Riyadh',
        ], $this->authHeaders());

        $response->assertCreated()
            ->assertJsonStructure([
                'success', 'message',
                'data' => ['uuid', 'name', 'email', 'phone_e164', 'locale', 'timezone', 'is_active'],
                'correlation_id',
            ])
            ->assertJson([
                'success' => true,
                'data'    => [
                    'name'       => 'Jane Doe',
                    'email'      => 'jane@example.test',
                    'phone_e164' => '+1234567890',
                    'locale'     => 'ar',
                    'timezone'   => 'Asia/Riyadh',
                    'is_active'  => true,
                ],
            ]);

        $this->assertDatabaseHas('recipient_users', ['email' => 'jane@example.test']);
    }

    public function test_create_validation_fails_for_duplicate_email(): void
    {
        $this->createUser(['email' => 'existing@example.test']);

        $response = $this->postJson($this->baseUrl, [
            'name'  => 'Another User',
            'email' => 'existing@example.test',
        ], $this->authHeaders());

        $response->assertUnprocessable()
            ->assertJson(['success' => false, 'error_code' => 'VALIDATION_ERROR'])
            ->assertJsonValidationErrors(['email']);
    }

    public function test_create_validation_fails_for_invalid_phone(): void
    {
        $response = $this->postJson($this->baseUrl, [
            'name'       => 'Bad Phone',
            'email'      => 'bad@example.test',
            'phone_e164' => 'not-a-phone',
        ], $this->authHeaders());

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['phone_e164']);
    }

    // ── Show ────────────────────────────────────────────────────────────

    public function test_admin_can_show_user(): void
    {
        $user = $this->createUser();

        $response = $this->getJson("{$this->baseUrl}/{$user->uuid}", $this->authHeaders());

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data'    => ['uuid' => $user->uuid, 'name' => 'John Doe'],
            ]);
    }

    public function test_show_nonexistent_returns_404(): void
    {
        $response = $this->getJson("{$this->baseUrl}/nonexistent-uuid", $this->authHeaders());

        $response->assertNotFound()
            ->assertJson(['success' => false, 'error_code' => 'NOT_FOUND']);
    }

    // ── Update ──────────────────────────────────────────────────────────

    public function test_admin_can_update_user(): void
    {
        $user = $this->createUser();

        $response = $this->putJson("{$this->baseUrl}/{$user->uuid}", [
            'name'  => 'Updated Name',
            'email' => 'john@example.test',
        ], $this->authHeaders());

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data'    => ['name' => 'Updated Name'],
            ]);
    }

    // ── Delete ──────────────────────────────────────────────────────────

    public function test_admin_can_soft_delete_user(): void
    {
        $user = $this->createUser();

        $response = $this->deleteJson("{$this->baseUrl}/{$user->uuid}", [], $this->authHeaders());

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted('recipient_users', ['uuid' => $user->uuid]);
    }

    // ── List + Pagination ───────────────────────────────────────────────

    public function test_list_returns_pagination_meta(): void
    {
        RecipientUser::create(['name' => 'User A', 'email' => 'a@example.test']);
        RecipientUser::create(['name' => 'User B', 'email' => 'b@example.test']);

        $response = $this->getJson("{$this->baseUrl}?per_page=1", $this->authHeaders());

        $response->assertOk()
            ->assertJsonStructure([
                'success', 'data',
                'meta' => ['pagination' => ['page', 'per_page', 'total', 'last_page']],
            ])
            ->assertJson([
                'meta' => ['pagination' => ['per_page' => 1, 'total' => 2, 'last_page' => 2]],
            ]);
    }

    public function test_list_filters_by_email(): void
    {
        RecipientUser::create(['name' => 'Alice', 'email' => 'alice@example.test']);
        RecipientUser::create(['name' => 'Bob', 'email' => 'bob@other.test']);

        $response = $this->getJson("{$this->baseUrl}?email=alice", $this->authHeaders());

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('alice@example.test', $response->json('data.0.email'));
    }

    public function test_list_filters_by_is_active(): void
    {
        RecipientUser::create(['name' => 'Active', 'email' => 'active@example.test', 'is_active' => true]);
        RecipientUser::create(['name' => 'Inactive', 'email' => 'inactive@example.test', 'is_active' => false]);

        $response = $this->getJson("{$this->baseUrl}?is_active=0", $this->authHeaders());

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('inactive@example.test', $response->json('data.0.email'));
    }

    // ── Response format ─────────────────────────────────────────────────

    public function test_response_does_not_expose_numeric_id(): void
    {
        $this->createUser();

        $response = $this->getJson($this->baseUrl, $this->authHeaders());

        $response->assertOk();

        foreach ($response->json('data') as $user) {
            $this->assertArrayNotHasKey('id', $user);
        }
    }
}
