<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Feature tests for the Products API.
 * These run against a real database (SQLite in-memory for CI).
 */
final class ProductApiTest extends TestCase
{
    public function test_health_endpoint_returns_200(): void
    {
        $response = $this->get('/health');
        $response->assertStatus(200);
        $response->assertJsonStructure(['service', 'status', 'checks', 'timestamp']);
    }
}
