<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthTest extends TestCase
{
    public function test_health_returns_correlation_id_and_version(): void
    {
        $response = $this->get('/api/v1/health');

        $response->assertOk();
        $cid = $response->headers->get('X-Correlation-Id');
        $this->assertNotEmpty($cid);

        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.service', 'user-service');
        $response->assertJsonPath('meta', []);
        $this->assertArrayHasKey('version', $response->json('data'));
    }

    public function test_unauthorized_envelope_has_standard_shape(): void
    {
        $response = $this->get('/api/v1/admin/me');

        $response->assertUnauthorized();
        $response->assertJson([
            'success'    => false,
            'error_code' => 'AUTH_INVALID',
        ]);

        $json = $response->json();
        $this->assertArrayHasKey('message', $json);
        $this->assertArrayHasKey('correlation_id', $json);
        $this->assertArrayHasKey('meta', $json);
        $this->assertIsArray($json['meta'] ?? []);
    }
}
