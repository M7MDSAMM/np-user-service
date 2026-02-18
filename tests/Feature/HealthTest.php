<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthTest extends TestCase
{
    public function test_health_returns_correlation_id_and_version(): void
    {
        $response = $this->get('/api/v1/health');

        $response->assertOk();
        $this->assertNotEmpty($response->headers->get('X-Correlation-Id'));

        $response->assertJsonPath('data.service', 'user-service');
        $this->assertArrayHasKey('version', $response->json('data'));
    }
}
