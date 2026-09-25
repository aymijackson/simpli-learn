<?php

namespace Elibrary\Lms\Models;

use App\Models\Concerns\BelongsToTenant;
use Elibrary\Lms\Enums\CoursePaymentGateway;
use Illuminate\Database\Eloquent\Model;

class CoursePaymentGatewayCredential extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'gateway', 'credentials', 'is_enabled'];

    protected function casts(): array
    {
        return [
            'gateway' => CoursePaymentGateway::class,
            'credentials' => 'encrypted:array',
            'is_enabled' => 'boolean',
        ];
    }
}
