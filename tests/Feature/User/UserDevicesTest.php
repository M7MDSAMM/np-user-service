<?php

namespace Tests\Feature\User;

use App\Domain\Admin\Admin;
use App\Domain\Auth\JwtTokenServiceInterface;
use App\Domain\User\RecipientUser;
use App\Domain\User\UserDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserDevicesTest extends TestCase
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

    public function test_admin_can_add_device(): void
    {
        $user = $this->createUser();

        $response = $this->postJson("/api/v1/users/{$user->uuid}/devices", [
            'token'    => 'fcm-token-abc123',
            'platform' => 'android',
        ], $this->authHeaders());

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => ['uuid', 'provider', 'token', 'platform', 'is_active'],
            ])
            ->assertJson([
                'success' => true,
                'data'    => [
                    'token'    => 'fcm-token-abc123',
                    'provider' => 'fcm',
                    'platform' => 'android',
                    'is_active' => true,
                ],
            ]);

        $this->assertDatabaseHas('user_devices', ['token' => 'fcm-token-abc123']);
    }

    public function test_admin_can_list_user_devices(): void
    {
        $user = $this->createUser();
        $user->devices()->create(['token' => 'token-1', 'platform' => 'ios']);
        $user->devices()->create(['token' => 'token-2', 'platform' => 'web']);

        $response = $this->getJson("/api/v1/users/{$user->uuid}/devices", $this->authHeaders());

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertCount(2, $response->json('data'));
    }

    public function test_admin_can_delete_device(): void
    {
        $user   = $this->createUser();
        $device = $user->devices()->create(['token' => 'token-del', 'platform' => 'android']);

        $response = $this->deleteJson(
            "/api/v1/users/{$user->uuid}/devices/{$device->uuid}",
            [],
            $this->authHeaders(),
        );

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted('user_devices', ['uuid' => $device->uuid]);
    }

    public function test_cannot_delete_device_belonging_to_another_user(): void
    {
        $user1  = $this->createUser();
        $user2  = RecipientUser::create(['name' => 'Other', 'email' => 'other@example.test']);
        $device = $user2->devices()->create(['token' => 'token-other', 'platform' => 'web']);

        $response = $this->deleteJson(
            "/api/v1/users/{$user1->uuid}/devices/{$device->uuid}",
            [],
            $this->authHeaders(),
        );

        $response->assertNotFound();
    }

    public function test_duplicate_token_rejected(): void
    {
        $user = $this->createUser();
        $user->devices()->create(['token' => 'duplicate-token', 'platform' => 'ios']);

        $response = $this->postJson("/api/v1/users/{$user->uuid}/devices", [
            'token'    => 'duplicate-token',
            'platform' => 'android',
        ], $this->authHeaders());

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['token']);
    }

    public function test_device_response_does_not_expose_numeric_id(): void
    {
        $user = $this->createUser();
        $user->devices()->create(['token' => 'token-no-id', 'platform' => 'web']);

        $response = $this->getJson("/api/v1/users/{$user->uuid}/devices", $this->authHeaders());

        $response->assertOk();

        foreach ($response->json('data') as $device) {
            $this->assertArrayNotHasKey('id', $device);
            $this->assertArrayNotHasKey('user_id', $device);
        }
    }
}
