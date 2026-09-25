<?php

namespace Elibrary\Cbt\Payments;

use App\Models\PlatformPaymentSettings;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use Elibrary\Cbt\Enums\PaymentGateway;
use Elibrary\Cbt\Models\PaymentGatewayCredential;

/**
 * The single place the platform-override decision lives. When the platform
 * mode is on, every gateway (including bank transfer — confirmed explicitly:
 * learners see the platform's own bank account too, reconciled with the
 * tenant manually afterward) resolves to the platform's own credential row
 * (tenant_id IS NULL) instead of the tenant's. Always bypasses TenantScope
 * explicitly rather than relying on ambient request context, because this is
 * also called from tenant-agnostic contexts (webhooks) with no resolved tenant.
 */
class GatewayCredentialResolver
{
    public function resolve(Tenant $tenant, PaymentGateway $gateway): ?PaymentGatewayCredential
    {
        $query = PaymentGatewayCredential::withoutGlobalScope(TenantScope::class)
            ->where('gateway', $gateway->value);

        if (PlatformPaymentSettings::current()->isPlatformManaged()) {
            return $query->whereNull('tenant_id')->first();
        }

        return $query->where('tenant_id', $tenant->id)->first();
    }
}
