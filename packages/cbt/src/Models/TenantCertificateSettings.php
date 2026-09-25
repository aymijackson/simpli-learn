<?php

namespace Elibrary\Cbt\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantCertificateSettings extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'default_policy', 'default_price', 'default_currency',
        'bank_transfer_instructions', 'issuer_name', 'signatory_name', 'signatory_title',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }
}
