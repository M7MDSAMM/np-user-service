<?php

namespace Tests\Feature\Admin;

use App\Domain\Admin\Admin;
use App\Domain\Auth\JwtTokenServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    private string $loginUrl = '/api/v1/admin/auth/login';
    private string $meUrl    = '/api/v1/admin/me';

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
            'name'      => 'Test Admin',
            'email'     => 'admin@local.test',
            'password'  => 'Admin12345!',
            'role'      => 'super_admin',
            'is_active' => true,
        ], $overrides));
    }

    private function tokenFor(Admin $admin): string
    {
        return $this->app->make(JwtTokenServiceInterface::class)->issueToken($admin);
    }

    // ── Login ───────────────────────────────────────────────────────────

    public function test_login_returns_standardized_success(): void
    {
        $this->createAdmin();

        $response = $this->postJson($this->loginUrl, [
            'email'    => 'admin@local.test',
            'password' => 'Admin12345!',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success', 'message', 'data' => ['access_token', 'token_type', 'expires_in'],
                'meta', 'correlation_id',
            ])
            ->assertJson(['success' => true]);
    }

    public function test_login_failure_returns_standardized_error(): void
    {
        $this->createAdmin();

        $response = $this->postJson($this->loginUrl, [
            'email'    => 'admin@local.test',
            'password' => 'wrong',
        ]);

        $response->assertUnauthorized()
            ->assertJson([
                'success'    => false,
                'error_code' => 'AUTH_INVALID',
            ]);
    }

    public function test_login_inactive_returns_401(): void
    {
        $this->createAdmin(['is_active' => false]);

        $response = $this->postJson($this->loginUrl, [
            'email'    => 'admin@local.test',
            'password' => 'Admin12345!',
        ]);

        $response->assertUnauthorized()
            ->assertJson(['success' => false, 'error_code' => 'AUTH_INVALID']);
    }

    public function test_login_validation_returns_standardized_422(): void
    {
        $response = $this->postJson($this->loginUrl, []);

        $response->assertUnprocessable()
            ->assertJsonStructure(['success', 'message', 'error_code', 'errors', 'correlation_id'])
            ->assertJson([
                'success'    => false,
                'error_code' => 'VALIDATION_ERROR',
            ])
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_login_updates_last_login_at(): void
    {
        $admin = $this->createAdmin();

        $this->postJson($this->loginUrl, [
            'email'    => 'admin@local.test',
            'password' => 'Admin12345!',
        ]);

        $admin->refresh();
        $this->assertNotNull($admin->last_login_at);
    }

    // ── Me ───────────────────────────────────────────────────────────────

    public function test_me_returns_standardized_success(): void
    {
        $admin = $this->createAdmin();

        $response = $this->getJson($this->meUrl, [
            'Authorization' => 'Bearer '.$this->tokenFor($admin),
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success', 'message',
                'data' => ['uuid', 'name', 'email', 'role', 'is_active'],
                'meta', 'correlation_id',
            ])
            ->assertJson(['success' => true, 'data' => ['uuid' => $admin->uuid]]);
    }

    public function test_me_without_token_returns_standardized_401(): void
    {
        $response = $this->getJson($this->meUrl);

        $response->assertUnauthorized()
            ->assertJson(['success' => false, 'error_code' => 'AUTH_INVALID']);
    }

    public function test_me_with_expired_token_returns_standardized_401(): void
    {
        $admin = $this->createAdmin();

        // Make the issued token already expired
        config(['jwt.ttl' => -60]);

        $token = $this->tokenFor($admin);

        $response = $this->getJson($this->meUrl, [
            'Authorization'    => 'Bearer '.$token,
            'X-Correlation-Id' => 'expired-123',
        ]);

        $response->assertUnauthorized()
            ->assertJson([
                'success'        => false,
                'error_code'     => 'TOKEN_EXPIRED',
                'correlation_id' => 'expired-123',
                'message'        => 'Token expired',
            ]);
    }

    // ── Correlation ID ──────────────────────────────────────────────────

    public function test_response_includes_correlation_id(): void
    {
        $response = $this->getJson('/api/v1/health', ['X-Correlation-Id' => 'test-123']);

        $response->assertHeader('X-Correlation-Id', 'test-123');
    }
}
