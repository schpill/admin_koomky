<?php

namespace Tests\Unit\Services;

use App\Services\HealthCheckService;
use Tests\TestCase;
use Illuminate\Support\Facades\Config;

class HealthCheckServiceTest extends TestCase
{
    private HealthCheckService $healthCheck;

    protected function setUp(): void
    {
        parent::setUp();
        $this->healthCheck = app(HealthCheckService::class);
    }

    public function test_check_returns_ok_when_all_services_healthy(): void
    {
        $result = $this->healthCheck->check();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('timestamp', $result);
        $this->assertArrayHasKey('services', $result);
        $this->assertEquals('ok', $result['status']);
    }

    public function test_check_includes_postgres_status(): void
    {
        $result = $this->healthCheck->check();

        $this->assertArrayHasKey('postgres', $result['services']);
        $this->assertArrayHasKey('status', $result['services']['postgres']);
    }

    public function test_check_includes_redis_status(): void
    {
        $result = $this->healthCheck->check();

        $this->assertArrayHasKey('redis', $result['services']);
        $this->assertArrayHasKey('status', $result['services']['redis']);
    }

    public function test_check_includes_meilisearch_status(): void
    {
        $result = $this->healthCheck->check();

        $this->assertArrayHasKey('meilisearch', $result['services']);
        $this->assertArrayHasKey('status', $result['services']['meilisearch']);
    }

    public function test_check_status_is_degraded_when_meilisearch_down(): void
    {
        // Test with Meilisearch unavailable
        Config::set('scout.meilisearch.host', 'http://invalid:7700');

        $result = $this->healthCheck->check();

        $this->assertEquals('degraded', $result['status']);
    }
}
