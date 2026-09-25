<?php

namespace Elibrary\Lms\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\User;
use Elibrary\Lms\Enums\CoursePaymentGateway;
use Elibrary\Lms\Enums\CoursePurchaseStatus;
use Elibrary\Lms\Enums\CoursePurchaseType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoursePurchase extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'course_id', 'user_id', 'purchase_type', 'gateway',
        'amount', 'currency', 'status', 'reference', 'gateway_reference',
        'paid_at', 'confirmed_by_user_id', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'purchase_type' => CoursePurchaseType::class,
            'gateway' => CoursePaymentGateway::class,
            'status' => CoursePurchaseStatus::class,
            'paid_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPending(): bool
    {
        return $this->status === CoursePurchaseStatus::Pending;
    }

    /**
     * The single place a course purchase gets marked paid and its effect
     * applied — shared by the owner's manual bank-transfer confirmation and
     * the gateway webhook handler, so "paid" can never mean two different
     * things. Idempotent: safe to call again on an already-paid purchase
     * (matters for webhooks, which gateways may legitimately redeliver).
     */
    public function markPaidAndActivate(?int $confirmedByUserId = null, ?string $gatewayReference = null): void
    {
        if ($this->status === CoursePurchaseStatus::Paid) {
            return;
        }

        $this->update([
            'status' => CoursePurchaseStatus::Paid->value,
            'paid_at' => now(),
            'confirmed_by_user_id' => $confirmedByUserId,
            'gateway_reference' => $gatewayReference ?? $this->gateway_reference,
        ]);

        if ($this->purchase_type === CoursePurchaseType::Enrollment) {
            $this->course->enrollments()->firstOrCreate(
                ['user_id' => $this->user_id],
                ['enrolled_at' => now()],
            );
        } elseif ($this->purchase_type === CoursePurchaseType::Certificate) {
            CourseCertificate::issueFor($this->course, $this->user);
        }
    }
}
