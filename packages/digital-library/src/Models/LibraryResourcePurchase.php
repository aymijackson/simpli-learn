<?php

namespace Elibrary\Library\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\User;
use Elibrary\Library\Enums\LibraryPaymentGateway;
use Elibrary\Library\Enums\LibraryPurchaseStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LibraryResourcePurchase extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'resource_id', 'user_id', 'gateway', 'amount', 'currency',
        'status', 'reference', 'gateway_reference', 'paid_at', 'confirmed_by_user_id', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'gateway' => LibraryPaymentGateway::class,
            'status' => LibraryPurchaseStatus::class,
            'paid_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(LibraryResource::class, 'resource_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPending(): bool
    {
        return $this->status === LibraryPurchaseStatus::Pending;
    }

    /**
     * The single place a purchase gets marked paid — a paid purchase row is
     * itself the entitlement (no separate table), checked via
     * LibraryResource::isPurchasedBy(). Idempotent: safe on redelivered
     * webhooks. Grants the *right to borrow* only — it does not bypass
     * copy scarcity, which LibraryCheckoutService still governs on top.
     */
    public function markPaid(?int $confirmedByUserId = null, ?string $gatewayReference = null): void
    {
        if ($this->status === LibraryPurchaseStatus::Paid) {
            return;
        }

        $this->update([
            'status' => LibraryPurchaseStatus::Paid->value,
            'paid_at' => now(),
            'confirmed_by_user_id' => $confirmedByUserId,
            'gateway_reference' => $gatewayReference ?? $this->gateway_reference,
        ]);
    }
}
