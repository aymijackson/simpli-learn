<?php

namespace App\Models;

use App\Enums\RevenueSplitType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Central-only — edited exclusively from the admin panel, not tenant-scoped
 * (no BelongsToTenant), since it's the platform's own record of how much of
 * a platform-collected payment is attributable to a given tenant.
 */
class TenantRevenueSplit extends Model
{
    protected $fillable = ['tenant_id', 'split_type', 'percentage', 'flat_fee', 'note'];

    protected function casts(): array
    {
        return [
            'split_type' => RevenueSplitType::class,
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** The platform's fee for a given payment amount, per this split's configuration. Null under "manual" — nothing to compute, reconciled off-system. */
    public function feeFor(float $amount): ?float
    {
        return match ($this->split_type) {
            RevenueSplitType::Percentage => round($amount * ($this->percentage ?? 0) / 100, 2),
            RevenueSplitType::FlatFee => $this->flat_fee,
            RevenueSplitType::Manual => null,
        };
    }
}
