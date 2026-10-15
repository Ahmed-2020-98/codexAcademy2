<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SchedulerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['academy.scheduler_secret' => 'test-scheduler-secret']);
    }

    public function test_missing_or_invalid_secret_is_rejected(): void
    {
        Artisan::shouldReceive('call')->never();

        $this->postJson('/api/internal/scheduler')->assertUnauthorized();
        $this->withToken('incorrect')->postJson('/api/internal/scheduler')->assertUnauthorized();
    }

    public function test_unconfigured_scheduler_is_rejected(): void
    {
        config(['academy.scheduler_secret' => null]);
        Artisan::shouldReceive('call')->never();

        $this->withToken('test-scheduler-secret')->postJson('/api/internal/scheduler')->assertUnauthorized();
    }

    public function test_valid_request_runs_the_scheduler_and_releases_the_lock(): void
    {
        Artisan::shouldReceive('call')->once()->with('schedule:run', ['--no-interaction' => true])->andReturn(0);

        $this->withToken('test-scheduler-secret')->postJson('/api/internal/scheduler')->assertNoContent();
        $lock = Cache::lock('academy:scheduler', 120);
        $this->assertTrue($lock->get());
        $lock->release();
    }

    public function test_concurrent_run_is_rejected(): void
    {
        $lock = Cache::lock('academy:scheduler', 120);
        $lock->get();
        Artisan::shouldReceive('call')->never();

        try {
            $this->withToken('test-scheduler-secret')->postJson('/api/internal/scheduler')->assertStatus(409);
        } finally {
            $lock->release();
        }
    }

    public function test_failed_run_releases_the_lock(): void
    {
        Artisan::shouldReceive('call')->once()->andReturn(1);

        $this->withToken('test-scheduler-secret')->postJson('/api/internal/scheduler')->assertStatus(500);
        $lock = Cache::lock('academy:scheduler', 120);
        $this->assertTrue($lock->get());
        $lock->release();
    }
}
