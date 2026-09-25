<?php

namespace Elibrary\Lms\Payments;

use Elibrary\Lms\Enums\CoursePaymentGateway;

/**
 * Maps a CoursePaymentGateway to its concrete checkout implementation.
 * Append-only registry — an unimplemented gateway resolves to null rather
 * than a dangling reference.
 */
class PaymentGatewayFactory
{
    private const MAP = [
        'stripe' => StripeGateway::class,
        'paystack' => PaystackGateway::class,
        'flutterwave' => FlutterwaveGateway::class,
    ];

    public function make(CoursePaymentGateway $gateway): ?PaymentGatewayContract
    {
        $class = self::MAP[$gateway->value] ?? null;

        return $class ? app($class) : null;
    }
}
