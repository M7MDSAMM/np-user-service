<?php

namespace Tests\Feature\User;

use App\Domain\Admin\Admin;
use App\Domain\Auth\JwtTokenServiceInterface;
use App\Domain\User\RecipientUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPreferencesTest extends TestCase
{
    use RefreshDatabase;

    private ?Admin $admin = null;

    protected function setUp(): void
    {
        parent::setUp();

        if (! file_exists(config('jwt.keys.private'))) {
            $this->artisan('jwt:generate-keys');
        }
    }

    private function authHeaders(): array
    {
        if (! $this->admin) {
            $this->admin = Admin::create([
                'name'      => 'Super Admin',
                'email'     => 'super@local.test',
                'password'  => 'Admin12345!',
                'role'      => 'super_admin',
                'is_active' => true,
            ]);
        }

        $token = $this->app->make(JwtTokenServiceInterface::class)->issueToken($this->admin);

        return ['Authorization' => 'Bearer ' . $token];
    }

    private function createUser(): RecipientUser
    {
        return RecipientUser::create([
            'name'  => 'John Doe',
            'email' => 'john@example.test',
        ]);
    }

    public function test_admin_can_get_default_preferences(): void
    {
        $user = $this->createUser();

        $response = $this->getJson("/api/v1/users/{$user->uuid}/preferences", $this->authHeaders());

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data'    => [
                    'channel_email'        => true,
                    'channel_whatsapp'     => false,
                    'channel_push'         => false,
                    'rate_limit_per_minute' => 5,
                ],
            ]);
    }

    public function test_admin_can_update_preferences(): void
    {
        $user = $this->createUser();

        $response = $this->putJson("/api/v1/users/{$user->uuid}/preferences", [
            'channel_whatsapp'     => true,
            'channel_push'         => true,
            'rate_limit_per_minute' => 10,
            'quiet_hours_start'    => '22:00',
            'quiet_hours_end'      => '08:00',
        ], $this->authHeaders());

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data'    => [
                    'channel_whatsapp'     => true,
                    'channel_push'         => true,
                    'rate_limit_per_minute' => 10,
                ],
            ]);

        $this->assertNotNull($response->json('data.quiet_hours_start'));
        $this->assertNotNull($response->json('data.quiet_hours_end'));
    }

    public function test_preferences_validation_rejects_invalid_rate_limit(): void
    {
        $user = $this->createUser();

        $response = $this->putJson("/api/v1/users/{$user->uuid}/preferences", [
            'rate_limit_per_minute' => 100,
        ], $this->authHeaders());

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['rate_limit_per_minute']);
    }

    public function test_preferences_are_persisted_across_reads(): void
    {
        $user = $this->createUser();

        $this->putJson("/api/v1/users/{$user->uuid}/preferences", [
            'channel_push' => true,
        ], $this->authHeaders());

        $response = $this->getJson("/api/v1/users/{$user->uuid}/preferences", $this->authHeaders());

        $response->assertOk()
            ->assertJson(['data' => ['channel_push' => true]]);
    }
}
