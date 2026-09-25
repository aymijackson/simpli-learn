<?php

namespace Tests\Feature;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Elibrary\Cbt\Models\Exam;
use Elibrary\Cbt\Models\ExamAttempt;
use Elibrary\Cbt\Models\PaymentGatewayCredential;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CbtCertificatePaymentsTest extends TestCase
{
    use RefreshDatabase;

    private function tenantWithOwner(string $slug = 'acme'): array
    {
        $tenant = Tenant::create(['name' => ucfirst($slug), 'slug' => $slug, 'status' => TenantStatus::Active]);
        $tenant->tenantModules()->create(['module' => Module::Cbt, 'is_enabled' => true]);
        $owner = User::factory()->create(['tenant_id' => $tenant->id, 'role' => UserRole::Owner]);

        return [$tenant, $owner];
    }

    private function examWithOneQuestion(Tenant $tenant, array $attributes = []): Exam
    {
        $exam = Exam::create(array_merge([
            'tenant_id' => $tenant->id,
            'title' => 'Quiz',
            'slug' => 'quiz',
            'duration_minutes' => 30,
            'pass_percentage' => 50,
            'is_published' => true,
        ], $attributes));

        $question = $exam->questions()->create(['tenant_id' => $tenant->id, 'question_text' => 'Q1', 'position' => 0]);
        $question->options()->create(['tenant_id' => $tenant->id, 'option_text' => 'Right', 'is_correct' => true, 'position' => 0]);
        $question->options()->create(['tenant_id' => $tenant->id, 'option_text' => 'Wrong', 'is_correct' => false, 'position' => 1]);

        return $exam;
    }

    private function startAttempt(User $user, Exam $exam): ExamAttempt
    {
        $this->actingAs($user)->post("/t/acme/cbt/exams/{$exam->slug}/start");

        return $exam->fresh()->attempts()->where('user_id', $user->id)->first();
    }

    private function submitCorrectly(User $user, Exam $exam, ExamAttempt $attempt): void
    {
        $question = $exam->questions->first();
        $correct = $question->options->firstWhere('is_correct', true);

        $this->actingAs($user)->post("/t/acme/cbt/attempts/{$attempt->id}", [
            'answers' => [$question->id => $correct->id],
        ]);
    }

    private function enableBankTransfer(User $owner): void
    {
        $this->actingAs($owner)->put('/t/acme/cbt/manage/payment-gateways/bank_transfer', [
            'is_enabled' => '1',
        ]);
    }

    public function test_a_paid_exam_blocks_the_certificate_until_payment_is_confirmed(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $this->enableBankTransfer($owner);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = $this->examWithOneQuestion($tenant, ['certificate_policy' => 'paid', 'certificate_price' => 10, 'certificate_currency' => 'USD']);
        $attempt = $this->startAttempt($user, $exam);
        $this->submitCorrectly($user, $exam, $attempt);

        $this->assertNull($attempt->fresh()->certificate);

        $this->actingAs($user)->get("/t/acme/cbt/attempts/{$attempt->id}/certificate")->assertNotFound();
    }

    public function test_the_bank_transfer_flow_from_self_report_to_owner_confirmation_issues_the_certificate(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $this->enableBankTransfer($owner);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = $this->examWithOneQuestion($tenant, ['certificate_policy' => 'paid', 'certificate_price' => 10, 'certificate_currency' => 'USD']);
        $attempt = $this->startAttempt($user, $exam);
        $this->submitCorrectly($user, $exam, $attempt);

        $storeResponse = $this->actingAs($user)->post("/t/acme/cbt/attempts/{$attempt->id}/certificate/pay", [
            'gateway' => 'bank_transfer',
        ]);
        $storeResponse->assertRedirect();

        $payment = \Elibrary\Cbt\Models\CertificatePayment::where('exam_attempt_id', $attempt->id)->first();
        $this->assertNotNull($payment);
        $this->assertSame('pending', $payment->status->value);

        $this->actingAs($user)->post("/t/acme/cbt/attempts/{$attempt->id}/certificate/pay/bank-transfer/{$payment->id}")->assertRedirect();

        // Owner confirms from the queue.
        $this->actingAs($owner)->post("/t/acme/cbt/manage/certificate-payments/{$payment->id}/confirm")->assertRedirect();

        $payment->refresh();
        $this->assertSame('paid', $payment->status->value);
        $this->assertNotNull($payment->confirmed_by_user_id);

        $certificate = $attempt->fresh()->certificate;
        $this->assertNotNull($certificate);
        $this->assertSame('verified', $certificate->tier->value);
    }

    public function test_a_freemium_upgrade_transitions_the_same_certificate_row_not_a_duplicate(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $this->enableBankTransfer($owner);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = $this->examWithOneQuestion($tenant, ['certificate_policy' => 'freemium', 'certificate_price' => 5, 'certificate_currency' => 'USD']);
        $attempt = $this->startAttempt($user, $exam);
        $this->submitCorrectly($user, $exam, $attempt);

        $unverifiedCertificateId = $attempt->fresh()->certificate->id;
        $this->assertSame('unverified', $attempt->fresh()->certificate->tier->value);

        $this->actingAs($user)->post("/t/acme/cbt/attempts/{$attempt->id}/certificate/pay", ['gateway' => 'bank_transfer']);
        $payment = \Elibrary\Cbt\Models\CertificatePayment::where('exam_attempt_id', $attempt->id)->first();
        $this->actingAs($user)->post("/t/acme/cbt/attempts/{$attempt->id}/certificate/pay/bank-transfer/{$payment->id}");
        $this->actingAs($owner)->post("/t/acme/cbt/manage/certificate-payments/{$payment->id}/confirm");

        $certificate = $attempt->fresh()->certificate;
        $this->assertSame($unverifiedCertificateId, $certificate->id);
        $this->assertSame('verified', $certificate->tier->value);
        $this->assertSame(1, \Elibrary\Cbt\Models\Certificate::where('exam_attempt_id', $attempt->id)->count());
    }

    public function test_the_pending_queue_is_tenant_isolated(): void
    {
        [$tenantA, $ownerA] = $this->tenantWithOwner('acme');
        [$tenantB, $ownerB] = $this->tenantWithOwner('other');
        $this->enableBankTransfer($ownerA);
        $user = User::factory()->create(['tenant_id' => $tenantA->id]);
        $exam = $this->examWithOneQuestion($tenantA, ['certificate_policy' => 'paid', 'certificate_price' => 10, 'certificate_currency' => 'USD']);
        $attempt = $this->startAttempt($user, $exam);
        $this->submitCorrectly($user, $exam, $attempt);
        $this->actingAs($user)->post("/t/acme/cbt/attempts/{$attempt->id}/certificate/pay", ['gateway' => 'bank_transfer']);
        $payment = \Elibrary\Cbt\Models\CertificatePayment::where('exam_attempt_id', $attempt->id)->first();

        $html = $this->actingAs($ownerB)->get('/t/other/cbt/manage/certificate-payments')->assertOk()->getContent();
        $this->assertStringNotContainsString($user->email, $html);

        // Tenant B's owner cannot confirm tenant A's payment even by guessing the id.
        $this->actingAs($ownerB)->post("/t/other/cbt/manage/certificate-payments/{$payment->id}/confirm")->assertNotFound();
    }

    public function test_gateway_credentials_are_stored_encrypted_not_as_plaintext(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();

        $this->actingAs($owner)->put('/t/acme/cbt/manage/payment-gateways/stripe', [
            'is_enabled' => '1',
            'secret_key' => 'sk_test_super_secret_value',
        ]);

        $raw = DB::table('payment_gateway_credentials')->where('tenant_id', $tenant->id)->where('gateway', 'stripe')->first();
        $this->assertNotNull($raw);
        $this->assertStringNotContainsString('sk_test_super_secret_value', $raw->credentials);

        $credential = PaymentGatewayCredential::where('tenant_id', $tenant->id)->where('gateway', 'stripe')->first();
        $this->assertSame('sk_test_super_secret_value', $credential->credentials['secret_key']);
    }
}
