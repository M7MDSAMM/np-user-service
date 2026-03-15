<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthTest extends TestCase
{
    public function test_health_returns_standardized_success_envelope(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertOk();
        $this->assertNotEmpty($response->headers->get('X-Correlation-Id'));

        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.service', 'user-service');
        $response->assertJsonPath('data.status', 'healthy');

        $json = $response->json();
        $this->assertArrayHasKey('message', $json);
        $this->assertArrayHasKey('meta', $json);
        $this->assertArrayHasKey('correlation_id', $json);
        $this->assertArrayHasKey('version', $json['data']);
        $this->assertArrayHasKey('environment', $json['data']);
        $this->assertArrayHasKey('timestamp', $json['data']);
    }

    public function test_unauthorized_envelope_has_standard_shape(): void
    {
        $response = $this->getJson('/api/v1/admin/me');

        $response->assertUnauthorized();
        $response->assertJson([
            'success'    => false,
            'error_code' => 'AUTH_INVALID',
        ]);

        $json = $response->json();
        $this->assertArrayHasKey('message', $json);
        $this->assertArrayHasKey('errors', $json);
        $this->assertArrayHasKey('correlation_id', $json);
        $this->assertArrayHasKey('meta', $json);
    }

    public function test_not_found_returns_standardized_error_envelope(): void
    {
        $response = $this->getJson('/api/v1/users/nonexistent-uuid');

        $response->assertNotFound();

        $json = $response->json();
        $this->assertFalse($json['success']);
        $this->assertSame('NOT_FOUND', $json['error_code']);
        $this->assertArrayHasKey('message', $json);
        $this->assertArrayHasKey('errors', $json);
        $this->assertArrayHasKey('correlation_id', $json);
        $this->assertArrayHasKey('meta', $json);
    }
}
