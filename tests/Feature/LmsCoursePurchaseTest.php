<?php

namespace Tests\Feature;

use App\Enums\Module;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Elibrary\Lms\Models\Course;
use Elibrary\Lms\Models\CoursePaymentGatewayCredential;
use Elibrary\Lms\Models\CoursePurchase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LmsCoursePurchaseTest extends TestCase
{
    use RefreshDatabase;

    private function tenantWithOwner(string $slug = 'acme'): array
    {
        $tenant = Tenant::create(['name' => ucfirst($slug), 'slug' => $slug, 'status' => TenantStatus::Active]);
        $tenant->tenantModules()->create(['module' => Module::Lms, 'is_enabled' => true]);
        $owner = User::factory()->create(['tenant_id' => $tenant->id, 'role' => UserRole::Owner]);

        return [$tenant, $owner];
    }

    private function paidCourse(Tenant $tenant): Course
    {
        return Course::create([
            'tenant_id' => $tenant->id, 'title' => 'Algebra', 'slug' => 'algebra', 'is_published' => true,
            'pricing_policy' => 'paid', 'price' => 25, 'currency' => 'USD',
        ]);
    }

    private function enableBankTransfer(User $owner): void
    {
        $this->actingAs($owner)->put('/t/acme/lms/manage/payment-gateways/bank_transfer', ['is_enabled' => '1']);
    }

    public function test_purchase_store_creates_a_pending_course_purchase(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $this->enableBankTransfer($owner);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $course = $this->paidCourse($tenant);

        $this->actingAs($user)->post('/t/acme/lms/courses/algebra/purchase', ['gateway' => 'bank_transfer'])->assertRedirect();

        $purchase = CoursePurchase::where('course_id', $course->id)->first();
        $this->assertNotNull($purchase);
        $this->assertSame('pending', $purchase->status->value);
        $this->assertSame('enrollment', $purchase->purchase_type->value);
    }

    public function test_bank_transfer_self_report_then_owner_confirm_creates_the_enrollment(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();
        $this->enableBankTransfer($owner);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $course = $this->paidCourse($tenant);

        $this->actingAs($user)->post('/t/acme/lms/courses/algebra/purchase', ['gateway' => 'bank_transfer']);
        $purchase = CoursePurchase::where('course_id', $course->id)->first();

        $this->actingAs($user)->post("/t/acme/lms/courses/algebra/purchase/bank-transfer/{$purchase->id}")->assertRedirect();

        $this->assertFalse($course->isEnrolled($user));

        $this->actingAs($owner)->post("/t/acme/lms/manage/course-purchases/{$purchase->id}/confirm")->assertRedirect();

        $purchase->refresh();
        $this->assertSame('paid', $purchase->status->value);
        $this->assertNotNull($purchase->confirmed_by_user_id);
        $this->assertTrue($course->fresh()->isEnrolled($user->fresh()));
    }

    public function test_the_pending_queue_is_tenant_isolated(): void
    {
        [$tenantA, $ownerA] = $this->tenantWithOwner('acme');
        [$tenantB, $ownerB] = $this->tenantWithOwner('other');
        $this->enableBankTransfer($ownerA);
        $user = User::factory()->create(['tenant_id' => $tenantA->id]);
        $course = $this->paidCourse($tenantA);

        $this->actingAs($user)->post('/t/acme/lms/courses/algebra/purchase', ['gateway' => 'bank_transfer']);
        $purchase = CoursePurchase::where('course_id', $course->id)->first();

        $html = $this->actingAs($ownerB)->get('/t/other/lms/manage/course-purchases')->assertOk()->getContent();
        $this->assertStringNotContainsString($user->email, $html);

        $this->actingAs($ownerB)->post("/t/other/lms/manage/course-purchases/{$purchase->id}/confirm")->assertNotFound();
    }

    public function test_gateway_credentials_are_stored_encrypted_not_as_plaintext(): void
    {
        [$tenant, $owner] = $this->tenantWithOwner();

        $this->actingAs($owner)->put('/t/acme/lms/manage/payment-gateways/stripe', [
            'is_enabled' => '1',
            'secret_key' => 'sk_test_super_secret_value',
        ]);

        $raw = DB::table('course_payment_gateway_credentials')->where('tenant_id', $tenant->id)->where('gateway', 'stripe')->first();
        $this->assertNotNull($raw);
        $this->assertStringNotContainsString('sk_test_super_secret_value', $raw->credentials);

        $credential = CoursePaymentGatewayCredential::where('tenant_id', $tenant->id)->where('gateway', 'stripe')->first();
        $this->assertSame('sk_test_super_secret_value', $credential->credentials['secret_key']);
    }
}
