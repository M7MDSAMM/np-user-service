<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\AssertsApiEnvelope;
use Tests\Support\JwtHelper;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase, JwtHelper, AssertsApiEnvelope;

    private string $loginUrl = '/api/v1/admin/auth/login';
    private string $meUrl    = '/api/v1/admin/me';

    // ── Login ───────────────────────────────────────────────────────────

    public function test_login_returns_standardized_success(): void
    {
        $this->createAdmin(['email' => 'admin@local.test']);

        $response = $this->postJson($this->loginUrl, [
            'email'    => 'admin@local.test',
            'password' => 'Admin12345!',
        ]);

        $this->assertApiSuccess($response);
        $response->assertJsonStructure([
            'data' => ['access_token', 'token_type', 'expires_in'],
        ]);
    }

    public function test_login_failure_returns_standardized_error(): void
    {
        $this->createAdmin(['email' => 'admin@local.test']);

        $response = $this->postJson($this->loginUrl, [
            'email'    => 'admin@local.test',
            'password' => 'wrong',
        ]);

        $this->assertApiError($response, 401, 'AUTH_INVALID');
    }

    public function test_login_inactive_returns_401(): void
    {
        $this->createAdmin(['email' => 'admin@local.test', 'is_active' => false]);

        $response = $this->postJson($this->loginUrl, [
            'email'    => 'admin@local.test',
            'password' => 'Admin12345!',
        ]);

        $this->assertApiError($response, 401, 'AUTH_INVALID');
    }

    public function test_login_validation_returns_standardized_422(): void
    {
        $response = $this->postJson($this->loginUrl, []);

        $this->assertApiError($response, 422, 'VALIDATION_ERROR');
        $response->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_login_updates_last_login_at(): void
    {
        $admin = $this->createAdmin(['email' => 'admin@local.test']);

        $this->postJson($this->loginUrl, [
            'email'    => 'admin@local.test',
            'password' => 'Admin12345!',
        ]);

        $admin->refresh();
        $this->assertNotNull($admin->last_login_at);
    }

    public function test_login_nonexistent_email_returns_401(): void
    {
        $response = $this->postJson($this->loginUrl, [
            'email'    => 'nobody@local.test',
            'password' => 'Admin12345!',
        ]);

        $this->assertApiError($response, 401, 'AUTH_INVALID');
    }

    // ── Me ───────────────────────────────────────────────────────────────

    public function test_me_returns_standardized_success(): void
    {
        $admin = $this->createAdmin();

        $response = $this->getJson($this->meUrl, [
            'Authorization' => 'Bearer ' . $this->tokenFor($admin),
        ]);

        $this->assertApiSuccess($response);
        $response->assertJsonStructure([
            'data' => ['uuid', 'name', 'email', 'role', 'is_active'],
        ])
            ->assertJsonPath('data.uuid', $admin->uuid);
    }

    public function test_me_without_token_returns_standardized_401(): void
    {
        $response = $this->getJson($this->meUrl);

        $this->assertApiError($response, 401, 'AUTH_INVALID');
    }

    public function test_me_with_expired_token_returns_standardized_401(): void
    {
        $admin = $this->createAdmin();

        config(['jwt.ttl' => -60]);

        $token = $this->tokenFor($admin);

        $response = $this->getJson($this->meUrl, [
            'Authorization'    => 'Bearer ' . $token,
            'X-Correlation-Id' => 'expired-123',
        ]);

        $this->assertApiError($response, 401, 'TOKEN_EXPIRED');
        $response->assertJsonPath('correlation_id', 'expired-123');
    }

    public function test_me_with_malformed_token_returns_401(): void
    {
        $response = $this->getJson($this->meUrl, [
            'Authorization' => 'Bearer not-a-valid-jwt-token',
        ]);

        $this->assertApiError($response, 401, 'AUTH_INVALID');
    }

    // ── Correlation ID ──────────────────────────────────────────────────

    public function test_response_includes_correlation_id(): void
    {
        $response = $this->getJson('/api/v1/health', ['X-Correlation-Id' => 'test-123']);

        $response->assertHeader('X-Correlation-Id', 'test-123');
    }
}
