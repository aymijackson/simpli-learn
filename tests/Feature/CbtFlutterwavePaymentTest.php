<?php

namespace Tests\Feature;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Elibrary\Cbt\Enums\PaymentGateway;
use Elibrary\Cbt\Models\CertificatePayment;
use Elibrary\Cbt\Models\Exam;
use Elibrary\Cbt\Models\ExamAttempt;
use Elibrary\Cbt\Payments\FlutterwaveGateway;
use Elibrary\Cbt\Payments\PaymentGatewayFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CbtFlutterwavePaymentTest extends TestCase
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

    private function enableFlutterwave(User $owner, string $secretKey = 'FLWSECK_TEST-x'): void
    {
        $this->actingAs($owner)->put('/t/acme/cbt/manage/payment-gateways/flutterwave', [
            'is_enabled' => '1',
            'secret_key' => $secretKey,
            'webhook_secret' => 'my-configured-hash',
        ]);
    }

    public function test_the_factory_resolves_flutterwave_to_a_contract_implementation(): void
    {
        $gateway = app(PaymentGatewayFactory::class)->make(PaymentGateway::Flutterwave);

        $this->assertInstanceOf(FlutterwaveGateway::class, $gateway);
    }

    public function test_choosing_flutterwave_redirects_the_learner_to_the_checkout_link(): void
    {
        Http::fake([
            'api.flutterwave.com/v3/payments' => Http::response([
                'status' => 'success',
                'data' => ['link' => 'https://checkout.flutterwave.com/fake-session'],
            ]),
        ]);

        [$tenant, $owner] = $this->tenantWithOwner();
        $this->enableFlutterwave($owner);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = $this->examWithOneQuestion($tenant, ['certificate_policy' => 'paid', 'certificate_price' => 15, 'certificate_currency' => 'NGN']);
        $attempt = $this->startAttempt($user, $exam);
        $this->submitCorrectly($user, $exam, $attempt);

        $response = $this->actingAs($user)->post("/t/acme/cbt/attempts/{$attempt->id}/certificate/pay", [
            'gateway' => 'flutterwave',
        ]);

        $response->assertRedirect('https://checkout.flutterwave.com/fake-session');

        $payment = CertificatePayment::where('exam_attempt_id', $attempt->id)->first();
        Http::assertSent(fn ($request) => $request->url() === 'https://api.flutterwave.com/v3/payments'
            && $request['tx_ref'] === $payment->reference
            && $request['currency'] === 'NGN');
    }

    public function test_a_valid_webhook_hash_marks_the_payment_paid_and_issues_the_certificate(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $this->enableFlutterwave($owner);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = $this->examWithOneQuestion($tenant, ['certificate_policy' => 'paid', 'certificate_price' => 15, 'certificate_currency' => 'NGN']);
        $attempt = $this->startAttempt($user, $exam);
        $this->submitCorrectly($user, $exam, $attempt);

        Http::fake(['api.flutterwave.com/v3/payments' => Http::response([
            'status' => 'success', 'data' => ['link' => 'https://checkout.flutterwave.com/x'],
        ])]);
        $this->actingAs($user)->post("/t/acme/cbt/attempts/{$attempt->id}/certificate/pay", ['gateway' => 'flutterwave']);
        $payment = CertificatePayment::where('exam_attempt_id', $attempt->id)->first();

        Http::fake(['api.flutterwave.com/v3/transactions/verify_by_reference*' => Http::response([
            'status' => 'success', 'data' => ['status' => 'successful', 'id' => 998877, 'tx_ref' => $payment->reference],
        ])]);

        $payload = json_encode(['event' => 'charge.completed', 'data' => ['tx_ref' => $payment->reference, 'status' => 'successful']]);

        $response = $this->call('POST', '/webhooks/certificates/flutterwave', [], [], [], [
            'HTTP_verif-hash' => 'my-configured-hash',
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertNoContent();

        $payment->refresh();
        $this->assertSame('paid', $payment->status->value);
        $this->assertSame('998877', $payment->gateway_reference);
        $this->assertSame('verified', $attempt->fresh()->certificate->tier->value);
    }

    public function test_a_wrong_hash_is_rejected_and_does_not_mark_the_payment_paid(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $this->enableFlutterwave($owner);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $exam = $this->examWithOneQuestion($tenant, ['certificate_policy' => 'paid', 'certificate_price' => 15, 'certificate_currency' => 'NGN']);
        $attempt = $this->startAttempt($user, $exam);
        $this->submitCorrectly($user, $exam, $attempt);

        Http::fake(['api.flutterwave.com/v3/payments' => Http::response([
            'status' => 'success', 'data' => ['link' => 'https://checkout.flutterwave.com/x'],
        ])]);
        $this->actingAs($user)->post("/t/acme/cbt/attempts/{$attempt->id}/certificate/pay", ['gateway' => 'flutterwave']);
        $payment = CertificatePayment::where('exam_attempt_id', $attempt->id)->first();

        $payload = json_encode(['event' => 'charge.completed', 'data' => ['tx_ref' => $payment->reference, 'status' => 'successful']]);

        $response = $this->call('POST', '/webhooks/certificates/flutterwave', [], [], [], [
            'HTTP_verif-hash' => 'the-wrong-hash',
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertForbidden();
        $this->assertSame('pending', $payment->fresh()->status->value);
        $this->assertNull($attempt->fresh()->certificate);
    }
}
