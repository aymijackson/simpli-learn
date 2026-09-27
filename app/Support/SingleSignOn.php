<?php

namespace App\Support;

use App\Models\Tenant;

/**
 * Sign in with Google or Microsoft. The platform registers one OAuth app per
 * provider (credentials in .env); each workspace owner chooses which buttons
 * appear on their sign-in page and, optionally, which email domains may join
 * automatically on first sign-in.
 */
class SingleSignOn
{
    public const PROVIDERS = [
        'google' => 'Google',
        'microsoft' => 'Microsoft',
    ];

    /** Providers whose OAuth app is configured for the platform. */
    public static function available(): array
    {
        return array_filter(self::PROVIDERS, fn ($label, $key) => filled(config("services.{$key}.client_id")) && filled(config("services.{$key}.client_secret")), ARRAY_FILTER_USE_BOTH);
    }

    /** Providers this workspace shows on its sign-in page. */
    public static function enabledFor(Tenant $tenant): array
    {
        return array_intersect_key(self::available(), array_flip($tenant->sso_providers ?? []));
    }

    public static function emailDomain(string $email): string
    {
        return strtolower((string) substr(strrchr($email, '@') ?: '', 1));
    }

    /** Whether someone with this email may join the workspace automatically. */
    public static function mayJoin(Tenant $tenant, string $email): bool
    {
        return in_array(self::emailDomain($email), array_map('strtolower', $tenant->sso_domains ?? []), true);
    }
}
