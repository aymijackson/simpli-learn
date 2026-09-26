<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_pages_send_security_headers_with_a_report_only_policy_by_default(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Content-Security-Policy', "frame-ancestors 'self'");
        $this->assertStringContainsString("script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net", $response->headers->get('Content-Security-Policy-Report-Only'));
        $this->assertStringContainsString('report-uri /csp-report', $response->headers->get('Content-Security-Policy-Report-Only'));
    }

    public function test_the_policy_can_be_enforced(): void
    {
        config(['security.csp.enforce' => true]);

        $response = $this->get('/');

        $this->assertStringContainsString("default-src 'self'", $response->headers->get('Content-Security-Policy'));
        $this->assertFalse($response->headers->has('Content-Security-Policy-Report-Only'));
    }

    public function test_violation_reports_are_logged(): void
    {
        Log::shouldReceive('channel')->with('csp')->once()->andReturnSelf();
        Log::shouldReceive('warning')->once()->withArgs(fn ($message, $context) => $message === 'CSP violation'
            && $context['directive'] === 'script-src-elem'
            && $context['blocked'] === 'https://evil.example/x.js');

        $this->call('POST', '/csp-report', [], [], [], ['CONTENT_TYPE' => 'application/csp-report'], json_encode([
            'csp-report' => [
                'document-uri' => 'https://site.test/t/acme',
                'violated-directive' => 'script-src-elem',
                'blocked-uri' => 'https://evil.example/x.js',
            ],
        ]))->assertNoContent();
    }
}
