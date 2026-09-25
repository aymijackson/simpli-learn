<?php

namespace Elibrary\Cbt\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\User;
use Elibrary\Cbt\Enums\CertificateTier;
use Elibrary\Cbt\Enums\PaymentCollector;
use Elibrary\Cbt\Enums\PaymentGateway;
use Elibrary\Cbt\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CertificatePayment extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'exam_attempt_id', 'certificate_id', 'user_id', 'gateway',
        'amount', 'currency', 'status', 'reference', 'gateway_reference',
        'collected_by', 'platform_fee_amount', 'platform_fee_note',
        'paid_at', 'confirmed_by_user_id', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'gateway' => PaymentGateway::class,
            'status' => PaymentStatus::class,
            'collected_by' => PaymentCollector::class,
            'paid_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class, 'exam_attempt_id');
    }

    public function certificate(): BelongsTo
    {
        return $this->belongsTo(Certificate::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by_user_id');
    }

    public function isPending(): bool
    {
        return $this->status === PaymentStatus::Pending;
    }

    /**
     * The single place a payment gets marked paid and its certificate
     * issued/upgraded — shared by the owner's manual bank-transfer
     * confirmation and the Stripe/Paystack/Flutterwave webhook handler, so
     * both paths can never drift on what "paid" actually does. Idempotent:
     * calling it again on an already-paid payment is a no-op (matters for
     * webhooks, which gateways may legitimately redeliver).
     */
    public function markPaidAndIssueCertificate(?int $confirmedByUserId = null, ?string $gatewayReference = null): Certificate
    {
        if ($this->status === PaymentStatus::Paid && $this->certificate) {
            return $this->certificate;
        }

        $this->update([
            'status' => PaymentStatus::Paid->value,
            'paid_at' => now(),
            'confirmed_by_user_id' => $confirmedByUserId,
            'gateway_reference' => $gatewayReference ?? $this->gateway_reference,
        ]);

        $attempt = $this->attempt;
        $certificate = $attempt->certificate;

        if ($certificate) {
            $certificate->update(['tier' => CertificateTier::Verified->value]);
        } else {
            $certificate = Certificate::issueFor($attempt, CertificateTier::Verified);
        }

        $this->update(['certificate_id' => $certificate->id]);

        return $certificate;
    }
}
