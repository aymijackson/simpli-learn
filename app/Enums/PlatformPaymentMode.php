<?php

namespace App\Enums;

enum PlatformPaymentMode: string
{
    case TenantManaged = 'tenant_managed';
    case PlatformManaged = 'platform_managed';

    public function label(): string
    {
        return match ($this) {
            self::TenantManaged => 'Tenant-managed — each organization uses its own payment credentials',
            self::PlatformManaged => 'Platform-managed — all payments (including bank transfer) route through the platform',
        };
    }
}
