<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Saving payment-gateway settings without ever sending a saved secret back
 * to the browser. The settings forms leave secret fields empty and only say
 * whether one is saved, so a blank secret means "keep what's there"; the
 * public key and bank details are shown and replaced as typed. A "remove
 * saved keys" checkbox clears the secrets.
 */
class GatewayCredentials
{
    public const SECRETS = ['secret_key', 'webhook_secret'];

    public static function merge(?array $existing, Request $request): array
    {
        $credentials = $existing ?? [];

        if ($request->exists('public_key')) {
            $public = trim((string) $request->input('public_key'));
            if ($public === '') {
                unset($credentials['public_key']);
            } else {
                $credentials['public_key'] = $public;
            }
        }

        if ($request->boolean('clear_secrets')) {
            foreach (self::SECRETS as $key) {
                unset($credentials[$key]);
            }
        }

        foreach (self::SECRETS as $key) {
            $value = trim((string) $request->input($key));
            if ($value !== '') {
                $credentials[$key] = $value;
            }
        }

        return $credentials;
    }

    public static function hasSecret(?array $credentials, string $key): bool
    {
        return ! empty($credentials[$key] ?? null);
    }
}
