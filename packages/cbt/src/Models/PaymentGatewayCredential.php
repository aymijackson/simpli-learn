<?php

namespace Elibrary\Cbt\Models;

use App\Models\Concerns\BelongsToTenant;
use Elibrary\Cbt\Enums\PaymentGateway;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentGatewayCredential extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'gateway', 'credentials', 'is_enabled'];

    protected function casts(): array
    {
        return [
            'gateway' => PaymentGateway::class,
            'credentials' => 'encrypted:array',
            'is_enabled' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }
}
