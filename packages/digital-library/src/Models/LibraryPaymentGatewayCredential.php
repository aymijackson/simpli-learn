<?php

namespace Elibrary\Library\Models;

use App\Models\Concerns\BelongsToTenant;
use Elibrary\Library\Enums\LibraryPaymentGateway;
use Illuminate\Database\Eloquent\Model;

class LibraryPaymentGatewayCredential extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'gateway', 'credentials', 'is_enabled'];

    protected function casts(): array
    {
        return [
            'gateway' => LibraryPaymentGateway::class,
            'credentials' => 'encrypted:array',
            'is_enabled' => 'boolean',
        ];
    }
}
