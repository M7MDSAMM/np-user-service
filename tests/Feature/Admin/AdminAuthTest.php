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

        // Generate JWT keys for testing if they don't exist
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

    public function test_login_success_returns_token(): void
    {
        $this->createAdmin();

        $response = $this->postJson($this->loginUrl, [
            'email'    => 'admin@local.test',
            'password' => 'Admin12345!',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'expires_in',
            ])
            ->assertJson([
                'token_type'  => 'Bearer',
                'expires_in'  => 900,
            ]);

        $this->assertNotEmpty($response->json('access_token'));
    }

    public function test_login_fails_for_inactive_admin(): void
    {
        $this->createAdmin(['is_active' => false]);

        $response = $this->postJson($this->loginUrl, [
            'email'    => 'admin@local.test',
            'password' => 'Admin12345!',
        ]);

        $response->assertUnauthorized()
            ->assertJsonPath('error.message', 'Invalid credentials');
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $this->createAdmin();

        $response = $this->postJson($this->loginUrl, [
            'email'    => 'admin@local.test',
            'password' => 'wrong-password',
        ]);

        $response->assertUnauthorized();
    }

    public function test_login_validates_required_fields(): void
    {
        $response = $this->postJson($this->loginUrl, []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_me_returns_admin_data_with_valid_token(): void
    {
        $admin = $this->createAdmin();

        $tokenService = $this->app->make(JwtTokenServiceInterface::class);
        $token = $tokenService->issueToken($admin);

        $response = $this->getJson($this->meUrl, [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertOk()
            ->assertJsonPath('data.uuid', $admin->uuid)
            ->assertJsonPath('data.name', 'Test Admin')
            ->assertJsonPath('data.email', 'admin@local.test')
            ->assertJsonPath('data.role', 'super_admin')
            ->assertJsonPath('data.is_active', true);
    }

    public function test_me_returns_401_without_token(): void
    {
        $response = $this->getJson($this->meUrl);

        $response->assertUnauthorized()
            ->assertJsonPath('error.status', 401);
    }

    public function test_me_returns_401_with_invalid_token(): void
    {
        $response = $this->getJson($this->meUrl, [
            'Authorization' => 'Bearer invalid.token.here',
        ]);

        $response->assertUnauthorized();
    }

    public function test_login_updates_last_login_at(): void
    {
        $admin = $this->createAdmin();
        $this->assertNull($admin->last_login_at);

        $this->postJson($this->loginUrl, [
            'email'    => 'admin@local.test',
            'password' => 'Admin12345!',
        ]);

        $admin->refresh();
        $this->assertNotNull($admin->last_login_at);
    }

    public function test_response_includes_correlation_id_header(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertHeader('X-Correlation-Id');
    }
}
