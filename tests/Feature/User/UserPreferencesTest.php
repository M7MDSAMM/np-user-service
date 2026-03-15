<?php

namespace Tests\Feature\User;

use App\Domain\User\RecipientUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\AssertsApiEnvelope;
use Tests\Support\JwtHelper;
use Tests\TestCase;

class UserPreferencesTest extends TestCase
{
    use RefreshDatabase, JwtHelper, AssertsApiEnvelope;

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

        $this->assertApiSuccess($response);
        $response->assertJson([
            'data' => [
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
            'channel_whatsapp'      => true,
            'channel_push'          => true,
            'rate_limit_per_minute' => 10,
            'quiet_hours_start'     => '22:00',
            'quiet_hours_end'       => '08:00',
        ], $this->authHeaders());

        $this->assertApiSuccess($response);
        $response->assertJson([
            'data' => [
                'channel_whatsapp'      => true,
                'channel_push'          => true,
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

        $this->assertApiSuccess($response);
        $response->assertJsonPath('data.channel_push', true);
    }

    public function test_preferences_for_nonexistent_user_returns_404(): void
    {
        $response = $this->getJson('/api/v1/users/nonexistent-uuid/preferences', $this->authHeaders());

        $this->assertApiError($response, 404, 'NOT_FOUND');
    }
}
