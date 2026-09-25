<?php

namespace Elibrary\Cbt\Payments;

use Elibrary\Cbt\Enums\PaymentGateway;

/**
 * Maps a PaymentGateway to its concrete checkout implementation. Deliberately
 * a small, append-only registry — each redirect-checkout gateway (Stripe,
 * Paystack, Flutterwave) is wired in independently as it's built, so an
 * unimplemented gateway simply resolves to null (never enabled in the
 * picker, never dangling) rather than the whole feature needing all three
 * finished before any of it works.
 */
class PaymentGatewayFactory
{
    private const MAP = [
        'stripe' => StripeGateway::class,
        'paystack' => PaystackGateway::class,
        'flutterwave' => FlutterwaveGateway::class,
    ];

    public function make(PaymentGateway $gateway): ?PaymentGatewayContract
    {
        $class = self::MAP[$gateway->value] ?? null;

        return $class ? app($class) : null;
    }
}
