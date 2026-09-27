<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ErrorOccurred;
use App\Support\Monitoring\HealthCheck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class MonitoringTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['monitoring.alerts_enabled' => true, 'monitoring.alert_email' => 'ops@example.com']);
        Route::middleware('web')->get('/_boom/{secret}', fn () => throw new \RuntimeException('Kaboom'));
    }

    public function test_health_is_ok_when_everything_works_and_the_scheduler_is_running(): void
    {
        HealthCheck::beat();

        $this->getJson('/health')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('checks.database.ok', true)
            ->assertJsonPath('checks.storage.ok', true)
            ->assertJsonPath('checks.cache.ok', true)
            ->assertJsonPath('checks.scheduler.ok', true)
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_health_fails_when_the_scheduler_is_not_running(): void
    {
        $this->getJson('/health')->assertStatus(503)->assertJsonPath('checks.scheduler.ok', false);

        Cache::forever(HealthCheck::HEARTBEAT_KEY, now()->subMinutes(30)->toIso8601String());
        $this->getJson('/health')->assertStatus(503)->assertJsonPath('status', 'failing');
    }

    public function test_errors_email_the_alert_address_once_per_window(): void
    {
        Notification::fake();

        $this->get('/_boom/reset-token-123')->assertStatus(500);
        $this->get('/_boom/reset-token-456')->assertStatus(500);

        Notification::assertSentOnDemandTimes(ErrorOccurred::class, 1);
        Notification::assertSentOnDemand(ErrorOccurred::class, function (ErrorOccurred $n, array $channels, AnonymousNotifiable $to) {
            // The route pattern is reported, never the real path with its secret.
            return $to->routes['mail'] === ['ops@example.com']
                && $n->summary['message'] === 'Kaboom'
                && $n->summary['page'] === 'GET _boom/{secret}'
                && ! str_contains(implode(' ', $n->summary), 'reset-token');
        });

        // After the window, the next alert says how often it happened meanwhile.
        $this->travel(16)->minutes();
        $this->get('/_boom/x')->assertStatus(500);
        Notification::assertSentOnDemandTimes(ErrorOccurred::class, 2);
        Notification::assertSentOnDemand(ErrorOccurred::class, fn (ErrorOccurred $n) => str_contains($n->summary['occurrences'] ?? '', 'times since the last alert'));
    }

    public function test_not_found_pages_and_disabled_alerts_send_nothing(): void
    {
        Notification::fake();

        $this->get('/this-page-does-not-exist')->assertNotFound();
        config(['monitoring.alerts_enabled' => false]);
        $this->get('/_boom/x')->assertStatus(500);

        Notification::assertNothingSent();
    }

    public function test_alerts_go_to_platform_admins_when_no_address_is_set(): void
    {
        Notification::fake();
        config(['monitoring.alert_email' => null]);
        User::factory()->create(['tenant_id' => null, 'email' => 'admin@example.com']);

        $this->get('/_boom/x');

        Notification::assertSentOnDemand(ErrorOccurred::class, fn ($n, $channels, AnonymousNotifiable $to) => $to->routes['mail'] === ['admin@example.com']);
    }

    public function test_admins_see_system_health_and_can_send_a_test_alert(): void
    {
        Notification::fake();
        HealthCheck::beat();
        $admin = User::factory()->create(['tenant_id' => null]);

        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('System health')->assertSee('Scheduler')->assertSee('Error alerts are');

        $this->artisan('monitoring:test-alert')->expectsOutputToContain('Test alert sent to: ops@example.com')->assertSuccessful();
        Notification::assertSentOnDemand(ErrorOccurred::class);
    }
}
