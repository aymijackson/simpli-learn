<?php

namespace Tests\Feature;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\PlatformPaymentSettings;
use App\Models\Tenant;
use App\Models\TenantRevenueSplit;
use App\Models\User;
use Elibrary\Cbt\Enums\PaymentCollector;
use Elibrary\Cbt\Models\CertificatePayment;
use Elibrary\Cbt\Models\Exam;
use Elibrary\Cbt\Models\ExamAttempt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPaymentSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function centralAdmin(): User
    {
        return User::factory()->create(['tenant_id' => null]);
    }

    private function tenantWithOwner(string $slug = 'acme'): array
    {
        $tenant = Tenant::create(['name' => ucfirst($slug), 'slug' => $slug, 'status' => TenantStatus::Active]);
        $tenant->tenantModules()->create(['module' => Module::Cbt, 'is_enabled' => true]);
        $owner = User::factory()->create(['tenant_id' => $tenant->id, 'role' => UserRole::Owner]);

        return [$tenant, $owner];
    }

    private function examWithOneQuestion(Tenant $tenant): Exam
    {
        $exam = Exam::create([
            'tenant_id' => $tenant->id,
            'title' => 'Quiz',
            'slug' => 'quiz',
            'duration_minutes' => 30,
            'pass_percentage' => 50,
            'is_published' => true,
            'certificate_policy' => 'paid',
            'certificate_price' => 100,
            'certificate_currency' => 'USD',
        ]);

        $question = $exam->questions()->create(['tenant_id' => $tenant->id, 'question_text' => 'Q1', 'position' => 0]);
        $question->options()->create(['tenant_id' => $tenant->id, 'option_text' => 'Right', 'is_correct' => true, 'position' => 0]);
        $question->options()->create(['tenant_id' => $tenant->id, 'option_text' => 'Wrong', 'is_correct' => false, 'position' => 1]);

        return $exam;
    }

    public function test_central_admin_can_reach_payment_settings(): void
    {
        $admin = $this->centralAdmin();

        $this->actingAs($admin)->get('/admin/payment-settings')->assertOk();
    }

    public function test_toggling_platform_managed_mode_changes_which_credential_a_new_payment_resolves_and_snapshots_the_split(): void
    {
        $admin = $this->centralAdmin();
        [$tenant, $owner] = $this->tenantWithOwner();

        // Tenant configures its own bank transfer (would be used in tenant-managed mode).
        $this->actingAs($owner)->put('/t/acme/cbt/manage/payment-gateways/bank_transfer', ['is_enabled' => '1']);

        // Platform configures its own bank transfer + turns on platform-managed mode + a 10% split for this tenant.
        $this->actingAs($admin)->put('/admin/payment-settings/gateways/bank_transfer', ['is_enabled' => '1']);
        $this->actingAs($admin)->put('/admin/payment-settings', ['mode' => 'platform_managed']);
        $this->actingAs($admin)->put("/admin/tenants/{$tenant->slug}/revenue-split", [
            'split_type' => 'percentage',
            'percentage' => 10,
        ])->assertRedirect();

        $this->assertTrue(PlatformPaymentSettings::current()->isPlatformManaged());

        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = $this->examWithOneQuestion($tenant);
        $this->actingAs($user)->post("/t/acme/cbt/exams/{$exam->slug}/start");
        $attempt = $exam->fresh()->attempts()->where('user_id', $user->id)->first();
        $correct = $exam->questions->first()->options->firstWhere('is_correct', true);
        $this->actingAs($user)->post("/t/acme/cbt/attempts/{$attempt->id}", ['answers' => [$exam->questions->first()->id => $correct->id]]);

        $this->actingAs($user)->post("/t/acme/cbt/attempts/{$attempt->id}/certificate/pay", ['gateway' => 'bank_transfer']);

        $payment = CertificatePayment::where('exam_attempt_id', $attempt->id)->first();
        $this->assertSame(PaymentCollector::Platform->value, $payment->collected_by->value);
        $this->assertEquals(10.0, (float) $payment->platform_fee_amount); // 10% of 100
    }

    public function test_toggling_back_to_tenant_managed_does_not_rewrite_historical_payments(): void
    {
        $admin = $this->centralAdmin();
        [$tenant, $owner] = $this->tenantWithOwner();
        $this->actingAs($owner)->put('/t/acme/cbt/manage/payment-gateways/bank_transfer', ['is_enabled' => '1']);
        $this->actingAs($admin)->put('/admin/payment-settings/gateways/bank_transfer', ['is_enabled' => '1']);
        $this->actingAs($admin)->put('/admin/payment-settings', ['mode' => 'platform_managed']);

        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = $this->examWithOneQuestion($tenant);
        $this->actingAs($user)->post("/t/acme/cbt/exams/{$exam->slug}/start");
        $attempt = $exam->fresh()->attempts()->where('user_id', $user->id)->first();
        $correct = $exam->questions->first()->options->firstWhere('is_correct', true);
        $this->actingAs($user)->post("/t/acme/cbt/attempts/{$attempt->id}", ['answers' => [$exam->questions->first()->id => $correct->id]]);
        $this->actingAs($user)->post("/t/acme/cbt/attempts/{$attempt->id}/certificate/pay", ['gateway' => 'bank_transfer']);

        $payment = CertificatePayment::where('exam_attempt_id', $attempt->id)->first();
        $this->assertSame('platform', $payment->collected_by->value);

        $this->actingAs($admin)->put('/admin/payment-settings', ['mode' => 'tenant_managed']);

        $this->assertSame('platform', $payment->fresh()->collected_by->value);
    }
}
