<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Time-based one-time passwords (RFC 6238) for authenticator apps such as
 * Google Authenticator, Microsoft Authenticator or Authy — 6 digits, 30 s
 * steps, SHA-1, which is what those apps expect by default.
 */
class TwoFactor
{
    private const DIGITS = 6;

    private const PERIOD = 30;

    /** Accept codes one step either side of now, to tolerate clock drift. */
    private const WINDOW = 1;

    private const BASE32 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public static function generateSecret(): string
    {
        $bytes = random_bytes(20);
        $bits = '';
        foreach (str_split($bytes) as $byte) {
            $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }

        return implode('', array_map(fn ($chunk) => self::BASE32[bindec($chunk)], str_split($bits, 5)));
    }

    /** The code an authenticator app shows for $secret at $timestamp. */
    public static function codeAt(string $secret, int $timestamp): string
    {
        $counter = intdiv($timestamp, self::PERIOD);
        $hash = hash_hmac('sha1', pack('N*', 0, $counter), self::base32Decode($secret), true);
        $offset = ord($hash[19]) & 0x0F;
        $value = ((ord($hash[$offset]) & 0x7F) << 24)
            | (ord($hash[$offset + 1]) << 16)
            | (ord($hash[$offset + 2]) << 8)
            | ord($hash[$offset + 3]);

        return str_pad((string) ($value % 10 ** self::DIGITS), self::DIGITS, '0', STR_PAD_LEFT);
    }

    /**
     * Check a code for this user. Each accepted code is remembered briefly so
     * it can't be replayed by someone watching over a shoulder.
     */
    public static function verify(User $user, string $secret, string $code, ?int $now = null): bool
    {
        $code = preg_replace('/\s+/', '', $code);
        if (! preg_match('/^\d{'.self::DIGITS.'}$/', $code)) {
            return false;
        }

        $now ??= time();
        for ($step = -self::WINDOW; $step <= self::WINDOW; $step++) {
            $timestamp = $now + $step * self::PERIOD;
            if (hash_equals(self::codeAt($secret, $timestamp), $code)) {
                $key = "two-factor-used:{$user->id}:".intdiv($timestamp, self::PERIOD);

                return Cache::add($key, true, self::PERIOD * (2 * self::WINDOW + 2));
            }
        }

        return false;
    }

    /** Link that authenticator apps read from the QR code. */
    public static function otpauthUrl(User $user, string $secret, string $issuer): string
    {
        $label = rawurlencode($issuer).':'.rawurlencode($user->email);

        return "otpauth://totp/{$label}?".http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'digits' => self::DIGITS,
            'period' => self::PERIOD,
        ]);
    }

    /** @return list<string> eight single-use codes like "k3f9q-2mxp7" */
    public static function generateRecoveryCodes(): array
    {
        return collect(range(1, 8))
            ->map(fn () => Str::lower(Str::random(5)).'-'.Str::lower(Str::random(5)))
            ->all();
    }

    /** Use up a recovery code if it matches one of the user's. */
    public static function useRecoveryCode(User $user, string $code): bool
    {
        $code = Str::lower(trim($code));
        $codes = $user->two_factor_recovery_codes ?? [];

        foreach ($codes as $index => $stored) {
            if (hash_equals($stored, $code)) {
                unset($codes[$index]);
                $user->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();

                return true;
            }
        }

        return false;
    }

    private static function base32Decode(string $secret): string
    {
        $bits = '';
        foreach (str_split(strtoupper(rtrim($secret, '='))) as $char) {
            $position = strpos(self::BASE32, $char);
            if ($position === false) {
                continue;
            }
            $bits .= str_pad(decbin($position), 5, '0', STR_PAD_LEFT);
        }

        return implode('', array_map(fn ($byte) => chr(bindec($byte)), array_filter(str_split($bits, 8), fn ($byte) => strlen($byte) === 8)));
    }
}
